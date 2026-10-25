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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Handler;

use Derafu\Backbone\Attribute\Handler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeHandlerInterface;

/**
 * Handler que realiza la recepción de los documentos mediante correo
 * electrónico.
 *
 * La recepción por correo solo se realiza si en la bolsa están los
 * datos/opciones para poder ejecutar una estrategia de recepción de correo.
 */
#[Handler(name: 'email_receiver', worker: 'receiver', component: 'exchange', package: 'billing')]
class EmailReceiverHandler extends AbstractReceiverHandler implements ExchangeHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    protected function hasRequiredData(ExchangeBagInterface $bag): bool
    {
        // Se necesitan los datos de transporte.
        $transport = $bag->getOptions()->get('transport');
        $transport = is_array($transport) ? $transport : $transport?->all();
        if (empty($transport)) {
            return false;
        }

        // Nota: Cada estrategia deberá validar "qué" datos de transporte
        // específicos necesita en el método canReceive().

        // En cualquier otro caso indicar que están los datos mínimos.
        return true;
    }
}
