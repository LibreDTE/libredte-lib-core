<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Sii\Dte\Documento\Normalization;

/**
 * Reglas de normalización para el detalle de los documentos.
 */
trait DetalleNormalizationTrait
{
    /**
     * Normaliza los detalles del documento.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     * @todo Revisar cómo se aplican descuentos y recargos. ¿Debería ser un
     * porcentaje del monto original?.
     */
    protected function applyDetalleNormalization(array $data): array
    {
        if (!isset($data['Detalle'][0])) {
            $data['Detalle'] = [
                $data['Detalle'],
            ];
        }

        $sumarMontoNF = (
            !isset($data['Encabezado']['Totales']['MontoNF'])
            || $data['Encabezado']['Totales']['MontoNF'] === false
        );

        $item = 1;
        foreach ($data['Detalle'] as &$d) {
            $d = array_merge([
                'NroLinDet' => $item++,
                'CdgItem' => false,
                'IndExe' => false,
                'Retenedor' => false,
                'NmbItem' => false,
                'DscItem' => false,
                'QtyRef' => false,
                'UnmdRef' => false,
                'PrcRef' => false,
                'QtyItem' => false,
                'Subcantidad' => false,
                'FchElabor' => false,
                'FchVencim' => false,
                'UnmdItem' => false,
                'PrcItem' => false,
                'DescuentoPct' => false,
                'DescuentoMonto' => false,
                'RecargoPct' => false,
                'RecargoMonto' => false,
                'CodImpAdic' => false,
                'MontoItem' => false,
            ], $d);

            // Corregir datos.
            $d['NmbItem'] = mb_substr($d['NmbItem'], 0, 80);
            if (!empty($d['DscItem'])) {
                $d['DscItem'] = mb_substr($d['DscItem'], 0, 1000);
            }

            // Normalizar.
            if ($this->getTipoDocumento()->esExportacion()) {
                $d['IndExe'] = 1;
            }
            if (is_array($d['CdgItem'])) {
                $d['CdgItem'] = array_merge([
                    'TpoCodigo' => false,
                    'VlrCodigo' => false,
                ], $d['CdgItem']);
                if (
                    $d['Retenedor'] === false
                    && $d['CdgItem']['TpoCodigo'] === 'CPCS'
                ) {
                    $d['Retenedor'] = true;
                }
            }
            if ($d['Retenedor'] !== false) {
                if (!is_array($d['Retenedor'])) {
                    $d['Retenedor'] = ['IndAgente' => 'R'];
                }
                $d['Retenedor'] = array_merge([
                    'IndAgente' => 'R',
                    'MntBaseFaena' => false,
                    'MntMargComer' => false,
                    'PrcConsFinal' => false,
                ], $d['Retenedor']);
            }
            if ($d['CdgItem'] !== false && !is_array($d['CdgItem'])) {
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
                    $d['MontoItem'] = $this->round(
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
                    $d['MontoItem'] = $this->round(
                        $d['MontoItem'],
                        $data['Encabezado']['Totales']['TpoMoneda']
                    );
                }
            // Si el monto del item es vacío se estandariza como "0".
            } else if (empty($d['MontoItem'])) {
                $d['MontoItem'] = 0;
            }

            // Sumar valor del monto a MntNeto o MntExe según corresponda.
            if ($d['MontoItem']) {
                // Si no es boleta.
                if (!$this->getTipoDocumento()->esBoleta()) {
                    // Si es exento o no facturable.
                    if (!empty($d['IndExe'])) {
                        if ($d['IndExe'] == 1) {
                            $data['Encabezado']['Totales']['MntExe'] +=
                                $d['MontoItem']
                            ;
                        } else if ($d['IndExe'] == 2) {
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
                        $data['Encabezado']['Totales']['MntNeto'] +=
                            $d['MontoItem']
                        ;
                    }
                }

                // Si es boleta.
                else {
                    // Si es exento o no facturable.
                    if (!empty($d['IndExe'])) {
                        if ($d['IndExe']==1) {
                            $data['Encabezado']['Totales']['MntExe'] +=
                                $d['MontoItem']
                            ;
                            $data['Encabezado']['Totales']['MntTotal'] +=
                                $d['MontoItem']
                            ;
                        } else if ($d['IndExe'] == 2) {
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

        // Entregar los datos normalizados.
        return $data;
    }
}
