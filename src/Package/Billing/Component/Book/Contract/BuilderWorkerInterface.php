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
 * Interfaz para el worker `billing.book.builder`.
 *
 * Responsable de construir el XML y la entidad resultante de cualquier tipo de
 * libro tributario a partir de la bolsa con detalles normalizados.
 *
 * Selecciona la estrategia usando `BookBagInterface::getTipo()` directamente,
 * ya que no depende del formato de origen (eso es responsabilidad del loader).
 */
interface BuilderWorkerInterface extends WorkerInterface
{
    /**
     * Construye la entidad libro a partir de la bolsa normalizada.
     *
     * @param BookBagInterface $bag Bolsa con detalles ya normalizados.
     * @return BookBagInterface La bolsa con el libro construido.
     * @throws BookException En caso de error al construir el libro.
     */
    public function build(BookBagInterface $bag): BookBagInterface;
}
