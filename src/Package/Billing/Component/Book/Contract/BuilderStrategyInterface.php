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

use Derafu\Backbone\Contract\StrategyInterface;

/**
 * Interfaz para las estrategias del `BuilderWorker`.
 *
 * Cada estrategia construye el XML y la entidad de un tipo de libro específico
 * a partir de los detalles ya normalizados por el `LoaderWorker`.
 *
 * Los nombres de estrategia son simplemente el tipo de libro:
 *   - `libro_ventas`
 *   - `libro_compras`
 *   - `libro_boletas`
 *   - `libro_guias`
 *   - `resumen_ventas_diarias`
 */
interface BuilderStrategyInterface extends StrategyInterface
{
    /**
     * Construye la entidad libro a partir del bag normalizado.
     *
     * @param BookBagInterface $bag Bag con detalles ya normalizados.
     * @return BookInterface Entidad libro resultante con su XML.
     */
    public function build(BookBagInterface $bag): BookInterface;
}
