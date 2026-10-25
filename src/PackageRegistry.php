<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core;

use Derafu\Backbone\Contract\PackageRegistryInterface;
use Derafu\Backbone\Trait\PackageRegistryTrait;
use libredte\lib\Core\Package\Billing\Contract\BillingPackageInterface;
use libredte\lib\Core\Package\System\Contract\SystemPackageInterface;

/**
 * Registro de paquetes disponibles en LibreDTE.
 *
 * LibreDTE Lib Core contiene los siguientes paquetes:
 *
 * - `billing`: Paquete de facturación.
 * - `system`: Capacidades transversales que no son de negocio (ej.
 *   exploración genérica de repositorios de datos).
 *
 * La misión de LibreDTE es **Proveer facturación electrónica libre, accesible y
 * bien documentada para Chile**. Debido a lo anterior, esta biblioteca se
 * centra exclusivamente en proveer funcionalidades relacionadas con la
 * facturación electrónica de Chile y no tiene, ni tendrá, la intención de
 * ser una biblioteca completa para la administración de empresas de Chile.
 *
 * Por el motivo anterior, el paquete "billing" es el único paquete de
 * negocio disponible en LibreDTE Lib Core. El paquete "system" existe solo
 * para capacidades transversales, no de negocio.
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

    /**
     * Entrega el paquete "system".
     *
     * @return SystemPackageInterface
     */
    public function getSystemPackage(): SystemPackageInterface
    {
        $package = $this->getPackage('system');
        assert($package instanceof SystemPackageInterface);

        return $package;
    }
}
