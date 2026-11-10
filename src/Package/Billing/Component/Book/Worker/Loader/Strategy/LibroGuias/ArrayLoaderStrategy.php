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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\LibroGuias;

use Derafu\Array\ArrayCaster;
use Derafu\Array\ArrayReader;
use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractArrayLoaderStrategy;

/**
 * Estrategia `libro_guias.array` del `LoaderWorker`.
 *
 * Normaliza los detalles del Libro de Guías de Despacho desde un arreglo PHP.
 */
#[Strategy(name: 'libro_guias.array', worker: 'loader', component: 'book', package: 'billing')]
class ArrayLoaderStrategy extends AbstractArrayLoaderStrategy implements LoaderStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * Normaliza la carátula del libro de guías de despacho.
     *
     * @param BookBagInterface $bag
     * @return array<string, mixed>
     */
    protected function normalizeCaratula(BookBagInterface $bag): array
    {
        return array_merge([
            'RutEmisorLibro'    => $bag->getEmisor()?->getRut(),
            'RutEnvia'          => $bag->getCertificate()?->getId(),
            'PeriodoTributario' => date('Y-m'),
            'FchResol'          => $bag->getBookAuth()['fecha_resolucion'] ?? null,
            'NroResol'          => $bag->getBookAuth()['numero_resolucion'] ?? null,
            'TipoLibro'         => 'ESPECIAL',
            'TipoEnvio'         => 'TOTAL',
            'NroSegmento'       => null,
            'FolioNotificacion' => 1,
        ], $bag->getCaratula());
    }

    /**
     * {@inheritDoc}
     *
     * Normaliza un detalle del libro de guías de despacho.
     *
     * El orden de las claves determina el orden de los elementos en el XML.
     *
     * Los montos son números enteros y la tasa es un número: el texto que llega
     * de un CSV se acepta si se puede convertir, y uno que no es un número es un
     * error que dice en qué guía y en qué campo está. Los valores del detalle no
     * se reemplazan por los convertidos.
     *
     * @param array<string, mixed> $detalle
     * @return array<string, mixed>
     */
    protected function normalizeDetalle(array $detalle): array
    {
        // Valores por defecto.
        $detalle = array_merge([
            'Folio' => null,
            'Anulado' => null,
            'Operacion' => null,
            'TpoOper' => null,
            'FchDoc' => date('Y-m-d'),
            'RUTDoc' => null,
            'RznSoc' => null,
            'MntNeto' => null,
            'TasaImp' => 0,
            'IVA' => 0,
            'MntTotal' => null,
            'MntModificado' => null,
            'TpoDocRef' => null,
            'FolioDocRef' => null,
            'FchDocRef' => null,
        ], ArrayCaster::nullify($detalle, [false]));

        // Los montos y la tasa deben ser números.
        $this->assertNumbers($detalle);

        $row = ArrayReader::fromArray(ArrayCaster::nullify($detalle, [false, '']));

        // Truncar razón social.
        if ($detalle['RznSoc']) {
            $detalle['RznSoc'] = mb_substr($row->getAsString('RznSoc') ?? '', 0, 50);
        }

        // Calcular IVA si falta.
        if (!$detalle['IVA'] && $detalle['TasaImp'] && $detalle['MntNeto']) {
            $detalle['IVA'] = (int) round(($row->getAsInt('MntNeto') ?? 0) * (($row->getAsNumber('TasaImp') ?? 0) / 100));
        }

        // Calcular monto total si falta.
        if ($detalle['MntTotal'] === null) {
            $detalle['MntTotal'] = ($row->getAsInt('MntNeto') ?? 0) + (ArrayReader::fromArray(ArrayCaster::nullify($detalle, [false, '']))->getAsInt('IVA') ?? 0);
        }

        // Retornar el detalle normalizado.
        return $detalle;
    }

    /**
     * Verifica que los montos y la tasa de una guía sean números.
     *
     * Un valor que no está (`false`, vacío o `null`) no se verifica. Si uno no es
     * un número, el error dice en qué guía y en qué campo está.
     *
     * @param array<string, mixed> $detalle
     */
    private function assertNumbers(array $detalle): void
    {
        $reader = ArrayReader::fromArray(ArrayCaster::nullify($detalle, [false, '']), sprintf('document %s', is_scalar($detalle['Folio'] ?? null) ? (string) $detalle['Folio'] : '?'));

        foreach (['MntNeto', 'IVA', 'MntTotal', 'MntModificado'] as $field) {
            $reader->getAsInt($field);
        }

        $reader->getAsNumber('TasaImp');
    }
}
