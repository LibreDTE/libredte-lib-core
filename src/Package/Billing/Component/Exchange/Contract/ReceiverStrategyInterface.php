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

use Derafu\Backbone\Contract\StrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;

/**
 * Interfaz para las estrategias del "billing.exchange.receiver".
 */
interface ReceiverStrategyInterface extends StrategyInterface
{
    /**
     * Recibe documentos a través del proceso de intercambio.
     *
     * @param ExchangeBagInterface $bag Bolsa con las opciones para realizar el
     * intercambio de documentos.
     * @return ExchangeResultInterface[] El resultado del intercambio donde cada
     * resultado contiene un sobre con el estado de la recepción.
     */
    public function receive(ExchangeBagInterface $bag): array;

    /**
     * Indica si la estrategia puede recibir documentos con los datos/opciones
     * de una bolsa.
     *
     * @param ExchangeBagInterface $bag
     * @return void
     * @throws ExchangeException Motivo por el que no se puede procesar.
     */
    public function canReceive(ExchangeBagInterface $bag): void;
}
