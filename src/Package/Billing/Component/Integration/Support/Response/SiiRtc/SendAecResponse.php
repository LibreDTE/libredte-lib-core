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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc;

use JsonSerializable;

/**
 * Respuesta del RTC del SII al enviar un AEC.
 *
 * Contiene el Track ID asignado por el SII para hacer seguimiento del envío
 * del Archivo Electrónico de Cesión (AEC).
 */
class SendAecResponse implements JsonSerializable
{
    private readonly int $trackId;

    public function __construct(array $response/*, array $request = []*/)
    {
        $inner = reset($response);
        $this->trackId = is_array($inner) ? (int) ($inner['TRACKID'] ?? 0) : 0;
    }

    /**
     * Entrega el Track ID asignado por el RTC del SII al recibir el AEC.
     */
    public function getTrackId(): int
    {
        return $this->trackId;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return ['track_id' => $this->trackId];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
