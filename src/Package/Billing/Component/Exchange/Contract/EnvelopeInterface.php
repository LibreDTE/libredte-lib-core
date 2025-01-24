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

use DateTimeInterface;
use Derafu\Lib\Core\Support\Store\Contract\BagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\ProcessType;

/**
 * Interfaz para la clase que representa un sobre con documentos.
 */
interface EnvelopeInterface
{
    /**
     * Entrega el remitente del sobre.
     *
     * @return SenderInterface
     */
    public function getSender(): SenderInterface;

    /**
     * Entrega el receptor del sobre.
     *
     * @return ReceiverInterface
     */
    public function getReceiver(): ReceiverInterface;

    /**
     * Obtiene el tipo de documento que el sobre contiene.
     *
     * Todos los documentos deberán ser del mismo tipo.
     *
     * @return DocumentType
     */
    public function getDocumentType(): DocumentType;

    /**
     * Obtiene el proceso comercial o de negocio al que estan asociados los
     * documentos del sobre.
     *
     * @return ProcessType
     */
    public function getProcess(): ProcessType;

    /**
     * Obtiene el identificador único del mensaje comercial encapsulado.
     *
     * @return string
     */
    public function getBusinessMessageID(): string;

    /**
     * Obtiene el identificador del mensaje original al que se responde con los
     * documentos de este sobre.
     *
     * @return string|null
     */
    public function getOriginalBusinessMessageID(): ?string;

    /**
     * Obtiene la fecha y hora de creación del sobre.
     *
     * Para sobres enviados será la fecha y hora de creación en LibreDTE del
     * sobre. Para sobres recibidos se debe tratar de incluir la fecha y hora
     * original que el emisor haya asignado (ej: fecha recepción correo).
     *
     * @return DateTimeInterface
     */
    public function getCreationDateAndTime(): DateTimeInterface;

    /**
     * Asigna los documentos del sobre.
     *
     * @param DocumentInterface[] $documents
     * @return static
     */
    public function setDocuments(array $documents): static;

    /**
     * Agrega un documento al sobre.
     *
     * @param DocumentInterface $document
     * @return static
     */
    public function addDocument(DocumentInterface $document): static;

    /**
     * Obtiene la lista de documentos que hay en el sobre.
     *
     * @return DocumentInterface[]
     */
    public function getDocuments(): array;

    /**
     * Obtiene la cantidad de documentos en el sobre.
     *
     * @return int
     */
    public function countDocuments(): int;

    /**
     * Asigna los metadatos del sobre.
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
     * Obtiene los metadatos del sobre.
     *
     * Estos son útiles para el proceso de intercambio y/o el medio de
     * transporte usado para el intercambio.
     *
     * @return BagInterface
     */
    public function getMetadata(): BagInterface;
}
