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

use Derafu\Xml\Contract\XmlDocumentInterface;
use JsonSerializable;

/**
 * Interfaz para los documentos de respuesta al intercambio de DTE.
 *
 * El documento es una vista sobre el `XmlDocumentInterface` que lo contiene;
 * todos los datos se derivan del XML. Implementada por `EnvioRecibos` y
 * `RespuestaEnvio`.
 *
 * No confundir con `DocumentInterface` de este mismo namespace, que
 * representa un documento dentro del sobre P2P de intercambio (Peppol/UBL),
 * una entidad completamente distinta.
 */
interface ExchangeDocumentInterface extends JsonSerializable
{
    /**
     * Entrega el documento XML del documento de respuesta.
     *
     * @return XmlDocumentInterface
     */
    public function getXmlDocument(): XmlDocumentInterface;

    /**
     * Genera el XML del documento de respuesta como string incluyendo
     * encabezado.
     *
     * @return string
     * @see XmlDocumentInterface::saveXml()
     */
    public function saveXml(): string;

    /**
     * Genera el XML del documento de respuesta como string sin encabezado ni
     * saltos de línea al inicio y final.
     *
     * @return string
     * @see XmlDocumentInterface::getXml()
     */
    public function getXml(): string;

    /**
     * Entrega el ID del nodo principal del documento para firmar.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Entrega el nombre del archivo XSD para validación de esquema.
     *
     * @return string
     */
    public function getSchema(): string;

    /**
     * Entrega el namespace de la firma electrónica.
     *
     * @return string|null
     */
    public function getSignatureNamespace(): ?string;

    /**
     * Entrega el documento como arreglo.
     *
     * @return array
     */
    public function toArray(): array;
}
