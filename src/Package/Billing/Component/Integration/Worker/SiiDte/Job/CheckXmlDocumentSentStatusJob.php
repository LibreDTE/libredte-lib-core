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
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\CheckXmlDocumentSentStatusException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDteException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\ConsumeWebserviceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\CheckXmlDocumentSentStatusResponse;

/**
 * Clase para realizar las consultas de validación de documentos al SII.
 */
#[Job(name: 'check_xml_document_sent_status', worker: 'sii_dte', component: 'integration', package: 'billing')]
class CheckXmlDocumentSentStatusJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Obtiene el estado actualizado del envío de un documento XML al SII.
     *
     * Este estado podría no ser el final, si no es un estado final se debe
     * reintentar la consulta posteriormente al SII.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/estado_envio.pdf
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param int $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del XML que se envió.
     * @return CheckXmlDocumentSentStatusResponse
     * @throws CheckXmlDocumentSentStatusException En caso de error.
     */
    public function checkSentStatus(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): CheckXmlDocumentSentStatusResponse {
        // Validar el RUT de la empresa que se utilizará para la consulta del
        // estado de envío al SII.
        Rut::validate($company);
        [$rutCompany, $dvCompany] = Rut::toArray($company);

        // Obtener el token asociado al certificado digital.
        $token = $this->siiLazyWorker->authenticate($request);

        // Datos para la consulta.
        $requestData = [
            'Rut' => $rutCompany,
            'Dv' => $dvCompany,
            'TrackId' => $trackId,
            'Token' => $token,
        ];

        // Consultar el estado del documento enviado al SII, decodificar la
        // respuesta y construir el resultado — todo dentro del mismo try,
        // para que tanto un fallo de red al consumir el webservice como una
        // respuesta del SII sin la forma esperada se envuelvan en la misma
        // excepción.
        try {
            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: 'QueryEstUp',
                function: 'getEstUp',
                args: $requestData
            );

            $responseData = $this->xmlService->decode($xmlResponse);

            return new CheckXmlDocumentSentStatusResponse(
                $responseData,
                $requestData
            );
        } catch (ConsumeWebserviceException | SiiDteException $e) {
            throw new CheckXmlDocumentSentStatusException(sprintf(
                'No fue posible obtener el estado del XML enviado al SII con Track ID %s. %s',
                $trackId,
                $e->getMessage()
            ));
        }
    }
}
