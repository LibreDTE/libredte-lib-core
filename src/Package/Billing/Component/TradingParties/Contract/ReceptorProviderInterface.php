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
 * Interfaz para el proveedor de datos de un receptor.
 */
interface ReceptorProviderInterface
{
    /**
     * Buscar los datos de un receptor a través de su RUT.
     *
     * @param int|string|ReceptorInterface $receptor Solo la parte entera del
     * RUT o el RUT completo o una instancia de ReceptorInterface.
     * @return ReceptorInterface Instancia del receptor. Puede ser una nueva con
     * los datos encontrados o una vacia sin datos más que el RUT o la que se
     * pasó con los datos actualizados si se encontraron.
     */
    public function retrieve(int|string|ReceptorInterface $receptor): ReceptorInterface;
}
