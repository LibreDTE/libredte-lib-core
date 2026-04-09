<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\LibroBoletas;

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
     * @return array
     */
    protected function normalizarCaratula(BookBagInterface $bag): array
    {
        return array_merge([
            'RutEmisorLibro' => $bag->getEmisor()?->getRut() ?? false,
            'RutEnvia' => $bag->getCertificate()?->getId() ?? false,
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => $bag->getBookAuth()['FchResol'] ?? false,
            'NroResol' => $bag->getBookAuth()['NroResol'] ?? false,
            'TipoLibro' => 'ESPECIAL',
            'TipoEnvio' => 'TOTAL',
            'NroSegmento' => false,
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
    protected function normalizarDetalle(array $detalle): array
    {
        // Valores por defecto.
        return array_merge([
            'TpoDoc' => false,
            'FolioDoc' => false,
            'Anulado' => false,
            'TpoServ' => 3,
            'FchEmiDoc' => false,
            'FchVencDoc' => false,
            'PeriodoDesde' => false,
            'PeriodoHasta' => false,
            'CdgSIISucur' => false,
            'RUTCliente' => false,
            'CodIntCli' => false,
            'MntExe' => false,
            'MntTotal' => false,
            'MntNoFact' => false,
            'MntPeriodo' => false,
            'SaldoAnt' => false,
            'VlrPagar' => false,
            'TotTicketBoleta' => false,
        ], $detalle);
    }
}
