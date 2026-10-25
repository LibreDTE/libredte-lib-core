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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\SubmitDocumentAcceptanceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\SubmitDocumentAcceptanceResponse;
use Throwable;

/**
 * Ingresa una aceptación o reclamo de un DTE en el RCV del SII.
 *
 * Acciones válidas:
 *   - ERM: Otorga recibo de mercaderías o servicios.
 *   - ACD: Acepta contenido del documento.
 *   - RCD: Reclamo al contenido del documento.
 *   - RFP: Reclamo por falta parcial de mercaderías.
 *   - RFT: Reclamo por falta total de mercaderías.
 */
#[Job(name: 'submit_document_acceptance', worker: 'sii_rcv', component: 'integration', package: 'billing')]
class SubmitDocumentAcceptanceJob extends AbstractJob implements JobInterface
{
    private const WSDL_SERVICE = 'registroreclamodteservice';

    private const VALID_ACTIONS = ['ERM', 'ACD', 'RCD', 'RFP', 'RFT'];

    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Ingresa una aceptación o reclamo de un DTE.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $action Acción a registrar (ERM, ACD, RCD, RFP, RFT).
     * @return SubmitDocumentAcceptanceResponse
     * @throws SubmitDocumentAcceptanceException En caso de error.
     */
    public function submit(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $action
    ): SubmitDocumentAcceptanceResponse {
        if (!in_array($action, self::VALID_ACTIONS, true)) {
            throw new SubmitDocumentAcceptanceException([
                'Action "{action}" is not valid. Valid actions are: {valid}.',
                'action' => $action,
                'valid' => implode(', ', self::VALID_ACTIONS),
            ]);
        }

        Rut::validate($company);
        [$rut, $dv] = Rut::toArray($company);

        try {
            $token = $this->siiLazyWorker->authenticate($request);
            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: self::WSDL_SERVICE,
                function: 'ingresarAceptacionReclamoDoc',
                args: [
                    'rutEmisor' => $rut,
                    'dvEmisor' => $dv,
                    'tipoDoc' => $document,
                    'folio' => $number,
                    'accionDoc' => $action,
                ],
                token: $token
            );

            return new SubmitDocumentAcceptanceResponse(
                $this->xmlService->decode($xmlResponse)
            );
        } catch (Throwable $e) {
            throw new SubmitDocumentAcceptanceException([
                'Could not submit action "{action}" for document type {type} with number {folio} for {company} in the SII RCV. {detail}',
                'action' => $action,
                'type' => $document,
                'folio' => $number,
                'company' => $company,
                'detail' => $e,
            ], code: $e->getCode(), previous: $e, request: $request);
        }
    }
}
