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

namespace libredte\lib\Core\Package\Billing\Component\Document\Support;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Trait\OptionsAwareTrait;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentEnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SobreEnvioInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DispatcherException;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;

/**
 * Contenedor de datos del sobre de documentos tributarios electrónicos.
 *
 * Permite "mover" un sobre con varios documentos, junto a otros datos
 * asociados, por métodos de manera sencilla y, sobre todo, extensible.
 */
class DocumentEnvelope implements DocumentEnvelopeInterface
{
    use OptionsAwareTrait;

    /**
     * Reglas de esquema de las opciones del documento.
     *
     * Acá solo se indicarán los índices que deben pueden existir en las
     * opciones. No se define el esquema de cada opción pues cada clase que
     * utilice estas opciones deberá resolver y validar sus propias opciones.
     *
     * @var array
     */
    protected array $optionsSchema = [
        'dispatcher' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * Tipo del sobre de documentos.
     *
     * @var TipoSobre|null
     */
    private ?TipoSobre $tipo_sobre = null;

    /**
     * Entidad del sobre del envío de documentos.
     *
     * @var SobreEnvioInterface|null
     */
    private ?SobreEnvioInterface $sobre_envio = null;

    /**
     * Instancia del documento XML asociado al sobre de documentos tributarios.
     *
     * @var XmlDocumentInterface|null
     */
    private ?XmlDocumentInterface $xmlDocument = null;

    /**
     * Lista de bolsas de documentos tributarios que este sobre contendrá.
     *
     * @var DocumentBagInterface[]|null
     */
    private ?array $documents = null;

    /**
     * Emisor del sobre de documentos.
     *
     * @var EmisorInterface|null
     */
    private ?EmisorInterface $emisor = null;

    /**
     * Mandatario del sobre de documentos.
     *
     * @var MandatarioInterface|null
     */
    private ?MandatarioInterface $mandatario = null;

    /**
     * Receptor del sobre de documentos.
     *
     * @var ReceptorInterface|null
     */
    private ?ReceptorInterface $receptor = null;

    /**
     * Certificado digital (firma electrónica) para la firma del sobre.
     *
     * @var CertificateInterface|null
     */
    private ?CertificateInterface $certificate = null;

    /**
     * Datos de la carátula del sobre.
     *
     * @var array|null
     */
    private ?array $caratula = null;

    /**
     * {@inheritDoc}
     */
    public function getOptionsSchema(): array
    {
        return $this->optionsSchema;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return 'LibreDTE_SetDoc';
    }

    /**
     * {@inheritDoc}
     */
    public function getTipoSobre(): TipoSobre
    {
        return $this->tipo_sobre;
    }

    /**
     * {@inheritDoc}
     */
    public function setSobreEnvio(SobreEnvioInterface $sobre_envio): static
    {
        $this->sobre_envio = $sobre_envio;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSobreEnvio(): ?SobreEnvioInterface
    {
        return $this->sobre_envio;
    }

    /**
     * {@inheritDoc}
     */
    public function setXmlDocument(?XmlDocumentInterface $xmlDocument): static
    {
        $this->xmlDocument = $xmlDocument;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocument(): ?XmlDocumentInterface
    {
        return $this->xmlDocument;
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
    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    /**
     * {@inheritDoc}
     */
    public function addDocument(?DocumentBagInterface $document): static
    {
        // Si ya se agregó la carátula no se permite agregar nuevos documentos.
        if (isset($this->caratula)) {
            throw new DispatcherException(
                'Documents cannot be added to the envelope after the cover has been generated.'
            );
        }

        // Si no hay documentos previamente se inicializa un arreglo vacío.
        if ($this->documents === null) {
            $this->documents = [];
        }

        // Si no está definido el tipo de sobre el primer documento que se
        // agregue lo definirá.
        if (!isset($this->tipo_sobre)) {
            $this->tipo_sobre = $document->getTipoDocumento()->getTipoSobre();
        }

        // Validar que el tipo de documento se pueda agregar al sobre.
        if ($document->getTipoDocumento()->getTipoSobre() !== $this->tipo_sobre) {
            throw new DispatcherException([
                'Document type {documentType} cannot be added to an envelope of type {envelopeType}.',
                'documentType' => $document->getTipoDocumento()->getNombre(),
                'envelopeType' => $this->getTipoSobre()->getNombre(),
            ]);
        }

        // Validar que no se haya llenado la lista de documentos permitida.
        $maximoDocumentos = $this->getTipoSobre()->getMaxDocumentos();
        if (isset($this->documentos[$maximoDocumentos - 1])) {
            throw new DispatcherException([
                'Cannot add new documents to envelope {envelope}; the limit is {max} documents per envelope.',
                'envelope' => $this->getTipoSobre()->getNombre(),
                'max' => $maximoDocumentos,
            ]);
        }

        // Agregar el documento al sobre.
        $this->documents[] = $document;

        // Entregar la misma instancia para encadenamiento.
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDispatcherOptions(): array
    {
        return $this->getOptions()->getReader()->requireArray('dispatcher');
    }

    /**
     * {@inheritDoc}
     */
    public function setEmisor(?EmisorInterface $emisor): static
    {
        $this->emisor = $emisor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmisor(): ?EmisorInterface
    {
        return $this->emisor;
    }

    /**
     * {@inheritDoc}
     */
    public function setMandatario(?MandatarioInterface $mandatario): static
    {
        $this->mandatario = $mandatario;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMandatario(): ?MandatarioInterface
    {
        return $this->mandatario;
    }

    /**
     * {@inheritDoc}
     */
    public function setReceptor(?ReceptorInterface $receptor): static
    {
        $this->receptor = $receptor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getReceptor(): ?ReceptorInterface
    {
        return $this->receptor;
    }

    /**
     * {@inheritDoc}
     */
    public function setCertificate(?CertificateInterface $certificate): static
    {
        $this->certificate = $certificate;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCertificate(): ?CertificateInterface
    {
        return $this->certificate;
    }

    /**
     * {@inheritDoc}
     */
    public function setCaratula(array $caratula): static
    {
        $this->caratula = $caratula;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCaratula(): ?array
    {
        return $this->caratula;
    }

    /**
     * {@inheritDoc}
     */
    public function withCertificate(
        CertificateInterface $certificate
    ): DocumentEnvelopeInterface {
        $class = static::class;

        $envelope = new $class();

        $envelope->setXmlDocument($this->getXmlDocument());
        $envelope->setDocuments($this->getDocuments());
        $envelope->setOptions($this->getOptions());
        $envelope->setEmisor($this->getEmisor());
        $envelope->setMandatario($this->getMandatario());
        $envelope->setReceptor($this->getReceptor());
        $envelope->setCertificate($this->getCertificate());

        return $envelope;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>&array{
     *     xml: string
     * }
     */
    public function toArray(): array
    {
        return [
            'tag' => $this->getTipoSobre()->getTagXml(),
            'xml' => $this->getXmlDocument()->setEncoding('ISO-8859-1')->saveXml(),
            'documents' => array_map(
                fn (DocumentBagInterface $document) => $document->toArray(),
                $this->documents ?? []
            ),
            'caratula' => $this->caratula,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        $array['xml'] = base64_encode($array['xml']);
        $array['documents'] = array_map(
            fn (DocumentBagInterface $document) => $document->jsonSerialize(),
            $this->documents ?? []
        );

        return $array;
    }
}
