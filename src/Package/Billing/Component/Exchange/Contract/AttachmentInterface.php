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
 * Interfaz para la clase que representa un archivo adjunto de un documento.
 */
interface AttachmentInterface extends JsonSerializable
{
    /**
     * Obtiene el nombre del archivo.
     *
     * @return string|null
     */
    public function getFilename(): ?string;

    /**
     * Obtiene los datos del archivo.
     *
     * @return string
     */
    public function getBody(): string;

    /**
     * Obtiene el tipo del archivo.
     *
     * @return string
     */
    public function getContentType(): string;

    /**
     * Obtiene el tamaño del archivo.
     *
     * @return int
     */
    public function getSize(): int;

    /**
     * Obtiene un arreglo con todos los atributos del archivo.
     *
     * @return array{
     *     data: string,
     *     name: string|null,
     *     type: string,
     *     size: int
     * }
     */
    public function toArray(): array;
}
