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
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\EstadoEnvioSii;

/**
 * Interfaz para la respuesta del SII a la consulta del estado de un envío.
 *
 * Es el mínimo común de las consultas de estado de DTE y de boletas. Cada tipo
 * de envío puede extenderla con lo que le es propio.
 */
interface SiiCheckXmlDocumentSentStatusResponseInterface extends JsonSerializable
{
    /**
     * Entrega el Track ID del envío consultado.
     *
     * @return int
     */
    public function getTrackId(): int;

    /**
     * Devuelve el estado del envío informado por el SII.
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Devuelve la descripción del estado del envío.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Devuelve el mensaje del estado, con el formato: <estado> - <descripción>.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Devuelve el estado de revisión del envío.
     *
     * @return string
     */
    public function getReviewStatus(): string;

    /**
     * Devuelve el detalle de la revisión del envío.
     *
     * @return string|null El detalle de la revisión o `null` si no existe.
     */
    public function getReviewDetail(): ?string;

    /**
     * Devuelve el estado del envío normalizado como enum.
     *
     * @return EstadoEnvioSii|null `null` cuando el SII aún no ha procesado
     * definitivamente el envío o el estado no se reconoce.
     */
    public function getEstadoEnvioSii(): ?EstadoEnvioSii;

    /**
     * Obtiene los datos normalizados de la respuesta.
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * Entrega los datos de la respuesta como un arreglo.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
