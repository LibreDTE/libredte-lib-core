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

use Derafu\Backbone\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;

/**
 * Interfaz para el worker `billing.book.loader`.
 *
 * Responsable de cargar y normalizar los datos de entrada de cualquier tipo de
 * libro tributario desde cualquier formato de origen (array, CSV, XML, etc.).
 *
 * Selecciona la estrategia como `{tipo}.{formato}` donde:
 *   - `tipo` proviene de `BookBagInterface::getTipo()`.
 *   - `formato` proviene de `BookBagInterface::getLoaderOptions()['format']`
 *     (por defecto 'array').
 */
interface LoaderWorkerInterface extends WorkerInterface
{
    /**
     * Carga y normaliza los datos de entrada de un libro tributario.
     *
     * Deja lista la carátula y el detalle del libro, en la forma exacta
     * que corresponde a su tipo, a partir de los datos de entrada
     * entregados en cualquiera de los formatos de origen soportados.
     *
     * Esta operación únicamente prepara esos datos: no construye el
     * libro ni genera su documento firmado, por lo que ese resultado
     * nunca forma parte de lo que esta operación entrega, sin importar
     * los datos de entrada recibidos. Para obtener el libro construido
     * y su documento hay que continuar con la operación de
     * construcción, usando esta misma carátula y detalle ya
     * normalizados.
     *
     * La autorización del emisor del libro (fecha y número de la
     * resolución que lo autoriza) solo aparece en el resultado cuando
     * esa autorización fue entregada junto con los datos del emisor; si
     * no se entregó, simplemente no va a estar presente.
     *
     * @param BookBagInterface $bag Datos de entrada del libro, junto con
     * su tipo y el emisor.
     * @return BookBagInterface Los mismos datos, con la carátula y el
     * detalle ya normalizados.
     * @throws BookException En caso de error al cargar o normalizar los
     * datos.
     */
    public function load(BookBagInterface $bag): BookBagInterface;
}
