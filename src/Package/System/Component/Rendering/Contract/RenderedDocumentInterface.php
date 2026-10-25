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

namespace libredte\lib\Core\Package\System\Component\Rendering\Contract;

use JsonSerializable;

/**
 * Interfaz para la clase que representa un archivo generado por un
 * renderizador (ej. el PDF o el HTML de un DTE, de un libro tributario, o de
 * cualquier otro contenido renderizado).
 */
interface RenderedDocumentInterface extends JsonSerializable
{
    /**
     * Obtiene los datos binarios (o de texto) del archivo renderizado.
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Obtiene el tipo MIME del archivo renderizado (ej. `application/pdf`).
     *
     * @return string
     */
    public function getMimeType(): string;

    /**
     * Obtiene el nombre sugerido para el archivo renderizado.
     *
     * @return string|null
     */
    public function getFilename(): ?string;

    /**
     * Obtiene el identificador de la presentación con la que se generó este
     * archivo (ej. `tributaria`, `cedible`).
     *
     * Es un identificador de texto libre, no atado a un enum específico: lo
     * asigna quien construye el resultado del renderizado (ej. el worker),
     * para que esta clase se pueda reutilizar fuera del contexto de
     * documentos tributarios.
     *
     * @return string|null
     */
    public function getLabel(): ?string;

    /**
     * Obtiene la cantidad de copias que representa este archivo renderizado.
     *
     * El contenido no se duplica en memoria por tener más de una copia: es
     * el mismo archivo, pensado para usarse (ej. imprimirse) esa cantidad de
     * veces. Ver `RenderResultInterface::toArray()`/`jsonSerialize()` para
     * la única expansión real a múltiples entradas.
     *
     * @return int
     */
    public function getCopies(): int;

    /**
     * Obtiene un arreglo con todos los atributos del archivo renderizado.
     *
     * El contenido se entrega tal cual (crudo, sin codificar), consistente
     * con el resto de la biblioteca: la codificación a base64 para
     * serialización JSON ocurre solo en `jsonSerialize()`.
     *
     * @return array{
     *     content: string,
     *     mimeType: string,
     *     filename: string|null,
     *     label: string|null,
     *     copies: int
     * }
     */
    public function toArray(): array;
}
