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

use JsonSerializable;

/**
 * Interfaz para una entidad de contribuyente.
 */
interface ContribuyenteInterface extends JsonSerializable
{
    /**
     * Devuelve solo la parte numérica del RUT del contribuyente.
     *
     * @return int Parte numérica del RUT del contribuyente.
     */
    public function getRutAsInt(): int;

    /**
     * Devuelve el RUT completo (incluyendo el DV) del contribuyente.
     *
     * @return string RUT completo del contribuyente.
     */
    public function getRut(): string;

    /**
     * Asigna la razón social del contribuyente.
     *
     * @param string|null $razon_social
     * @return static
     */
    public function setRazonSocial(?string $razon_social): static;

    /**
     * Devuelve la razón social del contribuyente.
     *
     * Si no hay razón social, devuelve el RUT.
     *
     * @return string Razón social o RUT.
     */
    public function getRazonSocial(): string;

    /**
     * Asigna el giro comercial del contribuyente.
     *
     * @param string|null $giro
     * @return static
     */
    public function setGiro(?string $giro): static;

    /**
     * Devuelve el giro comercial del contribuyente.
     *
     * @return string|null Giro del contribuyente o null si no se especifica.
     */
    public function getGiro(): ?string;

    /**
     * Asigna el código de actividad económica del contribuyente.
     *
     * @param int|null $actividad_economica
     * @return static
     */
    public function setActividadEconomica(?int $actividad_economica): static;

    /**
     * Devuelve el código de actividad económica del contribuyente.
     *
     * @return int|null Código de actividad económica o null.
     */
    public function getActividadEconomica(): ?int;

    /**
     * Asigna el teléfono del contribuyente.
     *
     * @param string|null $telefono
     * @return static
     */
    public function setTelefono(?string $telefono): static;

    /**
     * Devuelve el teléfono del contribuyente.
     *
     * @return string|null Teléfono del contribuyente o null.
     */
    public function getTelefono(): ?string;

    /**
     * Asigna el correo electrónico del contribuyente.
     *
     * @param string|null $email
     * @return static
     */
    public function setEmail(?string $email): static;

    /**
     * Devuelve el correo electrónico del contribuyente.
     *
     * @return string|null Correo electrónico del contribuyente o null.
     */
    public function getEmail(): ?string;

    /**
     * Asigna la dirección del contribuyente.
     *
     * @param string|null $direccion
     * @return static
     */
    public function setDireccion(?string $direccion): static;

    /**
     * Devuelve la dirección del contribuyente.
     *
     * @return string|null Dirección del contribuyente o null.
     */
    public function getDireccion(): ?string;

    /**
     * Asigna la comuna del contribuyente.
     *
     * @param string|null $comuna
     * @return static
     */
    public function setComuna(?string $comuna): static;

    /**
     * Devuelve la comuna del contribuyente.
     *
     * @return string|null Comuna del contribuyente o null.
     */
    public function getComuna(): ?string;

    /**
     * Asigna la ciudad del contribuyente.
     *
     * @param string|null $ciudad
     * @return static
     */
    public function setCiudad(?string $ciudad): static;

    /**
     * Devuelve la ciudad del contribuyente.
     *
     * @return string|null Ciudad del contribuyente o null.
     */
    public function getCiudad(): ?string;

    /**
     * Entrega los datos del contribuyente en un arreglo.
     *
     * @return array<array-key, mixed> Arreglo con los datos del contribuyente.
     */
    public function toArray(): array;
}
