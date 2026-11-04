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

use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorProviderInterface;

/**
 * Proveedor por defecto de datos de un emisor.
 *
 * No agrega ni modifica datos: entrega el emisor tal como se recibió. Si se
 * recibe solo el RUT, crea el emisor con él. Una aplicación que necesite
 * completar los datos que falten de un emisor debe registrar su propio
 * proveedor.
 */
class DummyEmisorProvider implements EmisorProviderInterface
{
    /**
     * Constructor del servicio y sus dependencias.
     *
     * @param EmisorFactoryInterface $emisorFactory
     */
    public function __construct(
        private EmisorFactoryInterface $emisorFactory
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(int|string|EmisorInterface $emisor): EmisorInterface
    {
        // Si se pasó el RUT se crea una instancia usando la factory.
        if (is_int($emisor) || is_string($emisor)) {
            $emisor = $this->emisorFactory->create(['rut' => $emisor]);
        }

        // Se entrega la instancia sin cambios.
        return $emisor;
    }
}
