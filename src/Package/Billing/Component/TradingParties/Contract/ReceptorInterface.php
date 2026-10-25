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
 * Interfaz para una entidad de receptor de documento tributario.
 */
interface ReceptorInterface extends ContribuyenteInterface, CorreoIntercambioDteInfoInterface
{
    /**
     * Asigna el código interno del receptor.
     *
     * @param string|null $codigo_interno Código interno del receptor.
     * @return static
     */
    public function setCodigoInterno(?string $codigo_interno): static;

    /**
     * Devuelve el código interno del receptor.
     *
     * @return string|null Código interno del receptor.
     */
    public function getCodigoInterno(): ?string;

    /**
     * Asigna la nacionalidad del receptor.
     *
     * @param string|null $nacionalidad Nacionalidad del receptor.
     * @return static
     */
    public function setNacionalidad(?string $nacionalidad): static;

    /**
     * Devuelve la nacionalidad del receptor.
     *
     * @return string|null Nacionalidad del receptor.
     */
    public function getNacionalidad(): ?string;

    /**
     * Asigna el identificador extranjero del receptor.
     *
     * @param string|null $identificador_extranjero Identificador extranjero del receptor.
     * @return static
     */
    public function setIdentificadorExtranjero(?string $identificador_extranjero): static;

    /**
     * Devuelve el identificador extranjero del receptor.
     *
     * @return string|null Identificador extranjero del receptor.
     */
    public function getIdentificadorExtranjero(): ?string;

    /**
     * Entrega los datos del receptor en un arreglo compatible con el XML del
     * DTE.
     *
     * @return array Arreglo con los datos del receptor en formato del DTE.
     */
    public function toDteArray(): array;
}
