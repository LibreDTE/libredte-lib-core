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
 * Interfaz para que los emisores y receptores implementen los métodos para
 * utilizar el correo de intercambio de DTE.
 */
interface CorreoIntercambioDteInfoInterface
{
    /**
     * Establece el correo electrónico de intercambio de DTE del contribuyente.
     *
     * @param string|null $correo
     * @return static
     */
    public function setCorreoIntercambioDte(?string $correo): static;

    /**
     * Obtiene el correo electrónico de intercambio de DTE del contribuyente.
     *
     * @return string|null
     */
    public function getCorreoIntercambioDte(): ?string;
}
