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
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
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
        $data = $bag->getNormalizedData();

        // Si es una boleta y no están los datos de monto neto ni IVA se
        // calculan.
        if (
            $bag->getTipoDocumento()->isBoleta()
            && (
                empty($data['Encabezado']['IdDoc']['IndMntNeto'])
                || $data['Encabezado']['IdDoc']['IndMntNeto'] != 2
            )
        ) {
            $total = (int) $data['Encabezado']['Totales']['MntTotal']
                - (int)$data['Encabezado']['Totales']['MntExe']
            ;
            if (
                $total
                && (
                    empty($data['Encabezado']['Totales']['MntNeto'])
                    || empty($data['Encabezado']['Totales']['IVA'])
                )
            ) {
                list(
                    $data['Encabezado']['Totales']['MntNeto'],
                    $data['Encabezado']['Totales']['IVA']
                ) = Utils::calculateNetoIVA(
                    $total,
                    $data['Encabezado']['Totales']['TasaIVA']
                        ?? $bag->getTipoDocumento()->getDefaultTasaIVA()
                );
            }
        }

        // Agregar IVA y monto total.
        if (!empty($data['Encabezado']['Totales']['MntNeto'])) {
            if ($data['Encabezado']['IdDoc']['MntBruto'] == 1) {
                list(
                    $data['Encabezado']['Totales']['MntNeto'],
                    $data['Encabezado']['Totales']['IVA']
                ) = Utils::calculateNetoIVA(
                    $data['Encabezado']['Totales']['MntNeto'],
                    $data['Encabezado']['Totales']['TasaIVA']
                );
            } else {
                if (
                    empty($data['Encabezado']['Totales']['IVA'])
                    && !empty($data['Encabezado']['Totales']['TasaIVA'])
                ) {
                    $data['Encabezado']['Totales']['IVA'] = round(
                        $data['Encabezado']['Totales']['MntNeto']
                            * ($data['Encabezado']['Totales']['TasaIVA'] / 100)
                    );
                }
            }
            if (empty($data['Encabezado']['Totales']['MntTotal'])) {
                $data['Encabezado']['Totales']['MntTotal'] =
                    $data['Encabezado']['Totales']['MntNeto']
                ;
                if (!empty($data['Encabezado']['Totales']['IVA'])) {
                    $data['Encabezado']['Totales']['MntTotal'] +=
                        $data['Encabezado']['Totales']['IVA']
                    ;
                }
                if (!empty($data['Encabezado']['Totales']['MntExe'])) {
                    $data['Encabezado']['Totales']['MntTotal'] +=
                        $data['Encabezado']['Totales']['MntExe']
                    ;
                }
            }
        } else {
            if (
                !$data['Encabezado']['Totales']['MntTotal']
                && !empty($data['Encabezado']['Totales']['MntExe'])
            ) {
                $data['Encabezado']['Totales']['MntTotal'] =
                    $data['Encabezado']['Totales']['MntExe']
                ;
            }
        }

        // Si hay IVA definido se cambia a valor entero. El IVA no es decimal.
        if (is_numeric($data['Encabezado']['Totales']['IVA'] ?? null)) {
            $data['Encabezado']['Totales']['IVA'] =
                (int) $data['Encabezado']['Totales']['IVA']
            ;
        }

        // Si hay impuesto retenido o adicional se contabiliza en el total.
        if (!empty($data['Encabezado']['Totales']['ImptoReten'])) {
            foreach ($data['Encabezado']['Totales']['ImptoReten'] as &$ImptoReten) {
                $tipo = $this
                    ->repositoryManager
                    ->getRepository(ImpuestoAdicionalRetencion::class)
                    ->find($ImptoReten['TipoImp'])
                    ->getTipo()
                ;
                // Si es una retención, se resta al total y se traspasa a IVA
                // no retenido en caso que corresponda.
                if ($tipo === 'R') {
                    $data['Encabezado']['Totales']['MntTotal'] -=
                        $ImptoReten['MontoImp']
                    ;
                    if ($ImptoReten['MontoImp'] != $data['Encabezado']['Totales']['IVA']) {
                        $data['Encabezado']['Totales']['IVANoRet'] =
                            $data['Encabezado']['Totales']['IVA']
                                - $ImptoReten['MontoImp']
                        ;
                    }
                }

                // Si es impuesto adicional se suma al total.
                elseif ($tipo === 'A' && isset($ImptoReten['MontoImp'])) {
                    $data['Encabezado']['Totales']['MntTotal'] +=
                        $ImptoReten['MontoImp']
                    ;
                }
            }
        }

        // Si hay crédito aasociado al impuesto (IVA) por ser empresa
        // constructora se descuenta del total.
        if (
            !empty($data['Encabezado']['Totales']['CredEC'])
            && method_exists($this, 'getDefaultCredEC')
        ) {
            if ($data['Encabezado']['Totales']['CredEC'] === true) {
                $data['Encabezado']['Totales']['CredEC'] = round(
                    $data['Encabezado']['Totales']['IVA']
                        * $this->getDefaultCredEC()
                );
            }
            $data['Encabezado']['Totales']['MntTotal'] -=
                $data['Encabezado']['Totales']['CredEC']
            ;
        }

        // Si hay monto total y monto no facturable se agrega el monto del
        // periodo.
        if (!in_array($data['Encabezado']['IdDoc']['TipoDTE'], [39, 41])) {
            if (
                !empty($data['Encabezado']['Totales']['MntTotal'])
                && !empty($data['Encabezado']['Totales']['MontoNF'])
            ) {
                $data['Encabezado']['Totales']['MontoPeriodo'] =
                    $data['Encabezado']['Totales']['MntTotal']
                        + $data['Encabezado']['Totales']['MontoNF']
                ;
            }
        }

        // Si hay monto total definido, y el documento no es de exportación, se
        // cambia a valor entero. El monto total no es decimal en documentos
        // nacionales.
        if (is_numeric($data['Encabezado']['Totales']['MntTotal'] ?? null)) {
            if (!$bag->getTipoDocumento()->isExportacion()) {
                $data['Encabezado']['Totales']['MntTotal'] =
                    (int) $data['Encabezado']['Totales']['MntTotal']
                ;
            }
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}
