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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\LibroBoletas;

use Derafu\Array\ArrayCaster;
use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractArrayLoaderStrategy;

/**
 * Estrategia `libro_boletas.array` del `LoaderWorker`.
 *
 * Normaliza los detalles del Libro de Boletas desde un arreglo PHP.
 */
#[Strategy(name: 'libro_boletas.array', worker: 'loader', component: 'book', package: 'billing')]
class ArrayLoaderStrategy extends AbstractArrayLoaderStrategy implements LoaderStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * Normaliza la carátula del libro de boletas.
     *
     *   - TipoLibro: 'ESPECIAL' (siempre es especial en boletas).
     *   - TipoEnvio: 'PARCIAL', 'FINAL', 'TOTAL' o 'AJUSTE'.
     *   - FolioNotificacion: correlativo que parte en 1 y se incrementa en 1
     *     por cada nuevo envío del libro de boletas al SII.
     *
     * Nota: los segmentos no están soportados en esta implementación.
     *
     * @param BookBagInterface $bag
     * @return array<string, mixed>
     */
    protected function normalizeCaratula(BookBagInterface $bag): array
    {
        return array_merge([
            'RutEmisorLibro' => $bag->getEmisor()?->getRut(),
            'RutEnvia' => $bag->getCertificate()?->getId(),
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => $bag->getBookAuth()['FchResol'] ?? null,
            'NroResol' => $bag->getBookAuth()['NroResol'] ?? null,
            'TipoLibro' => 'ESPECIAL',
            'TipoEnvio' => 'TOTAL',
            'NroSegmento' => null,
            'FolioNotificacion' => 1,
        ], $bag->getCaratula());
    }

    /**
     * {@inheritDoc}
     *
     * Normaliza un detalle del libro de boletas.
     *
     * El orden de las claves determina el orden de los elementos en el XML.
     */
    protected function normalizeDetalle(array $detalle): array
    {
        // Valores por defecto.
        return array_merge([
            'TpoDoc' => null,
            'FolioDoc' => null,
            'Anulado' => null,
            'TpoServ' => 3,
            'FchEmiDoc' => null,
            'FchVencDoc' => null,
            'PeriodoDesde' => null,
            'PeriodoHasta' => null,
            'CdgSIISucur' => null,
            'RUTCliente' => null,
            'CodIntCli' => null,
            'MntExe' => null,
            'MntTotal' => null,
            'MntNoFact' => null,
            'MntPeriodo' => null,
            'SaldoAnt' => null,
            'VlrPagar' => null,
            'TotTicketBoleta' => null,
        ], ArrayCaster::nullify($detalle, [false]));
    }
}
