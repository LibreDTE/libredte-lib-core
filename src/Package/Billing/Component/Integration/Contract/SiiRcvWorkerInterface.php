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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\CheckDocumentAssignabilityException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\GetDocumentSiiReceptionDateException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\ListDocumentEventsException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\SubmitDocumentAcceptanceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\CheckDocumentAssignabilityResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\GetDocumentSiiReceptionDateResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\ListDocumentEventsResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\SubmitDocumentAcceptanceResponse;
use UnexpectedValueException;

/**
 * Interfaz del worker del Registro de Compra y Venta (RCV) del SII.
 */
interface SiiRcvWorkerInterface extends WorkerInterface
{
    /**
     * Ingresa una aceptación o reclamo de un DTE en el RCV del SII.
     *
     * Acciones válidas:
     *
     *   - `ERM`: Entrega Real de Mercaderías o Servicios (Ley 19.983).
     *   - `ACD`: Acepta Contenido del Documento.
     *   - `RCD`: Reclamo al Contenido del Documento.
     *   - `RFP`: Reclamo por Falta Parcial de Mercaderías.
     *   - `RFT`: Reclamo por Falta Total de Mercaderías.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $action Acción a registrar.
     * @return SubmitDocumentAcceptanceResponse El resultado del registro de
     * la acción: código y descripción.
     * @throws SubmitDocumentAcceptanceException En caso de error.
     */
    public function submitDocumentAcceptance(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $action
    ): SubmitDocumentAcceptanceResponse;

    /**
     * Lista el historial de eventos de un DTE en el RCV del SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @return ListDocumentEventsResponse El historial de eventos del DTE en
     * el RCV.
     * @throws ListDocumentEventsException En caso de error.
     */
    public function listDocumentEvents(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): ListDocumentEventsResponse;

    /**
     * Consulta si un DTE puede ser cedido (factoring/AEC).
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @return CheckDocumentAssignabilityResponse El resultado de la
     * consulta: código y descripción indicando si el DTE puede ser cedido.
     * @throws CheckDocumentAssignabilityException En caso de error.
     */
    public function checkDocumentAssignability(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): CheckDocumentAssignabilityResponse;

    /**
     * Consulta la fecha en que el SII recibió un DTE.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @return GetDocumentSiiReceptionDateResponse La fecha de recepción del
     * DTE en el SII.
     * @throws UnexpectedValueException Si el RUT de la empresa es inválido.
     * @throws GetDocumentSiiReceptionDateException En caso de error.
     */
    public function getDocumentSiiReceptionDate(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): GetDocumentSiiReceptionDateResponse;
}
