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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\ResumenVentasDiarias;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderStrategyInterface;

/**
 * Estrategia `resumen_ventas_diarias.array` del `LoaderWorker`.
 *
 * Para el RVD (ConsumoFolios) los detalles se usan directamente sin
 * normalización previa: el BuilderWorker los procesa en `calcularResumen()`.
 */
#[Strategy(name: 'resumen_ventas_diarias.array', worker: 'loader', component: 'book', package: 'billing')]
class ArrayLoaderStrategy extends AbstractStrategy implements LoaderStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(BookBagInterface $bag): BookBagInterface
    {
        $bag->setCaratula($this->normalizarCaratula($bag));

        $detalles = $bag->getDetalle();

        foreach ($detalles as &$detalle) {
            $this->normalizarDetalle($detalle);
        }
        unset($detalle);

        return $bag->setDetalle($detalles);
    }

    /**
     * Normaliza la carátula del resumen de ventas diarias.
     *
     * @param BookBagInterface $bag
     * @return array
     */
    private function normalizarCaratula(BookBagInterface $bag): array
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
     * Normaliza un detalle del resumen de ventas diarias.
     *
     * @param array $detalle
     * @return void
     */
    private function normalizarDetalle(array &$detalle): void
    {
        $detalle = array_merge([
            // TODO: Normalizar detalle según lo que se usa en el BuilderStrategy.
        ], $detalle);
    }
}
