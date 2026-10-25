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

/**
 * Acción del receptor sobre un DTE en el RCV del SII.
 *
 * Estos son los códigos que el receptor registra vía
 * `ingresarAceptacionReclamoDoc` y que el SII retorna en
 * `listarEventosHistDoc` como campo `codigo`.
 */
enum RcvAccion: string
{
    /**
     * Entrega Real de Mercaderías o Servicios.
     * Activa la cesibilidad del documento (Ley 19.983).
     */
    case ERM = 'ERM';

    /**
     * Acepta Contenido del Documento.
     */
    case ACD = 'ACD';

    /**
     * Reclamo al Contenido del Documento.
     */
    case RCD = 'RCD';

    /**
     * Reclamo por Falta Parcial de Mercaderías.
     */
    case RFP = 'RFP';

    /**
     * Reclamo por Falta Total de Mercaderías.
     */
    case RFT = 'RFT';

    public function label(): string
    {
        return match ($this) {
            self::ERM => 'Entrega Real de Mercaderías o Servicios (Ley 19.983)',
            self::ACD => 'Acepta Contenido del Documento',
            self::RCD => 'Reclamo al Contenido del Documento',
            self::RFP => 'Reclamo por Falta Parcial de Mercaderías',
            self::RFT => 'Reclamo por Falta Total de Mercaderías',
        };
    }

    public function isFavorableParaEmisor(): bool
    {
        return match ($this) {
            self::ERM, self::ACD => true,
            default              => false,
        };
    }

    public function isReclamo(): bool
    {
        return match ($this) {
            self::RCD, self::RFP, self::RFT => true,
            default                         => false,
        };
    }
}
