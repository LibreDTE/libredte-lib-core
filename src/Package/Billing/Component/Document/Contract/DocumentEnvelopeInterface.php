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

use Derafu\Lib\Core\Common\Contract\OptionsAwareInterface;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;

/**
 * Interfaz para el sobre (contenedor) de documentos tributarios para el proceso
 * de envío al SII e intercambio entre contribuyentes.
 */
interface DocumentEnvelopeInterface extends OptionsAwareInterface
{
    /**
     * Entrega el identificador del sobre de documentos.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Entrega el tipo de sobre de documentos.
     *
     * @return TipoSobre
     */
    public function getTipoSobre(): TipoSobre;

    /**
     * Asigna la entidad del sobre del envío de documentos.
     *
     * @param SobreEnvioInterface $sobre_envio
     * @return static
     */
    public function setSobreEnvio(SobreEnvioInterface $sobre_envio): static;

    /**
     * Obtiene la entidad del sobre del envío de documentos.
     *
     * @return SobreEnvioInterface|null
     */
    public function getSobreEnvio(): ?SobreEnvioInterface;

    /**
     * Asigna el documento XML que representa al sobre de documentos.
     *
     * @param XmlInterface|null $xmlDocument
     * @return static
     */
    public function setXmlDocument(?XmlInterface $xmlDocument): static;

    /**
     * Obtiene el documento XML que representa al sobre de documentos.
     *
     * @return XmlInterface|null
     */
    public function getXmlDocument(): ?XmlInterface;

    /**
     * Asigna todos los documentos del sobre de una vez.
     *
     * @param DocumentBagInterface[] $documents
     * @return static
     */
    public function setDocuments(array $documents): static;

    /**
     * Obtiene todos los documentos del sobre.
     *
     * @return DocumentBagInterface[]|null
     */
    public function getDocuments(): ?array;

    /**
     * Agrega un nuevo documento al sobre de documentos.
     *
     * @param DocumentBagInterface $document
     * @return static
     */
    public function addDocument(DocumentBagInterface $document): static;

    /**
     * Obtiene las opciones del despachador del sobre de documentos al SII.
     *
     * @return array
     */
    public function getDispatcherOptions(): array;

    /**
     * Asigna el emisor del sobre de documentos.
     *
     * @param EmisorInterface|null $emisor
     * @return static
     */
    public function setEmisor(?EmisorInterface $emisor): static;

    /**
     * Obtiene el emisor del sobre de documentos.
     *
     * @return EmisorInterface|null
     */
    public function getEmisor(): ?EmisorInterface;

    /**
     * Asigna el mandatario del emisor (mandante) autorizado en el SII a enviar
     * el sobre con los documentos tributarios.
     *
     * @param MandatarioInterface|null $mandatario
     * @return static
     */
    public function setMandatario(?MandatarioInterface $mandatario): static;

    /**
     * Obtiene el mandatario del emisor (mandante) autorizado en el SII a enviar
     * el sobre con los documentos tributarios.
     *
     * @return MandatarioInterface|null
     */
    public function getMandatario(): ?MandatarioInterface;

    /**
     * Asigna el receptor del sobre de documentos.
     *
     * @param ReceptorInterface|null $receptor
     * @return static
     */
    public function setReceptor(?ReceptorInterface $receptor): static;

    /**
     * Obtiene el receptor del sobre de documentos.
     *
     * @return ReceptorInterface|null
     */
    public function getReceptor(): ?ReceptorInterface;

    /**
     * Asigna el certificado para firmar el sobre de documentos.
     *
     * @param CertificateInterface|null $certificate
     * @return static
     */
    public function setCertificate(?CertificateInterface $certificate): static;

    /**
     * Obtiene el certificado para firmar el sobre de documentos.
     *
     * @return CertificateInterface|null
     */
    public function getCertificate(): ?CertificateInterface;

    /**
     * Retorna una nueva sobre con los datos del sobre actual e incluye un
     * certificado.
     *
     * Los sobres no se pueden reutilizar una vez se crearon, porque ya se
     * generó el XmlDocument. Este método sirve para agregar a un sobre sin
     * certificado digital el certificado y tener un nuevo sobre sin el
     * documento XML del envío del DTE para volver a construir y firmar.
     *
     * @param CertificateInterface $certificate
     * @return DocumentEnvelopeInterface
     */
    public function withCertificate(
        CertificateInterface $certificate
    ): DocumentEnvelopeInterface;

    /**
     * Asigna los datos de la carátula del sobre de documentos.
     *
     * @param array $caratula
     * @return static
     */
    public function setCaratula(array $caratula): static;

    /**
     * Obtiene los datos de la carátula del sobre de documentos.
     *
     * @return array|null
     */
    public function getCaratula(): ?array;
}
