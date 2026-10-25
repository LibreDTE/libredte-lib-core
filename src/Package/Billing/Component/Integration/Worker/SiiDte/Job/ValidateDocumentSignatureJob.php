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
use Derafu\Support\Date;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\ValidateDocumentSignatureException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDteException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\ConsumeWebserviceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentSignatureResponse;

/**
 * Clase para el envío de documentos al SII.
 *
 * Principalmente es para el envío y consulta de estado del envío de documentos
 * tributarios electrónicos en formato XML.
 */
#[Job(name: 'validate_document_signature', worker: 'sii_dte', component: 'integration', package: 'billing')]
class ValidateDocumentSignatureJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Obtiene el estado avanzado de un documento en el SII.
     *
     * Este estado solo se obtiene si el documento se encuentra aceptado por el
     * SII, ya sea aceptado 100% OK o con reparos.
     *
     * Este servicio valida que el documento exista en SII (esté aceptado), que
     * los datos del documento proporcionados coincidan. Finalmente, valida que
     * la firma electrónica del documento coincida con la enviada al SII.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/OIFE2006_QueryEstDteAv_MDE.pdf
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT de la empresa emisora del documento.
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $date Fecha de emisión del documento, formato: AAAA-MM-DD.
     * @param int $total Total del documento.
     * @param string $recipient RUT del receptor del documento.
     * @param string $signature Tag DTE/Signature/SignatureValue del XML.
     * @return ValidateDocumentSignatureResponse
     * @throws ValidateDocumentSignatureException En caso de error.
     */
    public function validate(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient,
        string $signature
    ): ValidateDocumentSignatureResponse {
        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        Rut::validate($company);
        Rut::validate($recipient);
        [$rutCompany, $dvCompany] = Rut::toArray($company);
        [$rutRecipient, $dvRecipient] = Rut::toArray($recipient);

        // Validar fecha y convertir al formato del SII.
        $dateSii = Date::validateAndConvert($date, 'dmY');
        if ($dateSii === null) {
            throw new ValidateDocumentSignatureException(sprintf(
                'La fecha %s del documento no es válida, debe tener formato AAAA-MM-DD.',
                $date
            ));
        }

        // Obtener el token asociado al certificado digital.
        $token = $this->siiLazyWorker->authenticate($request);

        // Datos para la consulta.
        $requestData = [
            'RutEmpresa' => $rutCompany,
            'DvEmpresa' => $dvCompany,
            'RutReceptor' => $rutRecipient,
            'DvReceptor' => $dvRecipient,
            'TipoDte' => $document,
            'FolioDte' => $number,
            'FechaEmisionDte' => $dateSii,
            'MontoDte' => $total,
            'FirmaDte' => $signature,
            'Token' => $token,
        ];

        // Consultar el estado del documento, incluyendo su firma, al SII,
        // decodificar la respuesta y construir el resultado — todo dentro
        // del mismo try, para que tanto un fallo de red al consumir el
        // webservice como una respuesta del SII sin la forma esperada se
        // envuelvan en la misma excepción.
        try {
            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: 'QueryEstDteAv',
                function: 'getEstDteAv',
                args: $requestData
            );

            $responseData = $this->xmlService->decode($xmlResponse);

            return new ValidateDocumentSignatureResponse(
                $responseData,
                $requestData
            );
        } catch (ConsumeWebserviceException | SiiDteException $e) {
            throw new ValidateDocumentSignatureException(sprintf(
                'No fue posible obtener el estado de la firma del documento T%dF%d de %d-%s desde el SII. %s',
                $document,
                $number,
                $rutCompany,
                $dvCompany,
                $e->getMessage()
            ));
        }
    }
}
