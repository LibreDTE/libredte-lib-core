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

/**
 * Reglas de normalización para documentos con impuesto adicional o retención.
 */
trait NormalizeImpuestoAdicionalRetencionTrait
{
    /**
     * Calcula los montos de impuestos adicionales o retenciones.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     */
    protected function normalizeImpuestoAdicionalRetencion(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Copiar montos.
        $montos = [];
        foreach ($data['Detalle'] as &$d) {
            if (!empty($d['CodImpAdic'])) {
                if (!isset($montos[$d['CodImpAdic']])) {
                    $montos[$d['CodImpAdic']] = 0;
                }
                $montos[$d['CodImpAdic']] += $d['MontoItem'];
            }
        }

        // Si hay montos y no hay total para impuesto retenido se arma.
        if (!empty($montos)) {
            if (!is_array($data['Encabezado']['Totales']['ImptoReten'])) {
                $data['Encabezado']['Totales']['ImptoReten'] = [];
            } elseif (!isset($data['Encabezado']['Totales']['ImptoReten'][0])) {
                $data['Encabezado']['Totales']['ImptoReten'] = [
                    $data['Encabezado']['Totales']['ImptoReten'],
                ];
            }
        }

        // Armar impuesto adicional o retención en los totales.
        foreach ($montos as $codigo => $neto) {
            // Buscar si existe el impuesto en los totales.
            $i = 0;
            foreach ($data['Encabezado']['Totales']['ImptoReten'] as &$ImptoReten) {
                if ($ImptoReten['TipoImp'] == $codigo) {
                    break;
                }
                $i++;
            }

            // Si no existe se crea.
            if (!isset($data['Encabezado']['Totales']['ImptoReten'][$i])) {
                $data['Encabezado']['Totales']['ImptoReten'][] = [
                    'TipoImp' => $codigo,
                ];
            }

            // Se normaliza.
            $tasa = $this
                ->repositoryManager
                ->getRepository(ImpuestoAdicionalRetencion::class)
                ->find($codigo)
                ->getTasa()
            ;
            $data['Encabezado']['Totales']['ImptoReten'][$i] = array_merge([
                'TipoImp' => $codigo,
                'TasaImp' => $tasa,
                'MontoImp' => null,
            ], $data['Encabezado']['Totales']['ImptoReten'][$i]);

            // Si el monto no existe se asigna.
            if ($data['Encabezado']['Totales']['ImptoReten'][$i]['MontoImp'] === null) {
                $data['Encabezado']['Totales']['ImptoReten'][$i]['MontoImp'] = round(
                    $neto * $data['Encabezado']['Totales']['ImptoReten'][$i]['TasaImp'] / 100
                );
            }
        }

        // Quitar los códigos que no existen en el detalle.
        if (
            isset($data['Encabezado']['Totales']['ImptoReten'])
            && is_array($data['Encabezado']['Totales']['ImptoReten'])
        ) {
            $codigos = array_keys($montos);
            $n_impuestos = count($data['Encabezado']['Totales']['ImptoReten']);
            for ($i = 0; $i < $n_impuestos; $i++) {
                if (!in_array($data['Encabezado']['Totales']['ImptoReten'][$i]['TipoImp'], $codigos)) {
                    unset($data['Encabezado']['Totales']['ImptoReten'][$i]);
                }
            }
            sort($data['Encabezado']['Totales']['ImptoReten']);
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}
