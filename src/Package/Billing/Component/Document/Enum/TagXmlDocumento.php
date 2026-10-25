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

namespace libredte\lib\Core\Package\Billing\Component\Document\Enum;

/**
 * Enum del Tag XML donde va el documento (tag DTE).
 */
enum TagXmlDocumento: string
{
    case DOCUMENTO = 'Documento';
    case LIQUIDACION = 'Liquidacion';
    case EXPORTACIONES = 'Exportaciones';

    /**
     * Mapa con los códigos del tag XML y su nombre.
     */
    private const DESCRIPCIONES = [
        self::DOCUMENTO->value => 'Documento (todo menos DTE 43, 110, 111 ni 112)',
        self::LIQUIDACION->value => 'Liquidacion (DTE 43)',
        self::EXPORTACIONES->value => 'Exportaciones (DTE 110, 111 y 112)',
    ];

    /**
     * Entrega el nombre del tag XML.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return $this->value;
    }

    /**
     * Entrega la descripción del tag XML.
     *
     * @return string
     */
    public function getDescripcion(): string
    {
        return self::DESCRIPCIONES[$this->value];
    }
}
