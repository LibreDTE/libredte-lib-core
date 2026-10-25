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
use libredte\lib\Core\Package\System\Component\Rendering\Exception\RenderResultException;
use Stringable;

/**
 * Interfaz para la clase que representa el resultado de un renderizado (uno
 * o más archivos generados, ej. el PDF y/o el HTML de un DTE, de un libro
 * tributario, o de cualquier otro contenido renderizado).
 */
interface RenderResultInterface extends Stringable, JsonSerializable
{
    /**
     * Obtiene los archivos generados por el renderizado.
     *
     * Uno por presentación (`label`) distinta solicitada, nunca duplicado
     * por su cantidad de copias. Ver `RenderedDocumentInterface::getCopies()`.
     *
     * @return RenderedDocumentInterface[]
     */
    public function getRenderings(): array;

    /**
     * Indica si el renderizado generó un archivo con el `label` indicado.
     *
     * Puede ser `false` tanto porque nunca se solicitó esa presentación como
     * porque se omitió en silencio (ej. copia cedible solicitada para un
     * tipo de documento que no la admite).
     *
     * @param string $label
     * @return bool
     */
    public function hasRendering(string $label): bool;

    /**
     * Obtiene el archivo generado con el `label` indicado.
     *
     * @param string $label
     * @return RenderedDocumentInterface
     * @throws RenderResultException Si no existe un archivo renderizado con
     * ese `label`.
     */
    public function getRendering(string $label): RenderedDocumentInterface;

    /**
     * Entrega el contenido del renderizado como string.
     *
     * Solo funciona cuando el resultado contiene exactamente un archivo
     * renderizado. Si el resultado contiene más de uno, no hay una forma no
     * ambigua de reducirlo a un solo string, así que se lanza una excepción en
     * vez de entregar solo el primero en silencio.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Obtiene un arreglo con todos los archivos generados por el
     * renderizado.
     *
     * A diferencia de `getRenderings()`, esta representación sí se expande
     * por cantidad de copias: cada archivo con `copies > 1` aparece como
     * múltiples entradas (mismo `content`/`mimeType`, calculados una sola
     * vez), cada una con un `copyNumber` (desde 1) y, si hay más de una
     * copia, el `filename` ajustado para no colisionar entre copias.
     *
     * @return array
     */
    public function toArray(): array;
}
