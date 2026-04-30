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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Enum;

/**
 * Estado del envío de un DTE al SII.
 *
 * Almacenado como CHAR(1) en la base de datos para minimizar el espacio en
 * tablas con decenas o cientos de millones de filas.
 *
 * Mapa de estados del SII → este enum:
 *   - EPR sin rechazados ni reparos → ACEPTADO
 *   - EPR con reparos (RLV)         → REPARO
 *   - EPR con rechazados (RCH)      → RECHAZADO
 *   - Cualquier otro estado         → ENVIADO (respuesta intermedia)
 */
enum EstadoEnvioSii: string
{
    /**
     * Enviado al SII, esperando respuesta definitiva.
     */
    case ENVIADO = 'E';

    /**
     * Aceptado sin observaciones.
     */
    case ACEPTADO = 'A';

    /**
     * Aceptado con reparos leves (RLV en el SII).
     */
    case REPARO = 'R';

    /**
     * Rechazado por el SII (RCH en el SII).
     */
    case RECHAZADO = 'X';

    /**
     * Indica si el estado es definitivo (no se esperan más cambios del SII).
     */
    public function isFinal(): bool
    {
        return match($this) {
            self::ACEPTADO, self::REPARO, self::RECHAZADO => true,
            default => false,
        };
    }

    /**
     * Indica si el DTE fue aceptado por el SII (con o sin reparos).
     */
    public function isAceptado(): bool
    {
        return $this === self::ACEPTADO || $this === self::REPARO;
    }
}
