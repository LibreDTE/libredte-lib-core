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
