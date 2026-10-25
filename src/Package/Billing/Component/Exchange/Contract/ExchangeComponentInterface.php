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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Contract;

use Derafu\Backbone\Contract\ComponentInterface;

/**
 * Interfaz para `ExchangeComponent`.
 */
interface ExchangeComponentInterface extends ComponentInterface
{
    /**
     * Entrega el worker "billing.exchange.receiver".
     *
     * @return ReceiverWorkerInterface
     */
    public function getReceiverWorker(): ReceiverWorkerInterface;

    /**
     * Entrega el worker "billing.exchange.sender".
     *
     * @return SenderWorkerInterface
     */
    public function getSenderWorker(): SenderWorkerInterface;

    /**
     * Entrega el worker "billing.exchange.document_response".
     *
     * @return DocumentResponseWorkerInterface
     */
    public function getDocumentResponseWorker(): DocumentResponseWorkerInterface;

    /**
     * Recibe documentos a través del proceso de intercambio mediante la
     * estrategia definida en la bolsa.
     *
     * @param ExchangeBagInterface $bag Bolsa con las opciones para realizar el
     * intercambio de documentos.
     * @return ExchangeResultInterface[] El resultado del intercambio donde cada
     * resultado contiene un sobre con el estado de la recepción.
     */
    public function receive(ExchangeBagInterface $bag): array;

    /**
     * Envía documentos a través del proceso de intercambio mediante la
     * estrategia definida en la bolsa.
     *
     * @param ExchangeBagInterface $bag Bolsa con los sobres y opciones para
     * realizar el intercambio de documentos.
     * @return ExchangeResultInterface[] Los resultados de procesar los sobres
     * de la bolsa. Tiene el estado de cada estrategia que procesó el sobre.
     */
    public function send(ExchangeBagInterface $bag): array;

    /**
     * Procesa una bolsa de intercambio, sus sobres y documentos.
     *
     * Este método determinará "qué" sobres de la bolsa debe transportar, y si
     * es posible transportarlos los pasará a las estrategias que correspondan
     * para que realicen el intercambio.
     *
     * Si bien existe receive() y send(), este método es necesario porque se
     * podría querer realizar el transporte utilizando múltiples estrategias
     * disponibles y soportadas por el worker.
     *
     * La decisión de si es un envío o recepción se toma simplemente según si la
     * bolsa tiene o no asignados sobres al ser procesada. Si tiene sobres es un
     * envío, si no tiene es recepción. Se espera que las opciones de la bolsa
     * coincidan con la acción que se realizará.
     *
     * @param ExchangeBagInterface $bag Bolsa con los sobres y opciones para
     * realizar el intercambio de documentos.
     * @return ExchangeResultInterface[] Los resultados de procesar los sobres
     * de la bolsa. Tiene el estado de cada estrategia que procesó el sobre.
     */
    public function handle(ExchangeBagInterface $bag): array;
}
