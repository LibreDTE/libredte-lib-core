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

namespace libredte\lib\Core\Package\Billing\Component\Book\Contract;

use Derafu\Xml\Contract\XmlDocumentInterface;
use JsonSerializable;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;

/**
 * Interfaz base para todos los libros tributarios electrónicos.
 *
 * Define el contrato mínimo que toda entidad de libro debe cumplir. El libro es
 * una vista sobre su `XmlDocumentInterface`; todos los datos se derivan del XML.
 */
interface BookInterface extends JsonSerializable
{
    /**
     * Entrega el tipo de libro.
     *
     * @return TipoLibro
     */
    public function getTipo(): TipoLibro;

    /**
     * Entrega el documento XML del libro.
     *
     * @return XmlDocumentInterface
     */
    public function getXmlDocument(): XmlDocumentInterface;

    /**
     * Entrega el XML del libro, incluyendo la cabecera XML.
     *
     * El XML estará codificado en ISO-8859-1.
     *
     * @return string XML del libro, con cabecera.
     */
    public function saveXml(): string;

    /**
     * Entrega el XML del libro, sin la cabecera XML.
     *
     * El XML estará codificado en ISO-8859-1.
     *
     * @return string XML del libro, sin cabecera.
     */
    public function getXml(): string;

    /**
     * Entrega el identificador único del documento de envío del libro.
     *
     * Este ID se usa como referencia en la firma XML y en el envío al SII.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Entrega la carátula del libro.
     *
     * @return array<string, mixed>
     */
    public function getCaratula(): array;

    /**
     * Entrega el resumen del libro.
     *
     * @return array<string, mixed>
     */
    public function getResumen(): array;

    /**
     * Entrega los registros de detalle del libro.
     *
     * @return array<array>
     */
    public function getDetalle(): array;

    /**
     * Entrega la cantidad de registros de detalle del libro.
     *
     * @return int
     */
    public function countDetalle(): int;

    /**
     * Indica si el libro está en formato simplificado.
     *
     * Esto afecta a los libros de compras/ventas, cambiando su esquema a
     * `LibroCVS_v10.xsd`. Si bien no afecta a otros libros, por simplicidad
     * se incluye este método en la interfaz base de todos los libros.
     *
     * @return bool
     */
    public function isSimplificado(): bool;

    /**
     * Entrega el nombre del archivo XSD que valida este libro.
     *
     * Por defecto delega en `TipoLibro::getSchema()`. Las subclases que usen
     * un esquema diferente según su estado (p. ej. `LibroComprasVentas` con
     * formato simplificado) deben sobrescribir este método.
     *
     * @return string Nombre del archivo XSD (sin ruta).
     */
    public function getSchema(): string;

    /**
     * Entrega el namespace XML que debe usarse en el elemento `Signature`.
     *
     * La mayoría de los libros usa el namespace estándar XML DSIG. Los libros
     * que requieran un namespace diferente (p. ej. el Libro de Boletas que usa
     * `http://www.sii.cl/SiiDte`) deben sobrescribir este método.
     *
     * @return string|null Namespace URI, o `null` para usar el estándar.
     */
    public function getSignatureNamespace(): ?string;

    /**
     * Entrega los datos del libro como un arreglo.
     *
     * @return array
     */
    public function toArray(): array;
}
