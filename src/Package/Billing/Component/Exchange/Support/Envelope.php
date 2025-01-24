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

use DateTimeImmutable;
use DateTimeInterface;
use Derafu\Lib\Core\Helper\Str;
use Derafu\Lib\Core\Support\Store\Bag;
use Derafu\Lib\Core\Support\Store\Contract\BagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\ProcessType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;

/**
 * Clase que representa un sobre con documentos.
 *
 * Los sobres típicamente tendrán solo un documento. Y si llegasen a tener más
 * de un documento serían siempre del mismo remitente y al mismo destinatario.
 */
class Envelope implements EnvelopeInterface
{
    /**
     * Remitente del sobre.
     *
     * @var SenderInterface
     */
    private SenderInterface $sender;

    /**
     * Receptor del sobre.
     *
     * @var ReceiverInterface
     */
    private ReceiverInterface $receiver;

    /**
     * Tipo de documento que el sobre tiene.
     *
     * @var DocumentType
     */
    private DocumentType $documentType;

    /**
     * Tipo de proceso asociado al intercambio de documentos del sobre.
     *
     * @var ProcessType
     */
    private ProcessType $process;

    /**
     * Fecha y hora de creación del sobre.
     *
     * @var DateTimeInterface
     */
    private DateTimeInterface $creationDateAndTime;

    /**
     * Identificador único del mensaje comercial encapsulado.
     *
     * @var string
     */
    private string $businessMessageID;

    /**
     * Identificador del mensaje original al que se responde.
     *
     * @var string|null
     */
    private ?string $originalBusinessMessageID;

    /**
     * Documentos que están en el sobre.
     *
     * Normalmente un sobre tendría solo un documento. Sin embargo, el
     * componente de intercambio soporta múltiples documentos en un sobre. Cómo
     * serán manejados los múltiples documentos de un sobre es una
     * responsabilidad de cada estrategia de intercambio.
     *
     * @var DocumentInterface[]
     */
    private array $documents;

    /**
     * Metadatos del sobre.
     *
     * Estos metadatos están directamente relacionados con el sobre que se está
     * intercambiando y el tipo de transporte que se esté usando para dicho
     * intercambio.
     *
     * @var BagInterface
     */
    private BagInterface $metadata;

    /**
     * Constructor del sobre.
     *
     * @param SenderInterface $sender
     * @param ReceiverInterface $receiver
     * @param DocumentType $documentType
     * @param ProcessType $process
     * @param string|null $businessMessageID
     * @param string|null $originalBusinessMessageID
     * @param DateTimeInterface|null $creationDateAndTime
     * @param array $documents
     * @param BagInterface|array $metadata
     */
    public function __construct(
        SenderInterface $sender,
        ReceiverInterface $receiver,
        DocumentType $documentType = DocumentType::B2B,
        ProcessType $process = ProcessType::BILLING,
        ?string $businessMessageID = null,
        ?string $originalBusinessMessageID = null,
        ?DateTimeInterface $creationDateAndTime = null,
        array $documents = [],
        BagInterface|array $metadata = []
    ) {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->documentType = $documentType;
        $this->process = $process;
        $this->businessMessageID = $businessMessageID ?? Str::uuid4();
        $this->originalBusinessMessageID = $originalBusinessMessageID;
        $this->creationDateAndTime = $creationDateAndTime
            ?? new DateTimeImmutable()
        ;
        $this->setDocuments($documents);
        $this->setMetadata($metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function getSender(): SenderInterface
    {
        return $this->sender;
    }

    /**
     * {@inheritDoc}
     */
    public function getReceiver(): ReceiverInterface
    {
        return $this->receiver;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentType(): DocumentType
    {
        return $this->documentType;
    }

    /**
     * {@inheritDoc}
     */
    public function getProcess(): ProcessType
    {
        return $this->process;
    }

    /**
     * {@inheritDoc}
     */
    public function getBusinessMessageID(): string
    {
        return $this->businessMessageID;
    }

    /**
     * {@inheritDoc}
     */
    public function getOriginalBusinessMessageID(): ?string
    {
        return $this->originalBusinessMessageID;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreationDateAndTime(): DateTimeInterface
    {
        return $this->creationDateAndTime;
    }

    /**
     * {@inheritDoc}
     */
    public function setDocuments(array $documents): static
    {
        foreach ($documents as $document) {
            $this->addDocument($document);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addDocument(DocumentInterface $document): static
    {
        if ($document->getType() !== $this->getDocumentType()) {
            throw new ExchangeException(sprintf(
                'El tipo del documento %s no coincide con los tipos de documento que el sobre puede contener %s.',
                $document->getType()->getID(),
                $this->getDocumentType()->getID()
            ));
        }

        $this->documents[] = $document;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * {@inheritDoc}
     */
    public function countDocuments(): int
    {
        return count($this->documents);
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
