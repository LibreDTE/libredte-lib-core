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
 * Reglas de normalización para documentos de exportación.
 */
trait ExportacionNormalizationTrait
{
    /**
     * Normaliza los datos de exportación de un documento.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    protected function applyExportacionNormalization(array $data): array
    {
        // Agregar modalidad de venta por defecto si no existe.
        if (
            empty($data['Encabezado']['Transporte']['Aduana']['CodModVenta'])
            && (
                !isset($data['Encabezado']['IdDoc']['IndServicio'])
                || !in_array($data['Encabezado']['IdDoc']['IndServicio'], [3, 4, 5])
            )
        ) {
            $data['Encabezado']['Transporte']['Aduana']['CodModVenta'] = 1;
        }

        // Quitar campos que no son parte del documento de exportacion.
        $data['Encabezado']['Receptor']['CmnaRecep'] = false;

        // Colocar forma de pago de exportación.
        if (!empty($data['Encabezado']['IdDoc']['FmaPago'])) {
            $formas = [3 => 21];
            if (isset($formas[$data['Encabezado']['IdDoc']['FmaPago']])) {
                $data['Encabezado']['IdDoc']['FmaPagExp'] =
                    $formas[$data['Encabezado']['IdDoc']['FmaPago']]
                ;
            }
            $data['Encabezado']['IdDoc']['FmaPago'] = false;
        }

        // Si es entrega gratuita se coloca el tipo de cambio en CLP en 0 para
        // que total sea 0.
        if (
            !empty($data['Encabezado']['IdDoc']['FmaPagExp'])
            && $data['Encabezado']['IdDoc']['FmaPagExp'] == 21
            && !empty($data['Encabezado']['OtraMoneda'])
        ) {
            if (!isset($data['Encabezado']['OtraMoneda'][0])) {
                $data['Encabezado']['OtraMoneda'] = [
                    $data['Encabezado']['OtraMoneda'],
                ];
            }
            foreach ($data['Encabezado']['OtraMoneda'] as &$OtraMoneda) {
                if ($OtraMoneda['TpoMoneda'] === 'PESO CL') {
                    $OtraMoneda['TpoCambio'] = 0;
                }
            }
        }

        // Entregar los datos normalizados.
        return $data;
    }

}
