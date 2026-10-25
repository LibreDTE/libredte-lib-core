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
 * Interfaz para una entidad de emisor de documento tributario.
 */
interface EmisorInterface extends ContribuyenteInterface, AutorizacionDteInfoInterface, CorreoIntercambioDteInfoInterface
{
    /**
     * Agrega una actividad económica al contribuyente.
     *
     * @param int $actividad_economica
     * @return static
     */
    public function addActividadEconomica(int $actividad_economica): static;

    /**
     * Asigna las actividades económicas del contribuyente.
     *
     * @param int[] $actividades_economicas
     * @return static
     */
    public function setActividadesEconomicas(array $actividades_economicas): static;

    /**
     * Devuelve las actividades económicas del contribuyente.
     *
     * @return int[] Actividades económicas del contribuyente.
     */
    public function getActividadesEconomicas(): array;

    /**
     * Agrega un teléfono al contribuyente.
     *
     * @param string $telefono
     * @return static
     */
    public function addTelefono(string $telefono): static;

    /**
     * Asigna los teléfonos del contribuyente.
     *
     * @param string[] $telefonos
     * @return static
     */
    public function setTelefonos(array $telefonos): static;

    /**
     * Devuelve los teléfonos del contribuyente.
     *
     * @return string[] Teléfonos del contribuyente.
     */
    public function getTelefonos(): array;

    /**
     * Asigna el nombre de la sucursal del emisor.
     *
     * @param string|null $sucursal
     * @return static
     */
    public function setSucursal(?string $sucursal): static;

    /**
     * Entrega el nombre de la sucursal del emisor.
     *
     * @return string|null
     */
    public function getSucursal(): ?string;

    /**
     * Asigna el código de la sucursal asignado por el SII al emisor.
     *
     * @param int|null $codigo_sucursal
     * @return static
     */
    public function setCodigoSucursal(?int $codigo_sucursal): static;

    /**
     * Entrega el código de la sucursal asignado por el SII al emisor.
     *
     * @return int|null
     */
    public function getCodigoSucursal(): ?int;

    /**
     * Asigna el nombre o código del vendedor que está representando al emisor.
     *
     * @param string|null $vendedor
     * @return static
     */
    public function setVendedor(?string $vendedor): static;

    /**
     * Entrega el nombre o código del vendedor que está representando al emisor.
     *
     * @return string|null
     */
    public function getVendedor(): ?string;

    /**
     * Asigna los datos del logo del emisor.
     *
     * @param string|null $logo
     * @return static
     */
    public function setLogo(?string $logo): static;

    /**
     * Obtiene los datos del logo del emisor.
     *
     * @return string|null
     */
    public function getLogo(): ?string;

    /**
     * Entrega los datos del emisor en un arreglo compatible con el XML del DTE.
     *
     * @return array Arreglo con los datos del emisor en formato del DTE.
     */
    public function toDteArray(): array;
}
