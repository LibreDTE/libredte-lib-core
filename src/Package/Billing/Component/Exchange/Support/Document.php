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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Support;

use Derafu\Container\Bag;
use Derafu\Container\Contract\BagInterface;
use Derafu\Support\Str;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\AttachmentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;

/**
 * Clase que representa un documento que se enviará en un sobre.
 *
 * Contiene el documento y los metadatos de dicho documento.
 */
class Document implements DocumentInterface
{
    /**
     * Tipo del documento.
     *
     * @var DocumentType
     */
    private DocumentType $type;

    /**
     * Datos del documento que se está intercambiando.
     *
     * Típicamente será el XML de un documento tributario electrónico (DTE).
     *
     * @var string
     */
    private string $content;

    /**
     * Listado de archivos adjuntos que se enviarán junto al documento.
     *
     * @var AttachmentInterface[]
     */
    private array $attachments;

    /**
     * Metadatos del documento.
     *
     * Estos metadatos están directamente relacionados con el documento que se
     * está intercambiando y el tipo de transporte que se esté usando para dicho
     * intercambio.
     *
     * @var BagInterface
     */
    private BagInterface $metadata;

    /**
     * Constructor del documento que se está intercambiando.
     *
     * @param string $content
     * @param AttachmentInterface[] $attachments
     * @param DocumentType $type
     * @param BagInterface|array $metadata
     */
    public function __construct(
        string $content = '',
        array $attachments = [],
        DocumentType $type = DocumentType::B2B,
        BagInterface|array $metadata = []
    ) {
        $this->content = $content;
        $this->attachments = $attachments;
        $this->type = $type;
        $this->setMetadata($metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): DocumentType
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getID(): string
    {
        $id = $this->metadata->get('id');

        if ($id !== null) {
            return $id;
        }

        $id = Str::uuid4();

        $this->metadata->set('id', $id);

        return $id;
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * {@inheritDoc}
     */
    public function getContentSize(): int
    {
        return strlen($this->content);
    }

    /**
     * {@inheritDoc}
     */
    public function addAttachment(AttachmentInterface $attachment): static
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * {@inheritDoc}
     */
    public function setMetadata(BagInterface|array $metadata): static
    {
        $this->metadata = is_array($metadata)
            ? new Bag($metadata)
            : $metadata
        ;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addMetadata(string $key, mixed $value): static
    {
        $this->metadata->set($key, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata(): BagInterface
    {
        return $this->metadata;
    }

    /**
     * {@inheritDoc}
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
    public function toArray(): array
    {
        return [
            'id' => $this->getID(),
            'type' => $this->type->value,
            'content' => $this->content,
            'attachments' => array_map(
                fn (AttachmentInterface $attachment) => $attachment->toArray(),
                $this->attachments,
            ),
            'metadata' => $this->metadata->all(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getID(),
            'type' => $this->type->value,
            'content' => base64_encode($this->content),
            'attachments' => array_map(
                fn (AttachmentInterface $attachment) => $attachment->jsonSerialize(),
                $this->attachments,
            ),
            'metadata' => $this->metadata->all(),
        ];
    }
}
