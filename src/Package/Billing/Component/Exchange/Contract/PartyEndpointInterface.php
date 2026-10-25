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
 * Interfaz para los puntos técnicos de recepción de documentos de los
 * participantes del intercambio.
 *
 * Representa el punto técnico de recepción de los documentos.
 *
 * En el caso de Chile oficialmente es el "correo de contacto empresas" definido
 * en el SII.
 */
interface PartyEndpointInterface extends JsonSerializable
{
    /**
     * Obtiene el identificador completo del punto técnico de recepción.
     *
     * El identificador completo está formado por el ID del esquema y el valor
     * del punto técnico de recepción unidos por ":".
     *
     * Ejemplo: `EMAIL:correo-sasco@example.com`.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Obtiene el ID del esquema del valor del punto técnico de recepción.
     *
     * Es la parte a la izquierda del ID.
     *
     * Ejemplo: `EMAIL`.
     *
     * @return string
     */
    public function getSchemeId(): string;

    /**
     * Entrega el nombre del esquema del valor del ID del punto técnico de
     * recepción.
     *
     * Ejemplo: `Correo electrónico`.
     *
     * @return string
     */
    public function getSchemeName(): string;

    /**
     * Obtiene el valor del punto técnico de recepción.
     *
     * Este valor es único en el contexto del esquema del ID.
     *
     * Es la parte a la derecha del ID.
     *
     * Ejemplo: `correo-sasco@example.com`.
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Convierte el punto de recepción a un array.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
