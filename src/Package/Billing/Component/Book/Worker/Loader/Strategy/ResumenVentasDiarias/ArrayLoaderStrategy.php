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

use Derafu\Array\ArrayReader;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractArrayLoaderStrategy;

/**
 * Estrategia `resumen_ventas_diarias.array` del `LoaderWorker`.
 *
 * Normaliza cada detalle del RVD (ConsumoFolios) a los tipos que usa el
 * `BuilderWorker` en `calculateResumen()`: números enteros en los folios y los
 * montos, y el tipo y la fecha del documento como datos obligatorios.
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
     * @return array<string, mixed>
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
     * El tipo y la fecha del documento son obligatorios. El folio y los montos
     * son números enteros: el texto que llega de un CSV se convierte y uno que no
     * es un número es un error que dice en qué documento y en qué campo está. Si
     * no se informa el monto total, es la suma de los montos exento, neto y de
     * IVA. Lo que no se informa queda en `false`.
     *
     * @param array<string, mixed> $detalle
     * @return array<string, mixed>
     */
    protected function normalizeDetalle(array $detalle): array
    {
        // En los detalles `false` es lo que se pone cuando no hay un valor.
        $row = array_map(static fn (mixed $value): mixed => $value === false ? null : $value, $detalle);

        $reader = ArrayReader::fromArray($row, sprintf(
            'document %s/%s',
            ValueType::toString()->nullable()->parse($row['TpoDoc'] ?? null, 'TpoDoc') ?? '?',
            ValueType::toString()->nullable()->parse($row['NroDoc'] ?? null, 'NroDoc') ?? '?'
        ));

        $exempt = $reader->getAsInt('MntExe');
        $net = $reader->getAsInt('MntNeto');
        $vat = $reader->getAsInt('MntIVA');

        return [
            'TpoDoc' => $reader->requireAsInt('TpoDoc'),
            'NroDoc' => $reader->getAsInt('NroDoc') ?? false,
            'FchDoc' => $reader->requireString('FchDoc'),
            'TasaImp' => $reader->getAsNumber('TasaImp') ?? false,
            'MntNeto' => $net ?? false,
            'MntIVA' => $vat ?? false,
            'MntExe' => $exempt ?? false,
            'MntTotal' => $reader->getAsInt('MntTotal') ?? ($exempt ?? 0) + ($net ?? 0) + ($vat ?? 0),
        ];
    }
}
