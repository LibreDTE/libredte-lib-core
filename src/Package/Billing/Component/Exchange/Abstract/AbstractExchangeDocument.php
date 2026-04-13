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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Abstract;

use Derafu\Xml\Contract\XmlDocumentInterface;
use JsonSerializable;

/**
 * Clase abstracta (base) de los documentos de respuesta al intercambio de DTE.
 *
 * El documento es una vista sobre el `XmlDocumentInterface` que lo contiene.
 */
abstract class AbstractExchangeDocument implements JsonSerializable
{
    /**
     * Constructor del documento de respuesta.
     *
     * @param XmlDocumentInterface $xmlDocument Instancia del documento XML.
     */
    public function __construct(
        private readonly XmlDocumentInterface $xmlDocument
    ) {
    }

    /**
     * Entrega el documento XML del documento de respuesta.
     *
     * @return XmlDocumentInterface
     */
    public function getXmlDocument(): XmlDocumentInterface
    {
        return $this->xmlDocument;
    }

    /**
     * Entrega el XML del documento de respuesta en formato ISO-8859-1.
     *
     * @return string
     */
    public function getXml(): string
    {
        return $this->getXmlDocument()->setEncoding('ISO-8859-1')->saveXml();
    }

    /**
     * Entrega el ID del nodo principal del documento para firmar.
     *
     * @return string
     */
    abstract public function getId(): string;

    /**
     * Entrega el nombre del archivo XSD para validación de esquema.
     *
     * @return string
     */
    abstract public function getSchema(): string;

    /**
     * Entrega el namespace de la firma electrónica.
     *
     * Retorna `null` para que la firma use el namespace xmldsig estándar
     * `http://www.w3.org/2000/09/xmldsig#`, tal como lo exigen los XSD de
     * `EnvioRecibos` y `RespuestaDTE`.
     *
     * @return string|null
     */
    public function getSignatureNamespace(): ?string
    {
        return null;
    }

    /**
     * Entrega el documento como arreglo.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getXmlDocument()->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
