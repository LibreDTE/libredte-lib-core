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
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;

/**
 * Reglas de normalización para el IVA y monto total del documento.
 */
trait NormalizeIvaMntTotalTrait
{
    /**
     * Calcula el monto del IVA y el monto total del documento a partir del
     * monto neto y la tasa de IVA si es que existe.
     *
     * WARNING: Si es una boleta y tiene impuestos adicionales, no se
     * consideran los casos de esos impuestos adicionales. Se deberán indicar
     * los campos de MntNeto e IVA y no usar esta parte de la normalización.
     *
     * WARNING: Si el valor IndMntNeto es 2 indica que los montos de las líneas
     * son netos en cuyo caso no aplica el cálculo de neto e IVA a partir del
     * total y deberá venir informado de otra forma (aun no definido).
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     * @todo Revisar si los WARNING de la descripción del método realmente son
     * un problema y, si lo son, corregirlos.
     */
    protected function normalizeIvaMntTotal(DocumentBagInterface $bag): void
    {
        $data = $bag->requireNormalizedData();
        $writer = ArrayWriter::fromReference($data);
        $t = 'Encabezado.Totales.';

        // Lectura cruda de un dato, y lectura de un dato numérico.
        $get = static fn (string $path): mixed => $writer->getReader()->get($path, ValueType::mixed());
        $num = static fn (string $path): int|float|null => $writer->getReader()->getAsNumber($path);

        // Suma un monto (positivo o negativo) a un dato numérico.
        $sumar = static function (string $path, int|float $monto) use ($writer): void {
            $writer->set($path, ($writer->getReader()->getAsNumber($path) ?? 0) + $monto);
        };

        // Si es una boleta y no están los datos de monto neto ni IVA se
        // calculan.
        if (
            $bag->requireTipoDocumento()->isBoleta()
            && (
                empty($get('Encabezado.IdDoc.IndMntNeto'))
                || $get('Encabezado.IdDoc.IndMntNeto') != 2
            )
        ) {
            $total = (int) ($num($t . 'MntTotal') ?? 0)
                - (int) ($num($t . 'MntExe') ?? 0)
            ;
            if (
                $total
                && (
                    empty($get($t . 'MntNeto'))
                    || empty($get($t . 'IVA'))
                )
            ) {
                [$neto, $iva] = Utils::calculateNetoIVA(
                    $total,
                    $num($t . 'TasaIVA')
                        ?? $bag->requireTipoDocumento()->getDefaultTasaIVA()
                );
                $writer->set($t . 'MntNeto', $neto);
                $writer->set($t . 'IVA', $iva);
            }
        }

        // Agregar IVA y monto total.
        if (!empty($get($t . 'MntNeto'))) {
            if ($get('Encabezado.IdDoc.MntBruto') == 1) {
                [$neto, $iva] = Utils::calculateNetoIVA(
                    $num($t . 'MntNeto') ?? 0,
                    $num($t . 'TasaIVA')
                );
                $writer->set($t . 'MntNeto', $neto);
                $writer->set($t . 'IVA', $iva);
            } else {
                if (
                    empty($get($t . 'IVA'))
                    && !empty($get($t . 'TasaIVA'))
                ) {
                    $writer->set($t . 'IVA', round(
                        ($num($t . 'MntNeto') ?? 0)
                            * (($num($t . 'TasaIVA') ?? 0) / 100)
                    ));
                }
            }
            if (empty($get($t . 'MntTotal'))) {
                $writer->set($t . 'MntTotal', $get($t . 'MntNeto'));
                if (!empty($get($t . 'IVA'))) {
                    $sumar($t . 'MntTotal', $num($t . 'IVA') ?? 0);
                }
                if (!empty($get($t . 'MntExe'))) {
                    $sumar($t . 'MntTotal', $num($t . 'MntExe') ?? 0);
                }
            }
        } else {
            if (
                !$get($t . 'MntTotal')
                && !empty($get($t . 'MntExe'))
            ) {
                $writer->set($t . 'MntTotal', $get($t . 'MntExe'));
            }
        }

        // Si hay IVA definido se cambia a valor entero. El IVA no es decimal.
        if (is_numeric($get($t . 'IVA'))) {
            $writer->set($t . 'IVA', (int) $num($t . 'IVA'));
        }

        // Si hay impuesto retenido o adicional se contabiliza en el total.
        if (!empty($get($t . 'ImptoReten'))) {
            $impuestos = $writer->getReader()->get(
                $t . 'ImptoReten',
                ValueType::arrayOf(ValueType::map()),
                []
            );
            foreach ($impuestos as $ImptoReten) {
                // El repositorio entrega una entidad "falsa" si el código no
                // existe, por lo que su find() nunca retorna null.
                $tipo = ValueCaster::requireObject(
                    $this->repositoryManager->getRepository(
                        ImpuestoAdicionalRetencion::class
                    ),
                    ImpuestoAdicionalRetencionRepository::class
                )->find($ImptoReten['TipoImp'])->getTipo();
                $montoImp = ArrayReader::fromArray($ImptoReten)->getAsNumber('MontoImp') ?? 0;
                // Si es una retención, se resta al total y se traspasa a IVA
                // no retenido en caso que corresponda.
                if ($tipo === 'R') {
                    $sumar($t . 'MntTotal', -$montoImp);
                    if (($ImptoReten['MontoImp'] ?? null) != $get($t . 'IVA')) {
                        $writer->set(
                            $t . 'IVANoRet',
                            ($num($t . 'IVA') ?? 0) - $montoImp
                        );
                    }
                }

                // Si es impuesto adicional se suma al total.
                elseif ($tipo === 'A' && isset($ImptoReten['MontoImp'])) {
                    $sumar($t . 'MntTotal', $montoImp);
                }
            }
        }

        // Si hay crédito aasociado al impuesto (IVA) por ser empresa
        // constructora se descuenta del total.
        if (
            !empty($get($t . 'CredEC'))
            && method_exists($this, 'getDefaultCredEC')
        ) {
            if ($get($t . 'CredEC') === true) {
                $writer->set($t . 'CredEC', round(
                    ($num($t . 'IVA') ?? 0)
                        * (ValueCaster::toFloat($this->getDefaultCredEC()) ?? 0.0)
                ));
            }
            $sumar($t . 'MntTotal', -($num($t . 'CredEC') ?? 0));
        }

        // Si hay monto total y monto no facturable se agrega el monto del
        // periodo.
        if (!in_array($get('Encabezado.IdDoc.TipoDTE'), [39, 41])) {
            if (
                !empty($get($t . 'MntTotal'))
                && !empty($get($t . 'MontoNF'))
            ) {
                $writer->set(
                    $t . 'MontoPeriodo',
                    ($num($t . 'MntTotal') ?? 0) + ($num($t . 'MontoNF') ?? 0)
                );
            }
        }

        // Si hay monto total definido, y el documento no es de exportación, se
        // cambia a valor entero. El monto total no es decimal en documentos
        // nacionales.
        if (is_numeric($get($t . 'MntTotal'))) {
            if (!$bag->requireTipoDocumento()->isExportacion()) {
                $writer->set($t . 'MntTotal', (int) $num($t . 'MntTotal'));
            }
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}
