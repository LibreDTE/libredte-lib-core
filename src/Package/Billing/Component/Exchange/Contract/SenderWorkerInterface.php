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

use Derafu\Backbone\Contract\HandlersAwareInterface;
use Derafu\Backbone\Contract\StrategiesAwareInterface;
use Derafu\Backbone\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;

/**
 * Interfaz para el worker "billing.exchange.sender".
 */
interface SenderWorkerInterface extends WorkerInterface, HandlersAwareInterface, StrategiesAwareInterface
{
    /**
     * Envía documentos a través del proceso de intercambio mediante la
     * estrategia definida en la bolsa.
     *
     * @param ExchangeBagInterface $bag Bolsa con los sobres y opciones para
     * realizar el intercambio de documentos.
     * @return ExchangeResultInterface[] Los resultados de procesar los sobres
     * de la bolsa. Tiene el estado de cada estrategia que procesó el sobre.
     * @throws ExchangeException En caso de error al enviar los documentos.
     */
    public function send(ExchangeBagInterface $bag): array;

    /**
     * Procesa una bolsa de intercambio, sus sobres y docuemntos.
     *
     * Este método determinará "qué" sobres de la bolsa debe transportar, y si
     * es posible transportarlos los pasará a las estrategias que correspondan
     * para que realicen el intercambio.
     *
     * Si bien existe send(), este método es necesario porque se podría querer
     * realizar el transporte utilizando múltiples estrategias disponibles y
     * soportadas por el worker.
     *
     * @param ExchangeBagInterface $bag Bolsa con los sobres y opciones para
     * realizar el intercambio de documentos.
     * @return ExchangeResultInterface[] Los resultados de procesar los sobres
     * de la bolsa. Tiene el estado de cada estrategia que procesó el sobre.
     */
    public function handle(ExchangeBagInterface $bag): array;
}
