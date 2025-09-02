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

namespace libredte\lib\Core\Contract;

use Derafu\Backbone\Contract\ApplicationInterface as BackboneApplicationInterface;
use Derafu\Backbone\Contract\PackageRegistryInterface;

/**
 * Interfaz que define los métodos que debe implementar la aplicación de
 * LibreDTE.
 *
 * Esta interfaz es la mínima que se requiere. Donde cada aplicación deberá
 * implementar su propia versión de la clase Application. Por defecto la
 * biblioteca define una implementación de esta interfaz básica, pero que
 * permite usar todas las funcionalidades incluídas en la biblioteca.
 */
interface ApplicationInterface extends BackboneApplicationInterface
{
    public function getPackageRegistry(): PackageRegistryInterface;
}
