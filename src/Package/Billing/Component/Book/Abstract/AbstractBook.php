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
    public function getXml(): string
    {
        return $this->getXmlDocument()->setEncoding('ISO-8859-1')->saveXml();
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
