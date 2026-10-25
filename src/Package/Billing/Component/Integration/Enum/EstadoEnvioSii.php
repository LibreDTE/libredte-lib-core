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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Enum;

use Derafu\Enum\Contract\StatusInterface;
use Derafu\Enum\Status;

/**
 * Estado del envío de un DTE al SII.
 *
 * Almacenado como CHAR(1) en la base de datos para minimizar el espacio en
 * tablas con decenas o cientos de millones de filas.
 *
 * Mapa de códigos SII → este enum:
 *   - EPR (sin rechazados ni reparos) → ACEPTADO
 *   - RLV, RPR                        → REPARO
 *   - RSC, RCH, RPT, RFR, VOF, RCT   → RECHAZADO
 *   - Códigos no finales              → ENVIADO
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
     * Aceptado con reparos (RLV o RPR en el SII).
     */
    case REPARO = 'R';

    /**
     * Rechazado por el SII.
     */
    case RECHAZADO = 'X';

    /**
     * Códigos SII que representan rechazo definitivo del documento.
     */
    private const RECHAZADOS = ['RSC', 'RCH', 'RPT', 'RFR', 'VOF', 'RCT'];

    /**
     * Códigos SII de estados no finales (envío en proceso o con error
     * recuperable).
     */
    private const NO_FINALES = [
        '001', '002', '003', '004', '005', '007', '106', '107',
        '-11', '-8',
        'REC', 'SOK', 'FOK', 'PDR', 'PRD', 'CRT',
    ];

    /**
     * Indica si el estado es definitivo (no se esperan más cambios del SII).
     */
    public function isFinal(): bool
    {
        return match ($this) {
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

    /**
     * Indica si la glosa SII debe persistirse para este estado.
     *
     * Solo se almacena para RECHAZADO; para ACEPTADO y REPARO el texto es
     * siempre el mismo y se deriva en el getter de la entidad.
     */
    public function shouldStoreGlosa(): bool
    {
        return $this === self::RECHAZADO;
    }

    /**
     * Indica si el detalle SII debe persistirse para este estado.
     *
     * Se almacena para RECHAZADO y REPARO. Para ACEPTADO no hay detalle
     * relevante; para ENVIADO aún no hay revisión.
     */
    public function shouldStoreDetalle(): bool
    {
        return $this === self::RECHAZADO || $this === self::REPARO;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ENVIADO   => 'Enviado',
            self::ACEPTADO  => 'Aceptado',
            self::REPARO    => 'Con reparos',
            self::RECHAZADO => 'Rechazado',
        };
    }

    public function getStatusType(): StatusInterface
    {
        return match ($this) {
            self::ENVIADO   => Status::Secondary,
            self::ACEPTADO  => Status::Success,
            self::REPARO    => Status::Warning,
            self::RECHAZADO => Status::Danger,
        };
    }

    /**
     * Construye el enum a partir del código de 3 caracteres devuelto por el
     * SII (ej: 'RCH', 'EPR', 'RFR').
     */
    public static function tryFromSiiCodigo(string $codigo): ?self
    {
        return match (true) {
            in_array($codigo, ['EPR', 'LOK'], true)        => self::ACEPTADO,
            in_array($codigo, ['RLV', 'RPR'], true)        => self::REPARO,
            in_array($codigo, self::RECHAZADOS, true)      => self::RECHAZADO,
            in_array($codigo, self::NO_FINALES, true)      => self::ENVIADO,
            default                                        => null,
        };
    }

    /**
     * Construye el enum a partir de la glosa completa del SII
     * (ej: 'RCH - DTE Rechazado', 'EPR - Envío Procesado').
     *
     * Extrae el código antes del primer espacio y delega a tryFromSiiCodigo().
     */
    public static function tryFromGlosa(string $glosa): ?self
    {
        return self::tryFromSiiCodigo(explode(' ', $glosa, 2)[0]);
    }
}
