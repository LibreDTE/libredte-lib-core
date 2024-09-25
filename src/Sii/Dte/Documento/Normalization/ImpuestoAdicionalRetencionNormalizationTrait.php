<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
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

use libredte\lib\Core\Repository\ImpuestosAdicionalesRepository;

/**
 * Reglas de normalización para documentos con impuesto adicional o retención.
 */
trait ImpuestoAdicionalRetencionNormalizationTrait
{
    /**
     * Entrega el repositorio de impuestos adicionales que se pueden usar en un
     * documento tributario.
     *
     * @return ImpuestosAdicionalesRepository
     */
    abstract protected function getImpuestosAdicionalesRepository(): ImpuestosAdicionalesRepository;

    /**
     * Calcula los montos de impuestos adicionales o retenciones.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    protected function applyImpuestoRetenidoNormalization(array $data): array
    {
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
            $data['Encabezado']['Totales']['ImptoReten'][$i] = array_merge([
                'TipoImp' => $codigo,
                'TasaImp' => $this->getImpuestosAdicionalesRepository()->getTasa($codigo),
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

        // Entregar los datos normalizados.
        return $data;
    }
}
