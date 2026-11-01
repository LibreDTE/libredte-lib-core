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

namespace libredte\lib\Core\Package\Billing\Component\Book\Enum;

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
     * Entrega las rutas del detalle que identifican a un documento en este tipo
     * de libro: no puede haber dos documentos con los mismos valores.
     *
     * Son el tipo y el folio del documento en ventas; en compras, además, el RUT
     * de quien lo emitió (dos proveedores pueden tener el mismo folio); el folio
     * en guías; y el tipo y el folio del documento en el resumen de ventas
     * diarias. El libro de boletas no tiene una clave que validar.
     *
     * @return array<int, string>
     */
    public function getDocumentKey(): array
    {
        return match($this) {
            self::VENTAS => ['TpoDoc', 'NroDoc'],
            self::COMPRAS => ['RUTDoc', 'TpoDoc', 'NroDoc'],
            self::GUIAS => ['Folio'],
            self::RVD => ['TpoDoc', 'NroDoc'],
            self::BOLETAS => [],
        };
    }

    /**
     * Indica si el tipo de libro admite un resumen manual (los totales de
     * documentos que no están en el detalle, como boletas en papel o pagos
     * electrónicos). Solo el libro de ventas.
     *
     * @return bool
     */
    public function acceptsResumen(): bool
    {
        return match($this) {
            self::VENTAS => true,
            self::COMPRAS, self::BOLETAS, self::GUIAS, self::RVD => false,
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
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'codigo' => $this->getCodigo(),
            'nombre' => $this->getNombre(),
            'nombre_corto' => $this->getNombreCorto(),
        ];
    }
}
