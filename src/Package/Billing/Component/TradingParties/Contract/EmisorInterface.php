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
 * Interfaz para una entidad de emisor de documento tributario.
 */
interface EmisorInterface extends ContribuyenteInterface, AutorizacionDteInfoInterface, CorreoIntercambioDteInfoInterface
{
    /**
     * Entrega el código de la sucursal asignado por el SII al emisor.
     *
     * @return integer|null
     */
    public function getCodigoSucursal(): ?int;

    /**
     * Entrega el nombre o código del vendedor que está representando al emisor.
     *
     * @return string|null
     */
    public function getVendedor(): ?string;

    /**
     * Asigna los datos del logo del emisor.
     *
     * @param string $logo
     * @return static
     */
    public function setLogo(string $logo): static;

    /**
     * Obtiene los datos del logo del emisor.
     *
     * @return string|null
     */
    public function getLogo(): ?string;
}
