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

namespace libredte\lib\Core\Package\Billing\Component\Document\Support;

use Derafu\Lib\Core\Common\Trait\OptionsAwareTrait;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
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
     * @var XmlInterface|null
     */
    private ?XmlInterface $xmlDocument = null;

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
    public function setXmlDocument(?XmlInterface $xmlDocument): static
    {
        $this->xmlDocument = $xmlDocument;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocument(): ?XmlInterface
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
                'No es posible agregar documentos al sobre cuando la carátula ya fue generada.'
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
            throw new DispatcherException(sprintf(
                'El tipo de documento %s no se puede agregar a un sobre de tipo %s.',
                $document->getTipoDocumento()->getNombre(),
                $this->getTipoSobre()->getNombre()
            ));
        }

        // Validar que no se haya llenado la lista de documentos permitida.
        $maximoDocumentos = $this->getTipoSobre()->getMaximoDocumentos();
        if (isset($this->documentos[$maximoDocumentos - 1])) {
            throw new DispatcherException(sprintf(
                'No es posible agregar nuevos documentos al sobre %s, el límite es de %d documentos por sobre.',
                $this->getTipoSobre()->getNombre(),
                $maximoDocumentos
            ));
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
        return (array) $this->getOptions()->get('dispatcher');
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
}
