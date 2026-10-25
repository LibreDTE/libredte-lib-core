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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorProviderInterface;

/**
 * Proveedor falso de datos de un receptor para pruebas.
 *
 * La aplicación que use LibreDTE debe implementar este servicio para resolver
 * los datos que falten de un receptor al emitir un documento.
 */
class FakeReceptorProvider implements ReceptorProviderInterface
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
        // Si se pasó el RUT se crea una instancia del receptor usando la
        // factory.
        if (is_int($receptor) || is_string($receptor)) {
            $receptor = $this->receptorFactory->create(['rut' => $receptor]);
        }

        // El emisor se estandariza como SII.
        $receptor = Hydrator::hydrate($receptor, [
            'rut' => 60803000,
            'dv' => 'K',
            'razon_social' => 'Servicio de Impuestos Internos',
            'giro' => 'Gobierno',
            'telefono' => '+56 2 32525575',
            'email' => 'correo.sii@example.com',
            'direccion' => 'Alonso Ovalle 680',
            'comuna' => 'Santiago',
        ]);

        // Se entrega la instancia del receptor.
        return $receptor;
    }
}
