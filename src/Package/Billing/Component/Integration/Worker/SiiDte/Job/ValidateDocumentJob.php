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
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\ValidateDocumentException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDteException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\ConsumeWebserviceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentResponse;

/**
 * Clase para el envío de documentos al SII.
 *
 * Principalmente es para el envío y consulta de estado del envío de documentos
 * tributarios electrónicos en formato XML.
 */
#[Job(name: 'validate_document', worker: 'sii_dte', component: 'integration', package: 'billing')]
class ValidateDocumentJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Obtiene el estado de un documento en el SII.
     *
     * Este estado solo se obtiene si el documento se encuentra aceptado por el
     * SII, ya sea aceptado 100% OK o con reparos.
     *
     * Este servicio valida que el documento exista en SII (esté aceptado) y
     * además que los datos del documento proporcionados coincidan.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/estado_dte.pdf
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT de la empresa emisora del documento.
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $date Fecha de emisión del documento, formato: AAAA-MM-DD.
     * @param int $total Total del documento.
     * @param string $recipient RUT del receptor del documento.
     * @return ValidateDocumentResponse
     * @throws ValidateDocumentException En caso de error.
     */
    public function validate(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient
    ): ValidateDocumentResponse {
        // Validar que se haya proporcionado un certificado digital.
        if ($request->getCertificate() === null) {
            throw new ValidateDocumentException(
                'No digital certificate was provided for the document status query.'
            );
        }

        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        $user = $request->getCertificate()->getId();
        Rut::validate($user);
        Rut::validate($company);
        Rut::validate($recipient);
        [$rutUser, $dvUser] = Rut::toArray($user);
        [$rutCompany, $dvCompany] = Rut::toArray($company);
        [$rutRecipient, $dvRecipient] = Rut::toArray($recipient);

        // Validar fecha y convertir al formato del SII.
        $dateSii = Date::validateAndConvert($date, 'dmY');
        if ($dateSii === null) {
            throw new ValidateDocumentException([
                'Document date {date} is not valid; it must be in YYYY-MM-DD format.',
                'date' => $date,
            ]);
        }

        // Obtener el token asociado al certificado digital.
        $token = $this->siiLazyWorker->authenticate($request);

        // Datos para la consulta.
        $requestData = [
            'RutConsultante' => $rutUser,
            'DvConsultante' => $dvUser,
            'RutCompania' => $rutCompany,
            'DvCompania' => $dvCompany,
            'RutReceptor' => $rutRecipient,
            'DvReceptor' => $dvRecipient,
            'TipoDte' => $document,
            'FolioDte' => $number,
            'FechaEmisionDte' => $dateSii,
            'MontoDte' => $total,
            'Token' => $token,
        ];

        // Consultar el estado del documento al SII, decodificar la
        // respuesta y construir el resultado — todo dentro del mismo try,
        // para que tanto un fallo de red al consumir el webservice como una
        // respuesta del SII sin la forma esperada se envuelvan en la misma
        // excepción.
        try {
            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: 'QueryEstDte',
                function: 'getEstDte',
                args: $requestData
            );

            $responseData = $this->xmlService->decode($xmlResponse);

            return new ValidateDocumentResponse($responseData, $requestData);
        } catch (ConsumeWebserviceException | SiiDteException $e) {
            throw new ValidateDocumentException([
                'Could not obtain status for document type {type} with number {folio} for {rut}-{dv} from the SII. {detail}',
                'type' => $document,
                'folio' => $number,
                'rut' => $rutCompany,
                'dv' => $dvCompany,
                'detail' => $e,
            ]);
        }
    }
}
