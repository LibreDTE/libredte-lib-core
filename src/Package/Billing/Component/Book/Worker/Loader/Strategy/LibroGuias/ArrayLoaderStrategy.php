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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\LibroGuias;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderStrategyInterface;

/**
 * Estrategia `libro_guias.array` del `LoaderWorker`.
 *
 * Normaliza los detalles del Libro de Guías de Despacho desde un arreglo PHP.
 * Compatible con los campos del legacy `LibroGuia::normalizarDetalle()`.
 */
#[Strategy(name: 'libro_guias.array', worker: 'loader', component: 'book', package: 'billing')]
class ArrayLoaderStrategy extends AbstractStrategy implements LoaderStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(BookBagInterface $bag): BookBagInterface
    {
        $detalles = $bag->getDetalle();

        foreach ($detalles as &$detalle) {
            $this->normalizarDetalle($detalle);
        }
        unset($detalle);

        return $bag->setDetalle($detalles);
    }

    /**
     * Normaliza un detalle del libro de guías de despacho.
     *
     * El orden de las claves determina el orden de los elementos en el XML.
     * Compatible con `LibroGuia::normalizarDetalle()` del legacy.
     */
    private function normalizarDetalle(array &$detalle): void
    {
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
    }
}
