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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Contract;

use JsonSerializable;

/**
 * Interfaz para una entidad de persona natural.
 */
interface PersonaNaturalInterface extends JsonSerializable
{
    /**
     * Devuelve el RUN completo (incluyendo el DV) de la persona natural.
     *
     * @return string RUN completo de la persona natural.
     */
    public function getRun(): string;

    /**
     * Devuelve el nombre de la persona natural.
     *
     * Si no hay nombre, devuelve el RUN.
     *
     * @return string Nombre o RUN.
     */
    public function getNombre(): string;

    /**
     * Devuelve el correo electrónico de la persona natural.
     *
     * @return string|null Correo electrónico de la persona natural o null.
     */
    public function getEmail(): ?string;

    /**
     * Entrega los datos de la persona natural en un arreglo.
     *
     * @return array<array-key, mixed> Arreglo con los datos de la persona natural.
     */
    public function toArray(): array;
}
