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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Contract;

use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;

/**
 * Interfaz para la clase que representa la información de autorización que da
 * el SII a un contribuyente para ser emisor de documentos tributarios
 * electrónicos.
 */
interface AutorizacionDteInterface
{
    /**
     * Obtiene la fecha de resolución de la autorización para emisión de DTE.
     *
     * @return string
     */
    public function getFechaResolucion(): string;

    /**
     * Obtiene el número de resolución de la autorización para emisión de DTE.
     *
     * @return int
     */
    public function getNumeroResolucion(): int;

    /**
     * Obtiene el ambiente para el que esta autorización es válida.
     *
     * @return SiiEnvironment
     */
    public function getEnvironment(): SiiEnvironment;

    /**
     * Entrega un arreglo con los índices: FchResol y NroResol.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
