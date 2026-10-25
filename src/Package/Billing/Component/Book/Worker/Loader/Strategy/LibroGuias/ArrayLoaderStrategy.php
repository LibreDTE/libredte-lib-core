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
     * @return array
     */
    protected function normalizeCaratula(BookBagInterface $bag): array
    {
        return array_merge([
            'RutEmisorLibro'    => $bag->getEmisor()?->getRut() ?? false,
            'RutEnvia'          => $bag->getCertificate()?->getId() ?? false,
            'PeriodoTributario' => date('Y-m'),
            'FchResol'          => $bag->getBookAuth()['FchResol'] ?? false,
            'NroResol'          => $bag->getBookAuth()['NroResol'] ?? false,
            'TipoLibro'         => 'ESPECIAL',
            'TipoEnvio'         => 'TOTAL',
            'NroSegmento'       => false,
            'FolioNotificacion' => 1,
        ], $bag->getCaratula());
    }

    /**
     * {@inheritDoc}
     *
     * Normaliza un detalle del libro de guías de despacho.
     *
     * El orden de las claves determina el orden de los elementos en el XML.
     */
    protected function normalizeDetalle(array $detalle): array
    {
        // Valores por defecto.
        $detalle = array_merge([
            'Folio' => false,
            'Anulado' => false,
            'Operacion' => false,
            'TpoOper' => false,
            'FchDoc' => date('Y-m-d'),
            'RUTDoc' => false,
            'RznSoc' => false,
            'MntNeto' => false,
            'TasaImp' => 0,
            'IVA' => 0,
            'MntTotal' => false,
            'MntModificado' => false,
            'TpoDocRef' => false,
            'FolioDocRef' => false,
            'FchDocRef' => false,
        ], $detalle);

        // Truncar razón social.
        if ($detalle['RznSoc']) {
            $detalle['RznSoc'] = mb_substr((string) $detalle['RznSoc'], 0, 50);
        }

        // Calcular IVA si falta.
        if (!$detalle['IVA'] && $detalle['TasaImp'] && $detalle['MntNeto']) {
            $detalle['IVA'] = (int) round($detalle['MntNeto'] * ($detalle['TasaImp'] / 100));
        }

        // Calcular monto total si falta.
        if ($detalle['MntTotal'] === false) {
            $detalle['MntTotal'] = (int) $detalle['MntNeto'] + (int) $detalle['IVA'];
        }

        // Retornar el detalle normalizado.
        return $detalle;
    }
}
