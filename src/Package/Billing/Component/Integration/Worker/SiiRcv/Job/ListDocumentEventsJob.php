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
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\ListDocumentEventsException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\ListDocumentEventsResponse;
use Throwable;

/**
 * Lista el historial de eventos de un DTE en el RCV del SII.
 */
#[Job(name: 'list_document_events', worker: 'sii_rcv', component: 'integration', package: 'billing')]
class ListDocumentEventsJob extends AbstractJob implements JobInterface
{
    private const WSDL_SERVICE = 'registroreclamodteservice';

    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Lista los eventos históricos de un DTE.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @return ListDocumentEventsResponse
     * @throws ListDocumentEventsException En caso de error.
     */
    public function list(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): ListDocumentEventsResponse {
        Rut::validate($company);
        [$rut, $dv] = Rut::toArray($company);

        try {
            $token = $this->siiLazyWorker->authenticate($request);
            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: self::WSDL_SERVICE,
                function: 'listarEventosHistDoc',
                args: [
                    'rutEmisor' => $rut,
                    'dvEmisor' => $dv,
                    'tipoDoc' => $document,
                    'folio' => $number,
                ],
                token: $token
            );

            return new ListDocumentEventsResponse(
                $this->xmlService->decode($xmlResponse)
            );
        } catch (Throwable $e) {
            throw new ListDocumentEventsException(sprintf(
                'No fue posible listar los eventos del documento T%dF%d de %s en el RCV del SII. %s',
                $document,
                $number,
                $company,
                $e->getMessage()
            ), code: $e->getCode(), previous: $e, request: $request);
        }
    }
}
