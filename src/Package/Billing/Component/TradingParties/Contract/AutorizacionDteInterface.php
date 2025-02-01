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

use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;

/**
 * Interfaz para la clase que representa la información de autorización que da
 * el SII a un contribuyente para ser emisor de documentos tributarios
 * electrónicos.
 */
interface AutorizacionDteInterface
{
    /**
     * Obtiene la fecha de resolución de la autorización para emisión de DTE.
     *
     * @return string
     */
    public function getFechaResolucion(): string;

    /**
     * Obtiene el número de resolución de la autorización para emisión de DTE.
     *
     * @return integer
     */
    public function getNumeroResolucion(): int;

    /**
     * Obtiene el ambiente para el que esta autorización es válida.
     *
     * @return SiiAmbiente
     */
    public function getAmbiente(): SiiAmbiente;

    /**
     * Entrega un arreglo con los índices: FchResol y NroResol.
     *
     * @return array
     */
    public function toArray(): array;
}
