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
 * Interfaz para las estrategias del `LoaderWorker`.
 *
 * Cada estrategia sabe leer un formato de entrada específico para un tipo de
 * libro determinado, normaliza los detalles crudos y retorna el bag actualizado.
 *
 * Los nombres de estrategia siguen el patrón `{tipo}.{formato}`, por ejemplo:
 *   - `libro_ventas.array`
 *   - `libro_compras.csv`
 *   - `libro_guias.xml`
 */
interface LoaderStrategyInterface extends StrategyInterface
{
    /**
     * Carga y normaliza los detalles del bag según el formato de entrada.
     *
     * @param BookBagInterface $bag Bag con detalles crudos.
     * @return BookBagInterface El mismo bag con detalles normalizados.
     */
    public function load(BookBagInterface $bag): BookBagInterface;
}
