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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroVentas;

use Derafu\Array\ArrayReader;
use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\AbstractLibroComprasVentasBuilderStrategy;

/**
 * Estrategia `libro_ventas` del `BuilderWorker`.
 *
 * Construye el XML del Libro de Ventas (IECV con TipoOperacion VENTA)
 * a partir de los detalles normalizados por el `LoaderWorker`.
 */
#[Strategy(name: 'libro_ventas', worker: 'builder', component: 'book', package: 'billing')]
class BuilderStrategy extends AbstractLibroComprasVentasBuilderStrategy implements BuilderStrategyInterface
{
    /**
     * Tipos de documento que no van en el detalle del libro de ventas.
     *
     * El esquema acepta estos tipos en el detalle, pero la biblioteca los omite
     * a propósito, como una regla propia: se informan solo en el resumen del
     * período (sus totales igual se cuentan).
     *
     * @var array<int, int>
     */
    public const TIPOS_FUERA_DEL_DETALLE = [35, 38, 39, 41, 105, 500, 501, 919, 920, 922, 924];

    /**
     * {@inheritDoc}
     */
    protected function filterDetalle(array $detalles): array
    {
        return array_values(array_filter(
            $detalles,
            static fn (array $detalle): bool => !in_array(
                ArrayReader::fromArray($detalle, 'detalle')->requireAsInt('TpoDoc'),
                self::TIPOS_FUERA_DEL_DETALLE,
                true
            )
        ));
    }
}
