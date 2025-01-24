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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Support;

use Derafu\Lib\Core\Helper\Str;
use Derafu\Lib\Core\Support\Store\Bag;
use Derafu\Lib\Core\Support\Store\Contract\BagInterface;
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
}
