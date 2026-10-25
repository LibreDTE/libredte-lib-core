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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte;

use JsonSerializable;

/**
 * Respuesta del SII al enviar un documento XML (EnvioDTE o EnvioBOLETA).
 *
 * Contiene el Track ID asignado por el SII para hacer seguimiento del envío.
 */
class SendXmlDocumentResponse implements JsonSerializable
{
    private readonly int $trackId;

    public function __construct(array $response/*, array $request = []*/)
    {
        $this->trackId = (int) ($response['RECEPCIONDTE']['TRACKID'] ?? 0);
    }

    /**
     * Entrega el Track ID asignado por el SII al recibir el envío.
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
