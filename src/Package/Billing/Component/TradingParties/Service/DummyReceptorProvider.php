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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Service;

use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorProviderInterface;

/**
 * Proveedor por defecto de datos de un receptor.
 *
 * No agrega ni modifica datos: entrega el receptor tal como se recibió. Si se
 * recibe solo el RUT, crea el receptor con él. Una aplicación que necesite
 * completar los datos que falten de un receptor debe registrar su propio
 * proveedor.
 */
class DummyReceptorProvider implements ReceptorProviderInterface
{
    /**
     * Constructor del servicio y sus dependencias.
     *
     * @param ReceptorFactoryInterface $receptorFactory
     */
    public function __construct(
        private ReceptorFactoryInterface $receptorFactory
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(int|string|ReceptorInterface $receptor): ReceptorInterface
    {
        // Si se pasó el RUT se crea una instancia usando la factory.
        if (is_int($receptor) || is_string($receptor)) {
            $receptor = $this->receptorFactory->create(['rut' => $receptor]);
        }

        // Se entrega la instancia sin cambios.
        return $receptor;
    }
}
