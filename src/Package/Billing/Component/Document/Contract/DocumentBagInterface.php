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

use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use Derafu\Lib\Core\Support\Store\Contract\DataContainerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;
use DOMDocument;
use stdClass;

/**
 * Interfaz para el contenedor de un documento.
 */
interface DocumentBagInterface
{
    /**
     * Asignar los datos de entrada del documento.
     *
     * Si los datos no son string se serializan como JSON.
     *
     * @param string|array|stdClass|null $inputData
     * @return static
     */
    public function setInputData(string|array|stdClass|null $inputData): static;

    /**
     * Obtiene los datos de entrada del documento.
     *
     * @return string|null
     */
    public function getInputData(): ?string;

    /**
     * Asigna los datos procesados (parseados) del documento.
     *
     * @param array|null $parsedData
     * @return static
     */
    public function setParsedData(?array $parsedData): static;

    /**
     * Obtiene los datos procesados (parseados) del documento.
     *
     * @return array|null
     */
    public function getParsedData(): ?array;

    /**
     * Asigna los datos normalizados del documento.
     *
     * @param array|null $normalizedData
     * @return static
     */
    public function setNormalizedData(?array $normalizedData): static;

    /**
     * Obtiene los datos normalizados del documento.
     *
     * @return array|null
     */
    public function getNormalizedData(): ?array;

    /**
     * Asigna las opciones del documento.
     *
     * @param array|DataContainerInterface|null $options
     * @return static
     */
    public function setOptions(array|DataContainerInterface|null $options): static;

    /**
     * Obtiene las opciones del documento.
     *
     * @return DataContainerInterface|null
     */
    public function getOptions(): ?DataContainerInterface;

    /**
     * Obtiene las opciones del procesador (parser) del documento.
     *
     * @return array
     */
    public function getParserOptions(): array;

    /**
     * Obtiene las opciones del constructor del documento.
     *
     * @return array
     */
    public function getBuilderOptions(): array;

    /**
     * Obtiene las opciones del normalizador del documento.
     *
     * @return array
     */
    public function getNormalizerOptions(): array;

    /**
     * Obtiene las opciones del sanitizador del documento.
     *
     * @return array
     */
    public function getSanitizerOptions(): array;

    /**
     * Obtiene las opciones del validador del documento.
     *
     * @return array
     */
    public function getValidatorOptions(): array;

    /**
     * Obtiene las opciones del renderizador del documento.
     *
     * @return array
     */
    public function getRendererOptions(): array;

    /**
     * Asigna el documento XML.
     *
     * @param XmlInterface|null $document
     * @return static
     */
    public function setXmlDocument(?XmlInterface $xml): static;

    /**
     * Obtiene el documento XML.
     *
     * @return DOMDocument&XmlInterface|null
     */
    public function getXmlDocument(): ?XmlInterface;

    /**
     * Asigna el CAF para timbrar el documento.
     *
     * @param CafInterface|null $caf
     * @return static
     */
    public function setCaf(?CafInterface $caf): static;

    /**
     * Obtiene el CAF para timbrar el documento.
     *
     * @return CafInterface|null
     */
    public function getCaf(): ?CafInterface;

    /**
     * Asigna el certificado para firmar el documento.
     *
     * @param CertificateInterface|null $certificate
     * @return static
     */
    public function setCertificate(?CertificateInterface $certificate): static;

    /**
     * Obtiene el certificado para firmar el documento.
     *
     * @return CertificateInterface|null
     */
    public function getCertificate(): ?CertificateInterface;

    /**
     * Asigna el documento tributario electrónico.
     *
     * @param DocumentInterface|null $document
     * @return static
     */
    public function setDocument(?DocumentInterface $document): static;

    /**
     * Obtiene el documento tributario electrónico.
     *
     * @return DocumentInterface|null
     */
    public function getDocument(): ?DocumentInterface;

    /**
     * Asigna el tipo de documento tributario electrónico.
     *
     * @param TipoDocumentoInterface|null $document
     * @return static
     */
    public function setDocumentType(?TipoDocumentoInterface $documentType): static;

    /**
     * @see DocumentBagInterface::setDocumentType()
     */
    public function setTipoDocumento(?TipoDocumentoInterface $documentType): static;

    /**
     * Obtiene el tipo de documento tributario electrónico.
     *
     * @return TipoDocumentoInterface|null
     */
    public function getDocumentType(): ?TipoDocumentoInterface;

    /**
     * @see DocumentBagInterface::getDocumentType()
     */
    public function getTipoDocumento(): ?TipoDocumentoInterface;

    /**
     * Obtiene el código numérico del documento tributario electrónico.
     *
     * @return integer|null
     */
    public function getDocumentTypeId(): ?int;

    /**
     * @see DocumentBagInterface::getDocumentTypeId()
     */
    public function getCodigoTipoDocumento(): ?int;

    /**
     * Asigna el emisor del documento.
     *
     * @param EmisorInterface|null $emisor
     * @return static
     */
    public function setEmisor(?EmisorInterface $emisor): static;

    /**
     * Obtiene el emisor del documento.
     *
     * @return EmisorInterface|null
     */
    public function getEmisor(): ?EmisorInterface;

    /**
     * Asigna el receptor del documento.
     *
     * @param ReceptorInterface|null $receptor
     * @return static
     */
    public function setReceptor(?ReceptorInterface $receptor): static;

    /**
     * Obtiene el receptor del documento.
     *
     * @return ReceptorInterface|null
     */
    public function getReceptor(): ?ReceptorInterface;

    /**
     * Asigna el timbre del documento.
     *
     * Este es el nodo TED del documento.
     *
     * @param array|null $timbre
     * @return static
     */
    public function setTimbre(?array $timbre): static;

    /**
     * Obtiene el timbre del documento.
     *
     * Este es el nodo TED del documento.
     *
     * @return array|null
     */
    public function getTimbre(): ?array;

    /**
     * Obtiene los datos del documento.
     *
     * Los datos se arman en un nodo DTE con los datos normalizados.
     * Si está disponible además se agrega el timbre.
     *
     * Estos datos no incluyen la firma electrónica.
     *
     * @return array|null
     */
    public function getData(): ?array;

    /**
     * Entrega el ID del documento.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Retorna una nueva bolsa con los datos del DTE e incluye un archivo CAF.
     *
     * Las bolsas no se pueden reutilizar una vez se normalizaron, porque ya se
     * generó el DTE. Este método sirve para agregar a una bolsa sin CAF el CAF
     * y tener una nueva bolsa sin el DTE para volver a construir y timbrar.
     *
     * @param CafInterface $caf
     * @return DocumentBagInterface
     */
    public function withCaf(CafInterface $caf): DocumentBagInterface;

    /**
     * Retorna una nueva bolsa con los datos del DTE e incluye un certificado.
     *
     * Las bolsas no se pueden reutilizar una vez se normalizaron, porque ya se
     * generó el DTE. Este método sirve para agregar a una bolsa sin certificado
     * digital el certificado y tener una nueva bolsa sin el DTE para volver a
     * construir y firmar.
     *
     * @param CertificateInterface $certificate
     * @return DocumentBagInterface
     */
    public function withCertificate(
        CertificateInterface $certificate
    ): DocumentBagInterface;

    /**
     * Entrega el alias del tipo de documento que contiene la bolsa.
     *
     * @return string
     */
    public function getAlias(): string;
}
