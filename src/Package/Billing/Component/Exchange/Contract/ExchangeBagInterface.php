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

use Derafu\Config\Contract\OptionsAwareInterface;
use JsonSerializable;

/**
 * Interfaz que almacena sobres, y sus documentos, más los datos para enviar o
 * recibir durante el proceso de intercambio de documentos electrónicos.
 *
 * Contiene los sobres y todas las opciones necesarias para el intercambio.
 */
interface ExchangeBagInterface extends OptionsAwareInterface, JsonSerializable
{
    /**
     * Agrega un sobre a la bolsa.
     *
     * @param EnvelopeInterface $envelope
     * @return static
     */
    public function addEnvelope(EnvelopeInterface $envelope): static;

    /**
     * Obtiene el listado de sobres que la bolsa tiene.
     *
     * @return EnvelopeInterface[]
     */
    public function getEnvelopes(): array;

    /**
     * Indica si la bolsa tiene o no sobres dentro.
     *
     * @return bool
     */
    public function hasEnvelopes(): bool;

    /**
     * Obtiene los resultados del proceso de intercambio.
     *
     * @return ExchangeResultInterface[]
     */
    public function getResults(): array;

    /**
     * Agrega un resultado del proceso de intercambio.
     *
     * Al agregar un resultado si el sobre del resultado no está en los sobres
     * de la bolsa se espera que se agreguen automáticamente.
     *
     * @param ExchangeResultInterface $result
     * @return static
     */
    public function addResult(ExchangeResultInterface $result): static;

    /**
     * Convierte la bolsa a un array.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
