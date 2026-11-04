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

use Derafu\Array\ArrayReader;
use Derafu\Array\ArrayWriter;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
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
        $writer = ArrayWriter::fromReference($data);

        // El detalle es obligatorio: todo documento debe tener al menos un ítem.
        $detalle = $writer->getReader()->get('Detalle', ValueType::mixed());
        if (empty($detalle)) {
            throw new NormalizerException(
                'The document detail (Detalle) must be indicated: there must be at least one item.'
            );
        }

        if (!isset(ValueType::array()->parse($detalle, 'Detalle')[0])) {
            $writer->set('Detalle', [$detalle]);
        }

        $sumarMontoNF = $writer->getReader()->get('Encabezado.Totales.MontoNF', ValueType::mixed()) === null;
        $tpoMoneda = $writer->getReader()->getAsString('Encabezado.Totales.TpoMoneda');
        $isExportacion = $bag->requireTipoDocumento()->isExportacion();
        $isBoleta = $bag->requireTipoDocumento()->isBoleta();

        // Suma un monto a un total del documento. Un total vacío se parte en
        // 0 solo si se indica.
        $sumar = static function (string $total, int|float $monto, bool $partirEnCero) use ($writer): void {
            $path = 'Encabezado.Totales.' . $total;
            if ($partirEnCero && empty($writer->getReader()->get($path, ValueType::mixed()))) {
                $writer->set($path, 0);
            }
            $actual = $writer->getReader()->getAsNumber($path) ?? 0;
            $writer->set($path, $actual + $monto);
        };

        $linea = 1;
        $items = $writer->getReader()->get('Detalle', ValueType::arrayOf(ValueType::map()), []);
        foreach ($items as $key => $item) {
            $path = 'Detalle.' . $key;
            $d = array_merge([
                'NroLinDet' => $linea++,
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
            ], $item);

            // Corregir datos.
            $d['NmbItem'] = mb_substr(ArrayReader::fromArray($d, $path)->requireString('NmbItem'), 0, 80);
            if (!empty($d['DscItem'])) {
                $d['DscItem'] = mb_substr(ArrayReader::fromArray($d, $path)->requireString('DscItem'), 0, 1000);
            }

            // Normalizar.
            if ($isExportacion) {
                $d['IndExe'] = 1;
            }
            if (
                !empty($d['IndExe'])
                && !in_array(ValueCaster::toInt($d['IndExe']), [1, 2], true)
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
                $retenedor = is_array($d['Retenedor']) ? $d['Retenedor'] : ['IndAgente' => 'R'];
                $d['Retenedor'] = array_merge([
                    'IndAgente' => 'R',
                    'MntBaseFaena' => null,
                    'MntMargComer' => null,
                    'PrcConsFinal' => null,
                ], $retenedor);
            }
            if ($d['CdgItem'] !== null && !is_array($d['CdgItem'])) {
                $d['CdgItem'] = [
                    'TpoCodigo' => empty(ArrayReader::fromArray($d, $path)->get('Retenedor.IndAgente', ValueType::mixed()))
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
                    $row = ArrayReader::fromArray($d, $path);
                    $montoItem = Utils::round(
                        $row->requireAsFloat('QtyItem') * $row->requireAsFloat('PrcItem'),
                        $tpoMoneda
                    );

                    // Aplicar descuento. Si se indicó el monto se usa tal cual
                    // (el usuario controla el valor exacto). Si solo se indicó
                    // el porcentaje se aplica directamente sobre el monto del
                    // ítem, sin redondear y sin agregar DescuentoMonto, porque
                    // en el XML ese monto es un entero positivo y no puede
                    // representar el porcentaje con decimales.
                    $descuentoMonto = $row->getAsNumber('DescuentoMonto');
                    if (!empty($descuentoMonto)) {
                        $montoItem -= $descuentoMonto;
                    } elseif (!empty($d['DescuentoPct'])) {
                        $montoItem -= $montoItem * $row->requireAsFloat('DescuentoPct') / 100;
                    }

                    // Aplicar recargo, sobre el monto con el descuento aplicado.
                    // Mismo criterio que el descuento.
                    $recargoMonto = $row->getAsNumber('RecargoMonto');
                    if (!empty($recargoMonto)) {
                        $montoItem += $recargoMonto;
                    } elseif (!empty($d['RecargoPct'])) {
                        $montoItem += $montoItem * $row->requireAsFloat('RecargoPct') / 100;
                    }

                    // Aproximar monto del item.
                    $d['MontoItem'] = Utils::round($montoItem, $tpoMoneda);
                }
            }
            // Si el monto del item es vacío se estandariza como "0".
            elseif (empty($d['MontoItem'])) {
                $d['MontoItem'] = 0;
            }

            // Sumar valor del monto a MntNeto o MntExe según corresponda.
            if ($d['MontoItem']) {
                $montoItem = ArrayReader::fromArray($d, $path)->requireAsNumber('MontoItem');

                // Si no es boleta.
                if (!$isBoleta) {
                    // Si es exento o no facturable.
                    if (!empty($d['IndExe'])) {
                        if ($d['IndExe'] == 1) {
                            $sumar('MntExe', $montoItem, false);
                        } elseif ($d['IndExe'] == 2) {
                            if ($sumarMontoNF) {
                                $sumar('MontoNF', $montoItem, true);
                            }
                        }
                    }

                    // Si es afecto, se agrega al monto neto.
                    else {
                        $sumar('MntNeto', $montoItem, true);
                    }
                }

                // Si es boleta.
                else {
                    // Si es exento o no facturable.
                    if (!empty($d['IndExe'])) {
                        if ($d['IndExe'] == 1) {
                            $sumar('MntExe', $montoItem, false);
                            $sumar('MntTotal', $montoItem, false);
                        } elseif ($d['IndExe'] == 2) {
                            if ($sumarMontoNF) {
                                $sumar('MontoNF', $montoItem, true);
                            }
                        }
                    }

                    // Si es afecto, sólo agregar al monto total.
                    else {
                        $sumar('MntTotal', $montoItem, false);
                    }
                }
            }

            $writer->set($path, $d);
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}
