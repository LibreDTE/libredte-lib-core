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

/**
 * Interfaz para el proveedor de datos de un emisor.
 */
interface EmisorProviderInterface
{
    /**
     * Buscar los datos de un emisor a través de su RUT.
     *
     * @param int|string|EmisorInterface $emisor Solo la parte entera del RUT o
     * el RUT completo o una instancia de EmisorInterface.
     * @return EmisorInterface Instancia del emisor. Puede ser una nueva con los
     * datos encontrados o una vacia sin datos más que el RUT o la que se pasó
     * con los datos actualizados si se encontraron.
     */
    public function retrieve(int|string|EmisorInterface $emisor): EmisorInterface;
}
