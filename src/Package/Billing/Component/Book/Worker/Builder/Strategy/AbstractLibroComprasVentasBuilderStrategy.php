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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Xml\Service\XmlEncoder;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LibroComprasVentasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroComprasVentas;

/**
 * Estrategia base de construcción para Libro de Compras/Ventas.
 *
 * Recibe el bag con detalles ya normalizados por el `LoaderWorker`,
 * calcula los totales del período y genera el XML según `LibroCV_v10.xsd`.
 */
abstract class AbstractLibroComprasVentasBuilderStrategy extends AbstractStrategy
{
    /**
     * Valores por defecto de los totales del período por tipo de documento.
     *
     * El orden de las claves determina el orden de los elementos en el XML.
     *
     * @var array<string, mixed>
     */
    private array $totalDefault = [
        'TpoDoc' => null,
        'TotDoc' => 0,
        'TotAnulado' => false,
        'TotOpExe' => false,
        'TotMntExe' => 0,
        'TotMntNeto' => 0,
        'TotMntIVA' => 0,
        'TotIVAPropio' => false,
        'TotIVATerceros' => false,
        'TotLey18211' => false,
        'TotMntActivoFijo' => false,
        'TotMntIVAActivoFijo' => false,
        'TotIVANoRec' => false,
        'TotIVAUsoComun' => false,
        'FctProp' => false,
        'TotCredIVAUsoComun' => false,
        'TotIVAFueraPlazo' => false,
        'TotOtrosImp' => false,
        'TotIVARetTotal' => false,
        'TotIVARetParcial' => false,
        'TotImpSinCredito' => false,
        'TotMntTotal' => 0,
        'TotIVANoRetenido' => false,
        'TotMntNoFact' => false,
        'TotMntPeriodo' => false,
        'TotPsjNac' => false,
        'TotPsjInt' => false,
        'TotTabPuros' => false,
        'TotTabCigarrillos' => false,
        'TotTabElaborado' => false,
        'TotImpVehiculo' => false,
    ];

    /**
     * Construye el Libro de Compras/Ventas a partir del bag normalizado.
     */
    public function build(BookBagInterface $bag): LibroComprasVentasInterface
    {
        $caratula = $bag->getCaratula();
        $detalles = $bag->getDetalle();

        // Calcular resumen del período.
        $totalesPeriodo = $this->calculateTotalesPeriodo($detalles);

        // Construir carátula normalizada.
        $tipoOper = strtoupper($caratula['TipoOperacion'] ?? 'VENTA');
        $caratulaNorm = array_merge([
            'RutEmisorLibro' => false,
            'RutEnvia' => false,
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => false,
            'NroResol' => false,
            'TipoOperacion' => 'VENTA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
            'FolioNotificacion' => false,
        ], $caratula);

        // Construir ID del documento.
        $rut = str_replace('-', '', $caratulaNorm['RutEmisorLibro'] ?? '');
        $periodo = str_replace('-', '', $caratulaNorm['PeriodoTributario'] ?? date('Y-m'));
        $id = sprintf('LibreDTE_LIBRO_%s_%s_%s_%s', $tipoOper, $rut, $periodo, time());

        // Generar estructura XML.
        $resumenPeriodo = $totalesPeriodo ? ['TotalesPeriodo' => $totalesPeriodo] : false;

        $encoder = new XmlEncoder();
        $xmlDocument = $encoder->encode([
            'LibroCompraVenta' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte LibroCV_v10.xsd',
                    'version' => '1.0',
                ],
                'EnvioLibro' => [
                    '@attributes' => ['ID' => $id],
                    'Caratula' => $caratulaNorm,
                    'ResumenPeriodo' => $resumenPeriodo,
                    'Detalle' => $detalles ?: false,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ],
            ],
        ]);

        return new LibroComprasVentas($xmlDocument);
    }

    /**
     * Calcula los totales del período agrupados por TpoDoc.
     *
     * @param array<int, array<string, mixed>> $detalles
     * @return array<int|string, array<string, mixed>>
     */
    private function calculateTotalesPeriodo(array $detalles): array
    {
        $totales = [];

        foreach ($detalles as $d) {
            $tpoDoc = $d['TpoDoc'] ?? false;
            if (!$tpoDoc) {
                continue;
            }

            if (!isset($totales[$tpoDoc])) {
                $totales[$tpoDoc] = array_merge($this->totalDefault, ['TpoDoc' => $tpoDoc]);
            }

            $totales[$tpoDoc]['TotDoc']++;

            // Documentos anulados solo incrementan el contador.
            if (!empty($d['Anulado']) && $d['Anulado'] === 'A') {
                $totales[$tpoDoc]['TotAnulado'] = (int) $totales[$tpoDoc]['TotAnulado'] + 1;
                continue;
            }

            // Montos base.
            $totales[$tpoDoc]['TotMntExe'] += (int) ($d['MntExe'] ?? 0);
            $totales[$tpoDoc]['TotMntNeto'] += (int) ($d['MntNeto'] ?? 0);
            if (!empty($d['MntIVA'])) {
                $totales[$tpoDoc]['TotMntIVA'] += (int) $d['MntIVA'];
            }
            $totales[$tpoDoc]['TotMntTotal'] += (int) ($d['MntTotal'] ?? 0);

            // Activo fijo.
            if (!empty($d['MntActivoFijo'])) {
                $totales[$tpoDoc]['TotMntActivoFijo'] = (int) $totales[$tpoDoc]['TotMntActivoFijo'] + (int) $d['MntActivoFijo'];
            }
            if (!empty($d['MntIVAActivoFijo'])) {
                $totales[$tpoDoc]['TotMntIVAActivoFijo'] = (int) $totales[$tpoDoc]['TotMntIVAActivoFijo'] + (int) $d['MntIVAActivoFijo'];
            }

            // IVA no recuperable.
            if (!empty($d['IVANoRec'])) {
                if ($totales[$tpoDoc]['TotIVANoRec'] === false) {
                    $totales[$tpoDoc]['TotIVANoRec'] = [];
                }
                foreach ($d['IVANoRec'] as $iva) {
                    $cod = $iva['CodIVANoRec'];
                    if (!isset($totales[$tpoDoc]['TotIVANoRec'][$cod])) {
                        $totales[$tpoDoc]['TotIVANoRec'][$cod] = [
                            'CodIVANoRec' => $cod,
                            'TotOpIVANoRec' => 0,
                            'TotMntIVANoRec' => 0,
                        ];
                    }
                    $totales[$tpoDoc]['TotIVANoRec'][$cod]['TotOpIVANoRec']++;
                    $totales[$tpoDoc]['TotIVANoRec'][$cod]['TotMntIVANoRec'] += (int) $iva['MntIVANoRec'];
                }
            }

            // IVA de uso común.
            if (!empty($d['FctProp'])) {
                $totales[$tpoDoc]['TotIVAUsoComun'] = (int) $totales[$tpoDoc]['TotIVAUsoComun'] + (int) $d['IVAUsoComun'];
                $totales[$tpoDoc]['FctProp'] = $d['FctProp'] / 100;
                $totales[$tpoDoc]['TotCredIVAUsoComun'] = (int) $totales[$tpoDoc]['TotCredIVAUsoComun']
                    + (int) round((int) $d['IVAUsoComun'] * ($d['FctProp'] / 100));
            }

            // IVA fuera de plazo.
            if (!empty($d['IVAFueraPlazo'])) {
                $totales[$tpoDoc]['TotIVAFueraPlazo'] = (int) $totales[$tpoDoc]['TotIVAFueraPlazo'] + (int) $d['IVAFueraPlazo'];
            }

            // Otros impuestos.
            if (!empty($d['OtrosImp'])) {
                if ($totales[$tpoDoc]['TotOtrosImp'] === false) {
                    $totales[$tpoDoc]['TotOtrosImp'] = [];
                }
                foreach ($d['OtrosImp'] as $imp) {
                    $cod = $imp['CodImp'];
                    if (!isset($totales[$tpoDoc]['TotOtrosImp'][$cod])) {
                        $totales[$tpoDoc]['TotOtrosImp'][$cod] = [
                            'CodImp' => $cod,
                            'TotMntImp' => 0,
                        ];
                    }
                    $totales[$tpoDoc]['TotOtrosImp'][$cod]['TotMntImp'] += (int) $imp['MntImp'];
                }
            }

            // Monto sin crédito.
            if (!empty($d['MntSinCred'])) {
                $totales[$tpoDoc]['TotImpSinCredito'] = (int) $totales[$tpoDoc]['TotImpSinCredito'] + (int) $d['MntSinCred'];
            }

            // IVA retenido.
            if (!empty($d['IVARetTotal'])) {
                $totales[$tpoDoc]['TotIVARetTotal'] = (int) $totales[$tpoDoc]['TotIVARetTotal'] + (int) $d['IVARetTotal'];
            }
            if (!empty($d['IVARetParcial'])) {
                $totales[$tpoDoc]['TotIVARetParcial'] = (int) $totales[$tpoDoc]['TotIVARetParcial'] + (int) $d['IVARetParcial'];
            }
            if (!empty($d['IVANoRetenido'])) {
                $totales[$tpoDoc]['TotIVANoRetenido'] = (int) $totales[$tpoDoc]['TotIVANoRetenido'] + (int) $d['IVANoRetenido'];
            }

            // Impuesto vehículos.
            if (!empty($d['ImpVehiculo'])) {
                $totales[$tpoDoc]['TotImpVehiculo'] = (int) $totales[$tpoDoc]['TotImpVehiculo'] + (int) $d['ImpVehiculo'];
            }
        }

        // Re-indexar subarreglos para XML.
        foreach ($totales as &$t) {
            if (is_array($t['TotIVANoRec'])) {
                $t['TotIVANoRec'] = array_values($t['TotIVANoRec']);
            }
            if (is_array($t['TotOtrosImp'])) {
                $t['TotOtrosImp'] = array_values($t['TotOtrosImp']);
            }
        }
        unset($t);

        return $totales;
    }
}
