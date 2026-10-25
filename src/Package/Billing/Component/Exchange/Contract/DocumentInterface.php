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

use Derafu\Container\Contract\BagInterface;
use JsonSerializable;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;

/**
 * Interfaz para la clase que representa un documento que se enviará en un
 * sobre.
 */
interface DocumentInterface extends JsonSerializable
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

    /**
     * Convierte el documento a un array.
     *
     * @return array<array-key, mixed>&array{
     *     id: string,
     *     content: string,
     *     attachments: array<array-key, array{
     *         data: string,
     *         name: string|null,
     *         type: string,
     *         size: int
     *     }>,
     *     metadata: array<array-key, mixed>
     * }
     */
    public function toArray(): array;
}
