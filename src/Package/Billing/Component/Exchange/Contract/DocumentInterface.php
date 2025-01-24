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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Contract;

use Derafu\Lib\Core\Support\Store\Contract\BagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;

/**
 * Interfaz para la clase que representa un documento que se enviará en un
 * sobre.
 */
interface DocumentInterface
{
    /**
     * Obtiene el tipo del documento.
     *
     * @return DocumentType
     */
    public function getType(): DocumentType;

    /**
     * Obtiene el identificador único del documento.
     *
     * @return string
     */
    public function getID(): string;

    /**
     * Obtiene el contenido del documento que se está intercambiando.
     *
     * Típicamente será el XML de un documento tributario electrónico (DTE).
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Obtiene el tamaño del contenido en bytes.
     *
     * @return int
     */
    public function getContentSize(): int;

    /**
     * Agrega un archivo adjunto al documento.
     *
     * @param AttachmentInterface $attachment
     * @return static
     */
    public function addAttachment(AttachmentInterface $attachment): static;

    /**
     * Obtiene el listado de archivos adjuntos al documento.
     *
     * @return AttachmentInterface[]
     */
    public function getAttachments(): array;

    /**
     * Asigna los metadatos del documento.
     *
     * @param BagInterface|array $metadata
     * @return static
     */
    public function setMetadata(BagInterface|array $metadata): static;

    /**
     * Agrega una clave específica a los metadatos.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addMetadata(string $key, mixed $value): static;

    /**
     * Obtiene los metadatos del documento.
     *
     * Estos son útiles para el proceso de intercambio y/o el medio de
     * transporte usado para el intercambio.
     *
     * @return BagInterface
     */
    public function getMetadata(): BagInterface;
}
