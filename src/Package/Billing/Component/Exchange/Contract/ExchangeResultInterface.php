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

use Derafu\Container\Contract\BagInterface;
use JsonSerializable;

/**
 * Interfaz para el resultado del proceso de intercambio de un sobre.
 */
interface ExchangeResultInterface extends JsonSerializable
{
    /**
     * Obtiene el sobre asociado a la operación, y resultado, de intercambio.
     *
     * @return EnvelopeInterface
     */
    public function getEnvelope(): EnvelopeInterface;

    /**
     * Entrega los códigos de las estrategias que procesaron el sobre.
     *
     * @return string[]
     */
    public function getStrategies(): array;

    /**
     * Entrega los estados del intercambio de las estrategias que participaron
     * en el intercambio del sobre asociado.
     *
     * @return ExchangeStatusInterface[]
     */
    public function getStatuses(): array;

    /**
     * Agrega el estado de resultado de una estrategia al resultado general del
     * intercambio del sobre.
     *
     * @return static
     */
    public function addStatus(ExchangeStatusInterface $status): static;

    /**
     * Asigna los metadatos del resultado.
     *
     * @param BagInterface|array $metadata
     * @return static
     */
    public function setMetadata(BagInterface|array $metadata): static;

    /**
     * Agrega una clave específica a los metadatos.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addMetadata(string $key, mixed $value): static;

    /**
     * Obtiene los metadatos del resultado.
     *
     * @return BagInterface
     */
    public function getMetadata(): BagInterface;

    /**
     * Convierte el resultado a un array.
     *
     * El sobre se referencia por su `businessMessageID` (no se embebe
     * completo) — el sobre completo siempre está disponible en
     * `ExchangeBagInterface::toArray()['envelopes']`, ya que
     * `ExchangeBag::addResult()` garantiza que todo sobre con un resultado
     * también quede agregado a los sobres de la bolsa.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
