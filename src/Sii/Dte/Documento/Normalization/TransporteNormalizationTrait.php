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

/**
 * Reglas de normalización para datos de transporte.
 */
trait TransporteNormalizationTrait
{
    /**
     * Normaliza los datos de transporte.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    protected function applyTransporteNormalization(array $data): array
    {
        if (!empty($data['Encabezado']['Transporte'])) {
            $data['Encabezado']['Transporte'] = array_merge([
                'Patente' => false,
                'RUTTrans' => false,
                'Chofer' => false,
                'DirDest' => false,
                'CmnaDest' => false,
                'CiudadDest' => false,
                'Aduana' => false,
            ], $data['Encabezado']['Transporte']);
        }

        // Entregar los datos normalizados.
        return $data;
    }
}
