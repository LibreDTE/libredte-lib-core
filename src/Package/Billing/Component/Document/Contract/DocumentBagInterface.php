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
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;
use stdClass;

/**
 * Interfaz para el contenedor de un documento.
 */
interface DocumentBagInterface extends OptionsAwareInterface
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
     * Asigna los datos de LibreDTE que están asociados al documento pero no
     * son parte de la estructura oficial que utiliza el SII.
     *
     * @param array|null $libredteData
     * @return static
     */
    public function setLibredteData(?array $libredteData): static;

    /**
     * Obtiene los datos de LibreDTE que están asociados al documento pero no
     * son parte de la estructura oficial que utiliza el SII.
     *
     * @return array|null
     */
    public function getLibredteData(): ?array;

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
     * @param XmlInterface|null $xml
     * @return static
     */
    public function setXmlDocument(?XmlInterface $xml): static;

    /**
     * Obtiene el documento XML.
     *
     * @return XmlInterface|null
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
     * @param TipoDocumentoInterface|null $documentType
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
     * Tampoco se incluyen datos específicos de LibreDTE. Si se necesitan los
     * datos del documento completo, con los datos específicos de LibreDTE, usar
     * el método getDocumentData().
     *
     * @return array|null
     */
    public function getData(): ?array;

    /**
     * Obtiene los datos del documento agregando los datos específicos de
     * LibreDTE.
     *
     * Si se quieren solo los datos del documento, sin los agregados de LibreDTE
     * se deben extrar directamente del DTE.
     *
     * Por otro lado, si se quieren los datos normalizados preparados para firma
     * se debe utilizar getData().
     *
     * @return array|null
     */
    public function getDocumentData(): ?array;

    /**
     * Obtiene los datos extras del documento.
     *
     * Esto entregará todos los datos extras del documento menos aquellos que
     * son parte de los datos que se agregan a la estructura de datos del
     * documento tributario para completar tags entre diferentes tipos de
     * documentos. O sea, todo lo extra, menos el índice `dte`.
     *
     * @return array|null
     */
    public function getDocumentExtra(): ?array;

    /**
     * Obtiene el timbre del DTE.
     *
     * @return string|null
     */
    public function getDocumentStamp(): ?string;

    /**
     * Obtiene los datos de la autorización del emisor para emitir DTE.
     *
     * @return array|null
     */
    public function getDocumentAuth(): ?array;

    /**
     * Entrega el ID del documento.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Asigna el folio numérico al documento.
     *
     * El folio se asignará a los datos normalizados si existen o bien a los
     * datos parseados si aun no se normaliza.
     *
     * @param int $folio
     * @return static
     */
    public function setFolio(int $folio): static;

    /**
     * Obtiene el folio del documento.
     *
     * Cuando el folio es un entero se asume que es un folio oficial del SII.
     *
     * Cuando es un folio alfanumérico (string) se asume que es un folio de una
     * cotización de LibreDTE.
     *
     * Cuando el folio no existe (es `false` o `0`) se entregará `null`.
     *
     * El folio puede provenir del documento normalizado (si existe) o del
     * documento parseado (si aun no se normaliza).
     *
     * @return int|string|null
     */
    public function getFolio(): int|string|null;

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
