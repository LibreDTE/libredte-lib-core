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
 * Interfaz para que los emisores implementes los métodos necesarios para poder
 * interactuar con los datos de autorización de emisión de DTE en el SII.
 */
interface AutorizacionDteInfoInterface
{
    /**
     * Asigna el ambiente, fecha y número de resolución que autoriza al
     * contribuyente a ser facturador electrónico en dicho ambiente del SII.
     *
     * @param AutorizacionDteInterface $autorizacionDte Información de la autorización.
     * @return static
     */
    public function setAutorizacionDte(AutorizacionDteInterface $autorizacionDte): static;

    /**
     * Obtiene el ambiente, fecha y número de resolución que autoriza al
     * contribuyente a ser facturador electrónico en dicho ambiente del SII.
     *
     * @return AutorizacionDteInterface|null Información de la autorización o `null` si
     * no está definida.
     */
    public function getAutorizacionDte(): ?AutorizacionDteInterface;
}
