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

namespace libredte\lib\Core\Package\Billing\Component\Book\Enum;

use ValueError;

/**
 * Tipos de libro tributario soportados por el componente `billing.book`.
 *
 * El valor de cada case es el identificador usado para seleccionar las
 * estrategias del `LoaderWorker` (`{value}.{format}`) y del `BuilderWorker`
 * (`{value}`).
 */
enum TipoLibro: string
{
    case VENTAS = 'libro_ventas';
    case COMPRAS = 'libro_compras';
    case BOLETAS = 'libro_boletas';
    case GUIAS = 'libro_guias';
    case RVD = 'resumen_ventas_diarias';

    /**
     * Retorna el XPath del resumen del libro.
     */
    public function getXpathResumen(): string
    {
        return match($this) {
            self::VENTAS, self::COMPRAS => '//ResumenPeriodo',
            self::BOLETAS               => '//Resumen',
            self::GUIAS                 => '//ResumenPeriodo',
            self::RVD                   => '//Resumen',
        };
    }

    /**
     * Retorna el nombre del archivo XSD correspondiente a este tipo de libro.
     */
    public function getSchema(): string
    {
        return match($this) {
            self::VENTAS, self::COMPRAS => 'LibroCV_v10.xsd',
            self::BOLETAS               => 'LibroBOLETA_v10.xsd',
            self::GUIAS                 => 'LibroGuia_v10.xsd',
            self::RVD                   => 'ConsumoFolio_v10.xsd',
        };
    }

    /**
     * Retorna el código del tipo de libro.
     */
    public function getCodigo(): string
    {
        return $this->value;
    }

    /**
     * Retorna el nombre del tipo de libro.
     */
    public function getNombre(): string
    {
        return match($this) {
            self::VENTAS                => 'Libro de ventas',
            self::COMPRAS               => 'Libro de compras',
            self::BOLETAS               => 'Libro de boletas',
            self::GUIAS                 => 'Libro de guías',
            self::RVD                   => 'Resumen de ventas diarias',
        };
    }

    /**
     * Retorna el nombre corto del tipo de libro.
     */
    public function getNombreCorto(): string
    {
        return match($this) {
            self::VENTAS                => 'Ventas',
            self::COMPRAS               => 'Compras',
            self::BOLETAS               => 'Boletas',
            self::GUIAS                 => 'Guias',
            self::RVD                   => 'RVD',
        };
    }

    /**
     * Retorna los datos del tipo de libro como un arreglo.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'codigo' => $this->getCodigo(),
            'nombre' => $this->getNombre(),
            'nombre_corto' => $this->getNombreCorto(),
        ];
    }

    /**
     * Retorna el case correspondiente al elemento raíz del XML, o `null` si no
     * se reconoce.
     *
     * Nota: `LibroComprasVentas` resuelve a `VENTAS` porque ambos casos
     * comparten el mismo esquema XSD; para distinguirlos se debe usar el campo
     * `TipoOperacion` de la carátula.
     */
    public static function tryFromTag(string $tag): ?self
    {
        return match($tag) {
            'LibroComprasVentas' => self::VENTAS,
            'LibroBoleta'        => self::BOLETAS,
            'LibroGuia'          => self::GUIAS,
            'ConsumoFolios'      => self::RVD,
            default              => null,
        };
    }

    /**
     * Retorna el case correspondiente al elemento raíz del XML.
     *
     * @throws \ValueError Si el elemento raíz no corresponde a ningún libro.
     */
    public static function fromTag(string $tag): self
    {
        return self::tryFromTag($tag) ?? throw new ValueError(sprintf(
            '"%s" no es un elemento raíz válido de libro tributario.',
            $tag
        ));
    }
}
