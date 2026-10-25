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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\ResumenVentasDiarias;

use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractArrayLoaderStrategy;

/**
 * Estrategia `resumen_ventas_diarias.array` del `LoaderWorker`.
 *
 * Para el RVD (ConsumoFolios) los detalles se usan directamente sin
 * normalización previa: el BuilderWorker los procesa en `calculateResumen()`.
 */
#[Strategy(name: 'resumen_ventas_diarias.array', worker: 'loader', component: 'book', package: 'billing')]
class ArrayLoaderStrategy extends AbstractArrayLoaderStrategy implements LoaderStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * Normaliza la carátula del resumen de ventas diarias.
     *
     * @param BookBagInterface $bag
     * @return array
     */
    protected function normalizeCaratula(BookBagInterface $bag): array
    {
        return array_merge([
            'RutEmisor' => $bag->getEmisor()?->getRut() ?? false,
            'RutEnvia' => $bag->getCertificate()?->getId() ?? false,
            'FchResol' => $bag->getBookAuth()['FchResol'] ?? false,
            'NroResol' => $bag->getBookAuth()['NroResol'] ?? false,
            'FchInicio' => false,
            'FchFinal' => false,
            'Correlativo' => 1,
            'SecEnvio' => 1,
        ], $bag->getCaratula());
    }

    /**
     * {@inheritDoc}
     *
     * Normaliza un detalle del resumen de ventas diarias.
     *
     * @param array $detalle
     * @return array
     */
    protected function normalizeDetalle(array $detalle): array
    {
        return array_merge([
            // TODO: Normalizar detalle según lo que se usa en el BuilderStrategy.
        ], $detalle);
    }
}
