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

/**
 * Interfaz para la clase que representa un archivo generado por un
 * renderizador de documentos (ej. el PDF o el HTML de un DTE).
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
     * @return array
     */
    public function toArray(): array;
}
