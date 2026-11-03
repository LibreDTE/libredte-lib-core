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

namespace libredte\lib\Core\Package\Billing\Component\Book\Abstract;

use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;
use Derafu\Value\ValueType;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\XPathReader;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;

/**
 * Clase abstracta (base) de la representación de un libro tributario
 * electrónico.
 *
 * El libro es una vista sobre el `XmlDocumentInterface` que lo contiene; todos
 * los datos se derivan del XML mediante consultas XPath.
 */
abstract class AbstractBook implements BookInterface
{
    /**
     * Tipo de libro.
     */
    protected TipoLibro $tipo;

    /**
     * Constructor del libro tributario.
     *
     * @param XmlDocumentInterface $xmlDocument Instancia del documento XML
     * asociado al libro.
     */
    public function __construct(
        private readonly XmlDocumentInterface $xmlDocument
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getTipo(): TipoLibro
    {
        if (!isset($this->tipo)) {
            throw new LogicException([
                'The book type is not defined in class "{class}".',
                'class' => static::class,
            ]);
        }

        return $this->tipo;
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocument(): XmlDocumentInterface
    {
        return $this->xmlDocument;
    }

    /**
     * Obtiene un lector del documento XML.
     *
     * @return XPathReader
     */
    private function reader(): XPathReader
    {
        return $this->xmlDocument->getReader(source: 'book XML');
    }

    /**
     * {@inheritDoc}
     */
    public function getSignatureNamespace(): ?string
    {
        // Se usa el namespace por defecto de la firma electrónica.
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function saveXml(): string
    {
        return $this->getXmlDocument()->setEncoding('ISO-8859-1')->saveXml();
    }

    /**
     * {@inheritDoc}
     */
    public function getXml(): string
    {
        return $this->getXmlDocument()->setEncoding('ISO-8859-1')->getXml();
    }

    /**
     * {@inheritDoc}
     *
     * Por defecto busca el atributo `ID` en el elemento `EnvioLibro`. Las
     * subclases que usen un elemento diferente (p. ej. `DocumentoConsumoFolios`)
     * deben sobrescribir este método.
     */
    public function getId(): string
    {
        return $this->reader()->getString('//EnvioLibro/@ID', '');
    }

    /**
     * {@inheritDoc}
     */
    public function getCaratula(): array
    {
        return $this->reader()->getMap('//Caratula') ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getResumen(): array
    {
        return $this->reader()->getListOf(
            $this->getTipo()->getXpathResumen(),
            ValueType::map()
        ) ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getDetalle(): array
    {
        return $this->reader()->getListOf('//Detalle', ValueType::map()) ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function countDetalle(): int
    {
        return count($this->getDetalle());
    }

    /**
     * {@inheritDoc}
     */
    public function isSimplificado(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchema(): string
    {
        $schema = $this->getXmlDocument()->getSchema();

        if ($schema === null) {
            throw new BookException(
                'The book XML does not declare the xsi:schemaLocation attribute.'
            );
        }

        return $schema;
    }

    /**
     * {@inheritDoc}
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
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            ...$this->toArray(),
            'xml' => base64_encode($this->saveXml()),
        ];
    }
}
