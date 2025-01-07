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

/**
 * Reglas de normalización para datos de transporte.
 */
trait NormalizeTransporteTrait
{
    /**
     * Normaliza los datos de transporte.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     */
    protected function normalizeTransporte(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

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

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}
