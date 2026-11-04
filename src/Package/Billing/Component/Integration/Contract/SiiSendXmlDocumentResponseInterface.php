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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Contract;

use JsonSerializable;

/**
 * Interfaz para la respuesta del SII al enviar un documento XML.
 *
 * Es el mínimo común de los envíos de DTE y de boletas. Cada tipo de envío
 * puede extenderla con lo que le es propio.
 */
interface SiiSendXmlDocumentResponseInterface extends JsonSerializable
{
    /**
     * Entrega el Track ID asignado por el SII al recibir el envío.
     *
     * @return int
     */
    public function getTrackId(): int;

    /**
     * Entrega los datos de la respuesta como un arreglo.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
