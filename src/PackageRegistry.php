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

namespace libredte\lib\Core;

use Derafu\Backbone\Contract\PackageRegistryInterface;
use Derafu\Backbone\Trait\PackageRegistryTrait;
use libredte\lib\Core\Package\Billing\Contract\BillingPackageInterface;

/**
 * Registro de paquetes disponibles en LibreDTE.
 *
 * LibreDTE Lib Core contiene los siguientes paquetes:
 *
 * - `billing`: Paquete de facturación.
 *
 * La misión de LibreDTE es **Proveer facturación electrónica libre, accesible y bien documentada para Chile**. Debido a lo anterior, esta biblioteca se centra exclusivamente en proveer funcionalidades relacionadas con la facturación electrónica de Chile.
 */
class PackageRegistry implements PackageRegistryInterface
{
    use PackageRegistryTrait;

    /**
     * Entrega el paquete "billing".
     *
     * @return BillingPackageInterface
     */
    public function getBillingPackage(): BillingPackageInterface
    {
        $package = $this->getPackage('billing');
        assert($package instanceof BillingPackageInterface);

        return $package;
    }
}
