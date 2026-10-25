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

namespace libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Abstract;

use Derafu\Xml\Contract\XmlDocumentInterface;
use JsonSerializable;

/**
 * Clase abstracta (base) de los documentos de cesión electrónica (AEC).
 *
 * El documento es una vista sobre el `XmlDocumentInterface` que lo contiene.
 */
abstract class AbstractOwnershipTransferDocument implements JsonSerializable
{
    /**
     * Constructor del documento de cesión.
     *
     * @param XmlDocumentInterface $xmlDocument Instancia del documento XML.
     */
    public function __construct(
        private readonly XmlDocumentInterface $xmlDocument
    ) {
    }

    /**
     * Entrega el documento XML del documento de cesión.
     *
     * @return XmlDocumentInterface
     */
    public function getXmlDocument(): XmlDocumentInterface
    {
        return $this->xmlDocument;
    }

    /**
     * Entrega el XML del documento de cesión en formato ISO-8859-1, incluyendo
     * la cabecera XML.
     *
     * @return string
     */
    public function saveXml(): string
    {
        return $this->getXmlDocument()->setEncoding('ISO-8859-1')->saveXml();
    }

    /**
     * Entrega el XML del documento de cesión en formato ISO-8859-1, sin la
     * cabecera XML.
     *
     * @return string
     */
    public function getXml(): string
    {
        return $this->getXmlDocument()->setEncoding('ISO-8859-1')->getXml();
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
     * `DTECedido`, `Cesion` y `AEC`.
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
        return [
            ...$this->getXmlDocument()->toArray(),
            'xml' => $this->saveXml(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        $array['xml'] = base64_encode($array['xml']);

        return $array;
    }
}
