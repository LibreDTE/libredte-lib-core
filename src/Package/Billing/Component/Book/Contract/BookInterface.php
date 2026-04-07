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
     * Entrega el XML del libro.
     *
     * El XML estará codificado en ISO-8859-1.
     *
     * @return string XML del libro.
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
     * Entrega los datos del libro como un arreglo.
     *
     * @return array
     */
    public function toArray(): array;
}
