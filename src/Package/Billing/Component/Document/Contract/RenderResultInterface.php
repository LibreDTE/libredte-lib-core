<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use JsonSerializable;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\RendererException;
use Stringable;

/**
 * Interfaz para la clase que representa el resultado de renderizar un
 * documento (uno o más archivos generados, ej. el PDF y/o el HTML de un
 * DTE).
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
     * @throws RendererException Si no existe un archivo renderizado con ese
     * `label`.
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
