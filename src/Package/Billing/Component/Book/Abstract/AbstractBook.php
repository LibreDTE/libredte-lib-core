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

use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use LogicException;

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
            throw new LogicException(sprintf(
                'El tipo de libro no está definido en la clase "%s".',
                static::class
            ));
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
        return (string) ($this->getXmlDocument()->query('//EnvioLibro/@ID') ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getCaratula(): array
    {
        return (array) ($this->getXmlDocument()->query('//Caratula') ?? []);
    }

    /**
     * {@inheritDoc}
     */
    public function getResumen(): array
    {
        $resumen = (array) (
            $this->getXmlDocument()->query(
                $this->getTipo()->getXpathResumen()
            ) ?? []
        );

        if (!empty($resumen) && !isset($resumen[0])) {
            $resumen = [$resumen];
        }

        return $resumen;
    }

    /**
     * {@inheritDoc}
     */
    public function getDetalle(): array
    {
        $detalle = (array) ($this->getXmlDocument()->query('//Detalle') ?? []);

        if (!empty($detalle) && !isset($detalle[0])) {
            $detalle = [$detalle];
        }

        return $detalle;
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
                'El XML del libro no declara el atributo xsi:schemaLocation.'
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
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        $array['xml'] = base64_encode($array['xml']);

        return $array;
    }
}
