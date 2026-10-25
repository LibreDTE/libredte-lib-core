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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy;

use Derafu\Backbone\Abstract\AbstractStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;

/**
 * Estrategia base de carga desde array.
 */
abstract class AbstractArrayLoaderStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     */
    public function load(BookBagInterface $bag): BookBagInterface
    {
        return $this->normalize($bag->setDetalle(
            (array) $bag->getInputData() ?: $bag->getDetalle()
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function normalize(BookBagInterface $bag): BookBagInterface
    {
        $bag->setCaratula($this->normalizeCaratula($bag));

        $detalles = $bag->getDetalle();

        foreach ($detalles as &$detalle) {
            $detalle = $this->normalizeDetalle($detalle);
        }

        return $bag->setDetalle($detalles);
    }

    /**
     * Normaliza la carátula del libro.
     *
     * @param BookBagInterface $bag
     * @return array
     */
    abstract protected function normalizeCaratula(BookBagInterface $bag): array;

    /**
     * Normaliza un registro del detalle del libro.
     *
     * El orden de las claves determina el orden de los elementos en el XML,
     * que debe respetar el esquema XSD del libro.
     *
     * @param array $detalle
     * @return array
     */
    abstract protected function normalizeDetalle(array $detalle): array;
}
