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
 * Interfaz para las estrategias del worker "billing.exchange.sender".
 */
interface SenderStrategyInterface extends StrategyInterface
{
    /**
     * Envía documentos a través del proceso de intercambio.
     *
     * @param ExchangeBagInterface $bag Bolsa con los sobres y opciones para
     * realizar el intercambio de documentos.
     * @return ExchangeResultInterface[] Los resultados de procesar los sobres
     * de la bolsa. Tiene el estado de cada estrategia que procesó el sobre.
     */
    public function send(ExchangeBagInterface $bag): array;

    /**
     * Indica si la estrategia puede enviar un sobre específico.
     *
     * La bolsa se entrega como contexto (para datos comunes a todos los
     * sobres, como las opciones de transporte) — la evaluación es siempre
     * sobre el sobre puntual que se pasa, no sobre la bolsa completa.
     *
     * @param EnvelopeInterface $envelope Sobre que se desea enviar.
     * @param ExchangeBagInterface $bag Bolsa que contiene el sobre, con las
     * opciones comunes a todos los sobres que envíe esta llamada.
     * @return void
     * @throws ExchangeException Motivo por el que no se puede procesar.
     */
    public function canSend(EnvelopeInterface $envelope, ExchangeBagInterface $bag): void;
}
