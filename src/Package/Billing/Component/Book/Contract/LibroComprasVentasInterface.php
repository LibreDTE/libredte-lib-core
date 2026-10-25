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

use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoOperacion;

/**
 * Interfaz para el Libro de Compras o Ventas (IECV).
 *
 * Producido tanto por `LibroComprasWorker` como por `LibroVentasWorker`. La
 * diferencia entre ambos libros reside exclusivamente en el `TipoOperacion`.
 */
interface LibroComprasVentasInterface extends BookInterface
{
    /**
     * Entrega el tipo de operación del libro: `TipoOperacion::COMPRA` o
     * `TipoOperacion::VENTA`.
     *
     * @return TipoOperacion
     */
    public function getTipoOperacion(): TipoOperacion;

    /**
     * Entrega los totales del período agrupados por tipo de documento.
     *
     * Cada elemento del arreglo corresponde a un tipo de documento distinto y
     * contiene los campos calculados (TotDoc, TotMntNeto, TotMntIVA, etc.).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTotalesPeriodo(): array;
}
