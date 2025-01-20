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
     * Devuelve la razón social del contribuyente.
     *
     * Si no hay razón social, devuelve el RUT.
     *
     * @return string Razón social o RUT.
     */
    public function getRazonSocial(): string;

    /**
     * Devuelve el giro comercial del contribuyente.
     *
     * @return string|null Giro del contribuyente o null si no se especifica.
     */
    public function getGiro(): ?string;

    /**
     * Devuelve el código de actividad económica del contribuyente.
     *
     * @return int|null Código de actividad económica o null.
     */
    public function getActividadEconomica(): ?int;

    /**
     * Devuelve el teléfono del contribuyente.
     *
     * @return string|null Teléfono del contribuyente o null.
     */
    public function getTelefono(): ?string;

    /**
     * Devuelve el correo electrónico del contribuyente.
     *
     * @return string|null Correo electrónico del contribuyente o null.
     */
    public function getEmail(): ?string;

    /**
     * Devuelve la dirección del contribuyente.
     *
     * @return string|null Dirección del contribuyente o null.
     */
    public function getDireccion(): ?string;

    /**
     * Devuelve la comuna del contribuyente.
     *
     * @return string|null Comuna del contribuyente o null.
     */
    public function getComuna(): ?string;
}
