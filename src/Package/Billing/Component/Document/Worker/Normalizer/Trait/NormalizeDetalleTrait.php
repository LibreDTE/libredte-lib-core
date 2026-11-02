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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\NormalizerException;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;

/**
 * Reglas de normalización para el detalle de los documentos.
 */
trait NormalizeDetalleTrait
{
    /**
     * Normaliza los detalles del documento.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     * @todo Revisar cómo se aplican descuentos y recargos. ¿Debería ser un
     * porcentaje del monto original?.
     */
    protected function normalizeDetalle(DocumentBagInterface $bag): void
    {
        $data = $bag->requireNormalizedData();

        // El detalle es obligatorio: todo documento debe tener al menos un ítem.
        if (empty($data['Detalle'])) {
            throw new NormalizerException(
                'The document detail (Detalle) must be indicated: there must be at least one item.'
            );
        }

        if (!isset($data['Detalle'][0])) {
            $data['Detalle'] = [
                $data['Detalle'],
            ];
        }

        $sumarMontoNF = !isset($data['Encabezado']['Totales']['MontoNF']);

        $item = 1;
        foreach ($data['Detalle'] as &$d) {
            $d = array_merge([
                'NroLinDet' => $item++,
                'CdgItem' => null,
                'IndExe' => null,
                'Retenedor' => null,
                'NmbItem' => null,
                'DscItem' => null,
                'QtyRef' => null,
                'UnmdRef' => null,
                'PrcRef' => null,
                'QtyItem' => null,
                'Subcantidad' => null,
                'FchElabor' => null,
                'FchVencim' => null,
                'UnmdItem' => null,
                'PrcItem' => null,
                'DescuentoPct' => null,
                'DescuentoMonto' => null,
                'RecargoPct' => null,
                'RecargoMonto' => null,
                'CodImpAdic' => null,
                'MontoItem' => null,
            ], $d);

            // Corregir datos.
            $d['NmbItem'] = mb_substr($d['NmbItem'], 0, 80);
            if (!empty($d['DscItem'])) {
                $d['DscItem'] = mb_substr($d['DscItem'], 0, 1000);
            }

            // Normalizar.
            if ($bag->requireTipoDocumento()->isExportacion()) {
                $d['IndExe'] = 1;
            }
            if (
                !empty($d['IndExe'])
                && !in_array((int) $d['IndExe'], [1, 2], true)
            ) {
                throw new NormalizerException([
                    'Item {line} has IndExe = {indExe}, which is not supported (only 1 and 2 are supported).',
                    'line' => $d['NroLinDet'],
                    'indExe' => $d['IndExe'],
                ]);
            }
            if (is_array($d['CdgItem'])) {
                $d['CdgItem'] = array_merge([
                    'TpoCodigo' => null,
                    'VlrCodigo' => null,
                ], $d['CdgItem']);
                if (
                    $d['Retenedor'] === null
                    && $d['CdgItem']['TpoCodigo'] === 'CPCS'
                ) {
                    $d['Retenedor'] = true;
                }
            }
            if ($d['Retenedor'] !== null) {
                if (!is_array($d['Retenedor'])) {
                    $d['Retenedor'] = ['IndAgente' => 'R'];
                }
                $d['Retenedor'] = array_merge([
                    'IndAgente' => 'R',
                    'MntBaseFaena' => null,
                    'MntMargComer' => null,
                    'PrcConsFinal' => null,
                ], $d['Retenedor']);
            }
            if ($d['CdgItem'] !== null && !is_array($d['CdgItem'])) {
                $d['CdgItem'] = [
                    'TpoCodigo' => empty($d['Retenedor']['IndAgente'])
                        ? 'INT1'
                        : 'CPCS'
                    ,
                    'VlrCodigo' => $d['CdgItem'],
                ];
            }
            if ($d['PrcItem']) {
                if (!$d['QtyItem']) {
                    $d['QtyItem'] = 1;
                }
                if (empty($d['MontoItem'])) {
                    $d['MontoItem'] = Utils::round(
                        (float) $d['QtyItem'] * (float)$d['PrcItem'],
                        $data['Encabezado']['Totales']['TpoMoneda']
                    );

                    // Aplicar descuento.
                    if ($d['DescuentoPct']) {
                        $d['DescuentoMonto'] = round(
                            $d['MontoItem'] * (float) $d['DescuentoPct'] / 100
                        );
                    }
                    $d['MontoItem'] -= $d['DescuentoMonto'];

                    // Aplicar recargo.
                    if ($d['RecargoPct']) {
                        $d['RecargoMonto'] = round(
                            $d['MontoItem'] * (float) $d['RecargoPct'] / 100
                        );
                    }
                    $d['MontoItem'] += $d['RecargoMonto'];

                    // Aproximar monto del item.
                    $d['MontoItem'] = Utils::round(
                        $d['MontoItem'],
                        $data['Encabezado']['Totales']['TpoMoneda']
                    );
                }
            }
            // Si el monto del item es vacío se estandariza como "0".
            elseif (empty($d['MontoItem'])) {
                $d['MontoItem'] = 0;
            }

            // Sumar valor del monto a MntNeto o MntExe según corresponda.
            if ($d['MontoItem']) {
                // Si no es boleta.
                if (!$bag->requireTipoDocumento()->isBoleta()) {
                    // Si es exento o no facturable.
                    if (!empty($d['IndExe'])) {
                        if ($d['IndExe'] == 1) {
                            $data['Encabezado']['Totales']['MntExe'] +=
                                $d['MontoItem']
                            ;
                        } elseif ($d['IndExe'] == 2) {
                            if ($sumarMontoNF) {
                                if (empty($data['Encabezado']['Totales']['MontoNF'])) {
                                    $data['Encabezado']['Totales']['MontoNF'] = 0;
                                }
                                $data['Encabezado']['Totales']['MontoNF'] +=
                                    $d['MontoItem']
                                ;
                            }
                        }
                    }

                    // Si es afecto, se agrega al monto neto.
                    else {
                        if (empty($data['Encabezado']['Totales']['MntNeto'])) {
                            $data['Encabezado']['Totales']['MntNeto'] = 0;
                        }
                        $data['Encabezado']['Totales']['MntNeto'] +=
                            $d['MontoItem']
                        ;
                    }
                }

                // Si es boleta.
                else {
                    // Si es exento o no facturable.
                    if (!empty($d['IndExe'])) {
                        if ($d['IndExe'] == 1) {
                            $data['Encabezado']['Totales']['MntExe'] +=
                                $d['MontoItem']
                            ;
                            $data['Encabezado']['Totales']['MntTotal'] +=
                                $d['MontoItem']
                            ;
                        } elseif ($d['IndExe'] == 2) {
                            if ($sumarMontoNF) {
                                if (empty($data['Encabezado']['Totales']['MontoNF'])) {
                                    $data['Encabezado']['Totales']['MontoNF'] = 0;
                                }
                                $data['Encabezado']['Totales']['MontoNF'] +=
                                    $d['MontoItem']
                                ;
                            }
                        }
                    }

                    // Si es afecto, sólo agregar al monto total.
                    else {
                        $data['Encabezado']['Totales']['MntTotal'] +=
                            $d['MontoItem']
                        ;
                    }
                }
            }
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}
