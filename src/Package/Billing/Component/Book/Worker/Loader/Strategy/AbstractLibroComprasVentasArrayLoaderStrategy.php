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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy;

use Derafu\Backbone\Abstract\AbstractStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoOperacion;

/**
 * Estrategia base de carga desde array para Libro de Compras/Ventas.
 *
 * Normaliza cada registro de detalle según el esquema `LibroCV_v10.xsd`:
 * añade valores por defecto, calcula IVA cuando falta, normaliza estructuras
 * de IVA no recuperable y otros impuestos, y calcula el monto total.
 *
 * Compatible con todos los campos del legacy `LibroComprasVentas::normalizarDetalle()`.
 */
abstract class AbstractLibroComprasVentasArrayLoaderStrategy extends AbstractStrategy
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
     * Normaliza la carátula del libro de compra/venta.
     *
     * @param BookBagInterface $bag
     * @return array
     */
    private function normalizarCaratula(BookBagInterface $bag): array
    {
        return array_merge([
            'RutEmisorLibro'    => $bag->getEmisor()?->getRut() ?? false,
            'RutEnvia'          => $bag->getCertificate()?->getId() ?? false,
            'PeriodoTributario' => date('Y-m'),
            'FchResol'          => $bag->getBookAuth()['FchResol'] ?? false,
            'NroResol'          => $bag->getBookAuth()['NroResol'] ?? false,
            'TipoOperacion'     => $bag->getTipo() === TipoLibro::VENTAS
                ? TipoOperacion::VENTA->value
                : TipoOperacion::COMPRA->value
            ,
            'TipoLibro'         => 'MENSUAL',
            'TipoEnvio'         => 'TOTAL',
        ], $bag->getCaratula());
    }

    /**
     * Normaliza un registro de detalle del libro de compra/venta.
     *
     * El orden de las claves determina el orden de los elementos en el XML,
     * que debe respetar el esquema `LibroCV_v10.xsd`.
     */
    private function normalizarDetalle(array &$detalle): void
    {
        $detalle = array_merge([
            'TpoDoc' => false,
            'Emisor' => false,
            'IndFactCompra' => false,
            'NroDoc' => false,
            'Anulado' => false,
            'Operacion' => false,
            'TpoImp' => 1,
            'TasaImp' => false,
            'NumInt' => false,
            'IndServicio' => false,
            'IndSinCosto' => false,
            'FchDoc' => false,
            'CdgSIISucur' => false,
            'RUTDoc' => false,
            'RznSoc' => false,
            'Extranjero' => false,
            'TpoDocRef' => false,
            'FolioDocRef' => false,
            'MntExe' => false,
            'MntNeto' => false,
            'MntIVA' => false,
            'MntActivoFijo' => false,
            'MntIVAActivoFijo' => false,
            'IVANoRec' => false,
            'IVAUsoComun' => false,
            'IVAFueraPlazo' => false,
            'IVAPropio' => false,
            'IVATerceros' => false,
            'Ley18211' => false,
            'OtrosImp' => false,
            'MntSinCred' => false,
            'IVARetTotal' => false,
            'IVARetParcial' => false,
            'CredEC' => false,
            'DepEnvase' => false,
            'Liquidaciones' => false,
            'MntTotal' => false,
            'IVANoRetenido' => false,
            'MntNoFact' => false,
            'MntPeriodo' => false,
            'PsjNac' => false,
            'PsjInt' => false,
            'TabPuros' => false,
            'TabCigarrillos' => false,
            'TabElaborado' => false,
            'ImpVehiculo' => false,
        ], $detalle);

        // Si está anulado, se mantiene solo el mínimo requerido.
        if (!empty($detalle['Anulado']) && $detalle['Anulado'] === 'A') {
            $detalle = [
                'TpoDoc' => $detalle['TpoDoc'],
                'NroDoc' => $detalle['NroDoc'],
                'Anulado' => $detalle['Anulado'],
            ];
            return;
        }

        // Truncar razón social.
        if ($detalle['RznSoc']) {
            $detalle['RznSoc'] = mb_substr((string) $detalle['RznSoc'], 0, 50);
        }

        // Calcular IVA de uso común si hay factor de proporcionalidad.
        if (isset($detalle['FctProp'])) {
            if ($detalle['IVAUsoComun'] === false) {
                $detalle['IVAUsoComun'] = (int) round(
                    (int) $detalle['MntNeto'] * ($detalle['TasaImp'] / 100)
                );
            }
        } elseif (!$detalle['MntIVA'] && !is_array($detalle['IVANoRec']) && $detalle['TasaImp'] && $detalle['MntNeto']) {
            // Calcular IVA si falta y no hay IVA no recuperable.
            $detalle['MntIVA'] = (int) round($detalle['MntNeto'] * ($detalle['TasaImp'] / 100));
        }

        // Normalizar IVA no recuperable a lista.
        if (!empty($detalle['IVANoRec']) && !isset($detalle['IVANoRec'][0])) {
            $detalle['IVANoRec'] = [$detalle['IVANoRec']];
        }

        // Normalizar otros impuestos a lista.
        if (!empty($detalle['OtrosImp']) && !isset($detalle['OtrosImp'][0])) {
            $detalle['OtrosImp'] = [$detalle['OtrosImp']];
        }

        // Calcular monto total si falta.
        if ($detalle['MntTotal'] === false) {
            $total = (int) $detalle['MntExe'] + (int) $detalle['MntNeto'] + (int) $detalle['MntIVA'];
            if (!empty($detalle['IVANoRec'])) {
                foreach ($detalle['IVANoRec'] as $iva) {
                    $total += (int) $iva['MntIVANoRec'];
                }
            }
            if (isset($detalle['FctProp'])) {
                $total += (int) $detalle['IVAUsoComun'];
            }
            $total += (int) $detalle['MntSinCred']
                + (int) $detalle['TabPuros']
                + (int) $detalle['TabCigarrillos']
                + (int) $detalle['TabElaborado']
                + (int) $detalle['ImpVehiculo'];
            $detalle['MntTotal'] = $total;
        }

        // Si no hay monto neto, quitar campos de IVA.
        if ($detalle['MntNeto'] === false) {
            $detalle['MntNeto'] = $detalle['TasaImp'] = $detalle['MntIVA'] = false;
        }

        // Limpiar código de sucursal si es 0 o vacío.
        if (!$detalle['CdgSIISucur']) {
            $detalle['CdgSIISucur'] = false;
        }
    }
}
