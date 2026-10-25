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
use Symfony\Component\Mime\Address;

/**
 * Interfaz base para los participantes del intercambio.
 */
interface PartyInterface extends JsonSerializable
{
    /**
     * Identificador único del participante.
     */
    public function getIdentifier(): PartyIdentifierInterface;

    /**
     * Agrega un punto de recepción de documentos al participante.
     *
     * @param PartyEndpointInterface $endpoint
     * @return static
     */
    public function addEndpoint(PartyEndpointInterface $endpoint): static;

    /**
     * Puntos de recepción de documentos soportados por el participante.
     *
     * @return PartyEndpointInterface[]
     */
    public function getEndpoints(): array;

    /**
     * Obtiene las direcciones de correo electrónico registradas como puntos de
     * recepción de documentos del participante.
     *
     * @return Address[]
     */
    public function getEmails(): array;

    /**
     * Convierte el participante a un array.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
