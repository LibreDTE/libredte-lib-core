<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\RequestXmlDocumentSentStatusByEmailException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDteException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\ConsumeWebserviceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\RequestXmlDocumentSentStatusByEmailResponse;

/**
 * Clase para realizar las consultas de validación de documentos al SII.
 */
#[Job(name: 'request_xml_document_sent_status_by_email', worker: 'sii_dte', component: 'integration', package: 'billing')]
class RequestXmlDocumentSentStatusByEmailJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Solicita al SII que le envíe el estado del DTE mediente correo
     * electrónico.
     *
     * El correo al que se informa el estado del DTE es el que está configurado
     * en el SII, no siendo posible asignarlo mediante el servicio web.
     *
     * La principal ventaja de utilizar este método es que el SII en el correo
     * incluye los detalles de los rechazos, algo que no entrega a través del
     * servicio web de consulta del estado del envío del XML al SII.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/OIFE2005_wsDTECorreo_MDE.pdf
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param int $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del documento.
     * @return RequestXmlDocumentSentStatusByEmailResponse
     * @throws RequestXmlDocumentSentStatusByEmailException En caso de error.
     */
    public function requestEmail(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): RequestXmlDocumentSentStatusByEmailResponse {
        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        Rut::validate($company);
        [$rutCompany, $dvCompany] = Rut::toArray($company);

        // Obtener el token asociado al certificado digital.
        $token = $this->siiLazyWorker->authenticate($request);

        // Datos para la consulta.
        $requestData = [
            'Token' => $token,
            'RutEmpresa' => $rutCompany,
            'DvEmpresa' => $dvCompany,
            'TrackId' => $trackId,
        ];

        // Solicitar al SII que envíe el correo electrónico del estado del
        // DTE, decodificar la respuesta y construir el resultado — todo
        // dentro del mismo try, para que tanto un fallo de red al consumir
        // el webservice como una respuesta del SII sin la forma esperada
        // se envuelvan en la misma excepción.
        try {
            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: 'wsDTECorreo',
                function: 'reenvioCorreo',
                args: $requestData
            );

            $responseData = $this->xmlService->decode($xmlResponse);

            return new RequestXmlDocumentSentStatusByEmailResponse(
                $responseData,
                $requestData
            );
        } catch (ConsumeWebserviceException | SiiDteException $e) {
            throw new RequestXmlDocumentSentStatusByEmailException([
                'Could not request the email with upload status {trackId} from the SII. {detail}',
                'trackId' => $trackId,
                'detail' => $e,
            ]);
        }
    }
}
