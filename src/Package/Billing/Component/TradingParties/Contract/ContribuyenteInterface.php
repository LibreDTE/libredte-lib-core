<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Contract;

/**
 * Interfaz para una entidad de contribuyente.
 */
interface ContribuyenteInterface
{
    /**
     * Devuelve solo la parte numérica del RUT del contribuyente.
     *
     * @return integer Parte numérica del RUT del contribuyente.
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
}
