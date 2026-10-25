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
use Throwable;

/**
 * Interfaz para el estado del resultado de una estrategia de intercambio de un
 * sobre específico.
 */
interface ExchangeStatusInterface extends JsonSerializable
{
    /**
     * Entrega el código de la estrategia que generó este estado de intercambio
     * de un sobre.
     *
     * @return string
     */
    public function getStrategy(): string;

    /**
     * Indica si el estado del resultado de la ejecución de la estrategia al
     * procesar el sobre fue OK (se logró procesar sin problemas).
     *
     * @return bool
     */
    public function isOk(): bool;

    /**
     * Asigna el error que ocurrió al procesar el sobre.
     *
     * @param Throwable $error
     * @return static
     */
    public function setError(Throwable $error): static;

    /**
     * Entrega el error o excepción que se generó al procesar el sobre.
     *
     * @return Throwable|null
     */
    public function getError(): ?Throwable;

    /**
     * Indica si el estado tiene un error.
     *
     * @return bool
     */
    public function hasError(): bool;

    /**
     * Asigna los metadatos del estado del resultado.
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
     * Obtiene los metadatos del estado del resultado.
     *
     * @return BagInterface
     */
    public function getMetadata(): BagInterface;

    /**
     * Convierte el estado a un array.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
