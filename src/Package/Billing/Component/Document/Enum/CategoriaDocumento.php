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
 * Enum de la categoría de documentos.
 */
enum CategoriaDocumento: string
{
    case TRIBUTARIO = 'T';
    case INFORMATIVO = 'I';
    case REFERENCIA = 'R';

    /**
     * Mapa con los códigos de la categoría y su nombre.
     */
    private const NOMBRES = [
        self::TRIBUTARIO->value => 'Tributario oficial del SII.',
        self::INFORMATIVO->value => 'Informativo oficial del SII.',
        self::REFERENCIA->value => 'Referencia no oficial del SII.',
    ];

    /**
     * Entrega el código de la categoría.
     *
     * @return string
     */
    public function getCodigo(): string
    {
        return $this->value;
    }

    /**
     * Entrega el nombre de la categoría.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return self::NOMBRES[$this->value];
    }
}
