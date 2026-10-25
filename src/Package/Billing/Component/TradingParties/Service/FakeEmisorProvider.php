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

use Derafu\Support\Hydrator;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorProviderInterface;

/**
 * Proveedor falso de datos de un emisor para pruebas.
 *
 * La aplicación que use LibreDTE debe implementar este servicio para resolver
 * los datos que falten de un emisor al emitir un documento.
 */
class FakeEmisorProvider implements EmisorProviderInterface
{
    /**
     * Constructor del servicio y sus dependencias.
     *
     * @param EmisorFactoryInterface $emisorFactory
     */
    public function __construct(private EmisorFactoryInterface $emisorFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(int|string|EmisorInterface $emisor): EmisorInterface
    {
        // Si se pasó el RUT se crea una instancia del emisor usando la factory.
        if (is_int($emisor) || is_string($emisor)) {
            $emisor = $this->emisorFactory->create(['rut' => $emisor]);
        }

        // El emisor se estandariza como SASCO SPA.
        $emisor = Hydrator::hydrate($emisor, [
            'rut' => 76192083,
            'dv' => '9',
            'razon_social' => 'SASCO SpA',
            'giro' => 'Tecnología, Informática y Telecomunicaciones',
            'actividad_economica' => 726000,
            'telefono' => '+56 2 12345678',
            'email' => 'correo.sasco@example.com',
            'direccion' => 'DBG',
            'comuna' => 'Santa Cruz',
            'codigo_sucursal' => 123456,
            'vendedor' => 'libredte',
        ]);

        // Se entrega la instancia del emisor.
        return $emisor;
    }
}
