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
 * Estados de un envío de AEC al Registro de Transferencias de Créditos (RTC)
 * del SII.
 *
 * Es el campo `ESTADO_ENVIO` que entrega la función `getEstEnvio` del servicio
 * `wsRPETCConsulta`.
 *
 * Referencia: https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf
 */
enum EstadoEnvioAec: string
{
    case UPL = 'UPL';
    case RCP = 'RCP';
    case RSC = 'RSC';
    case SOK = 'SOK';
    case RFS = 'RFS';
    case FSO = 'FSO';
    case RCR = 'RCR';
    case COK = 'COK';
    case VDC = 'VDC';
    case RDC = 'RDC';
    case VCS = 'VCS';
    case RCS = 'RCS';
    case EOK = 'EOK';
    case EAN = 'EAN';

    /**
     * Descripción del estado.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::UPL => 'Envío en proceso de upload',
            self::RCP => 'Envío recibido',
            self::RSC => 'Rechazado por error en schema',
            self::SOK => 'Schema validado',
            self::RFS => 'Rechazado por firma de sobre',
            self::FSO => 'Firma de sobre validada',
            self::RCR => 'Error en carátula',
            self::COK => 'Carátula OK',
            self::VDC => 'Validación de documento',
            self::RDC => 'Documento inválido',
            self::VCS => 'Validación de cesiones',
            self::RCS => 'Cesión inválida',
            self::EOK => 'Envío aceptado',
            self::EAN => 'Envío anulado',
        };
    }

    /**
     * Indica si el estado es de un envío rechazado.
     */
    public function isError(): bool
    {
        return match ($this) {
            self::RSC,
            self::RFS,
            self::RCR,
            self::RDC,
            self::RCS => true,
            default => false,
        };
    }

    /**
     * Indica si el estado es final: el SII ya no seguirá procesando el envío.
     *
     * Los demás estados son intermedios y la consulta se debe repetir más
     * adelante.
     */
    public function isFinal(): bool
    {
        return $this->isError() || $this === self::EOK || $this === self::EAN;
    }

    /**
     * Tipo de estado, para presentarlo (por ejemplo, con un color).
     */
    public function getStatusType(): StatusInterface
    {
        return match (true) {
            $this === self::EOK => Status::Success,
            $this === self::EAN => Status::Warning,
            $this->isError() => Status::Danger,
            default => Status::Info,
        };
    }
}
