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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Contract;

/**
 * Interfaz para los identificadores de los participantes.
 *
 * Representa la identidad legal/fiscal de la organización.
 *
 * En el caso de Chile es el RUT.
 */
interface PartyIdentifierInterface
{
    /**
     * Obtiene el identificador completo del participante.
     *
     * El identificador completo está formado por el ID del esquema y el valor
     * del ID del participante unidos por ":".
     *
     * Ejemplo: `CL-RUT:76192083-9`.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Obtiene el ID del esquema del valor del ID del participante.
     *
     * Es la parte a la izquierda del ID.
     *
     * Ejemplo: `CL-RUT`.
     *
     * @return string
     */
    public function getSchemeId(): string;

    /**
     * Entrega el nombre del esquema del valor del ID del participante.
     *
     * Ejemplo: `Rol Único Tributario (RUT) de Chile`.
     *
     * @return string
     */
    public function getSchemeName(): string;

    /**
     * Obtiene el valor del ID del participante.
     *
     * Este valor del ID es único en el contexto del esquema del ID.
     *
     * Es la parte a la derecha del ID.
     *
     * Ejemplo: `76192083-9`.
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Entrega la autoridad que registra y lista los valores del ID de los
     * participantes en el contexto del esquema del ID.
     *
     * Ejemplo: `CL-SII`.
     *
     * @return string
     */
    public function getAuthority(): string;
}
