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
 * Interfaz para que los emisores implementes los métodos necesarios para poder
 * interactuar con los datos de autorización de emisión de DTE en el SII.
 */
interface AutorizacionDteInfoInterface
{
    /**
     * Asigna el ambiente, fecha y número de resolución que autoriza al
     * contribuyente a ser facturador electrónico en dicho ambiente del SII.
     *
     * @param AutorizacionDteInterface|null $autorizacion_dte Información de la autorización.
     * @return static
     */
    public function setAutorizacionDte(?AutorizacionDteInterface $autorizacion_dte): static;

    /**
     * Obtiene el ambiente, fecha y número de resolución que autoriza al
     * contribuyente a ser facturador electrónico en dicho ambiente del SII.
     *
     * @return AutorizacionDteInterface|null Información de la autorización o `null` si
     * no está definida.
     */
    public function getAutorizacionDte(): ?AutorizacionDteInterface;
}
