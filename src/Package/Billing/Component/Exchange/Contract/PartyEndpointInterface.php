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
 * Interfaz para los puntos técnicos de recepción de documentos de los
 * participantes del intercambio.
 *
 * Representa el punto técnico de recepción de los documentos.
 *
 * En el caso de Chile oficialmente es el "correo de contacto empresas" definido
 * en el SII.
 */
interface PartyEndpointInterface
{
    /**
     * Obtiene el identificador completo del punto técnico de recepción.
     *
     * El identificador completo está formado por el ID del esquema y el valor
     * del punto técnico de recepción unidos por ":".
     *
     * Ejemplo: `EMAIL:correo-sasco@example.com`.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Obtiene el ID del esquema del valor del punto técnico de recepción.
     *
     * Es la parte a la izquierda del ID.
     *
     * Ejemplo: `EMAIL`.
     *
     * @return string
     */
    public function getSchemeId(): string;

    /**
     * Entrega el nombre del esquema del valor del ID del punto técnico de
     * recepción.
     *
     * Ejemplo: `Correo electrónico`.
     *
     * @return string
     */
    public function getSchemeName(): string;

    /**
     * Obtiene el valor del punto técnico de recepción.
     *
     * Este valor es único en el contexto del esquema del ID.
     *
     * Es la parte a la derecha del ID.
     *
     * Ejemplo: `correo-sasco@example.com`.
     *
     * @return string
     */
    public function getValue(): string;
}
