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
 * Interfaz para una entidad de persona natural.
 */
interface PersonaNaturalInterface
{
    /**
     * Devuelve el RUN completo (incluyendo el DV) de la persona natural.
     *
     * @return string RUN completo de la persona natural.
     */
    public function getRun(): string;

    /**
     * Devuelve el nombre de la persona natural.
     *
     * Si no hay nombre, devuelve el RUN.
     *
     * @return string Nombre o RUN.
     */
    public function getNombre(): string;

    /**
     * Devuelve el correo electrónico de la persona natural.
     *
     * @return string|null Correo electrónico de la persona natural o null.
     */
    public function getEmail(): ?string;
}
