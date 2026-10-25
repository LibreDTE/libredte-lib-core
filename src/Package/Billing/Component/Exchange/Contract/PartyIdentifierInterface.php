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

use JsonSerializable;

/**
 * Interfaz para los identificadores de los participantes.
 *
 * Representa la identidad legal/fiscal de la organización.
 *
 * En el caso de Chile es el RUT.
 */
interface PartyIdentifierInterface extends JsonSerializable
{
    /**
     * Obtiene el identificador completo del participante.
     *
     * El identificador completo está formado por el ID del esquema y el valor
     * del ID del participante unidos por ":".
     *
     * Ejemplo: `CL-RUT:76192083-9`.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Obtiene el ID del esquema del valor del ID del participante.
     *
     * Es la parte a la izquierda del ID.
     *
     * Ejemplo: `CL-RUT`.
     *
     * @return string
     */
    public function getSchemeId(): string;

    /**
     * Entrega el nombre del esquema del valor del ID del participante.
     *
     * Ejemplo: `Rol Único Tributario (RUT) de Chile`.
     *
     * @return string
     */
    public function getSchemeName(): string;

    /**
     * Obtiene el valor del ID del participante.
     *
     * Este valor del ID es único en el contexto del esquema del ID.
     *
     * Es la parte a la derecha del ID.
     *
     * Ejemplo: `76192083-9`.
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Entrega la autoridad que registra y lista los valores del ID de los
     * participantes en el contexto del esquema del ID.
     *
     * Ejemplo: `CL-SII`.
     *
     * @return string
     */
    public function getAuthority(): string;

    /**
     * Convierte el identificador a un array.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
