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
                ->entityComponent
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
