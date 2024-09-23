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

namespace libredte\lib\Core\Helper;

use Carbon\Carbon;
use Exception;

/**
 * Clase para trabajar con fecha en PHP.
 *
 * Extiende las funcionalidades de Carbon
 */
class Date extends Carbon
{
    /**
     * Valida si una fecha está en el formato Y-m-d y la convierte a un nuevo
     * formato.
     *
     * @param string $date
     * @return string|null
     */
    public static function validateAndConvert(
        string $date,
        string $format = 'd/m/Y'
    ): ?string {
        try {

            $carbonDate = self::createFromFormat('Y-m-d', $date);
            if (
                $carbonDate === false
                || $carbonDate->format('Y-m-d') !== $date
                || $carbonDate->getLastErrors()['error_count'] > 0
            ) {
                return null;
            }
            return $carbonDate->format($format);
        } catch (Exception $e) {
            return null;
        }
    }
}
