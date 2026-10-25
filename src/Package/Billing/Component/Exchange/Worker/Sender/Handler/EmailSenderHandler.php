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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Handler;

use Derafu\Backbone\Attribute\Handler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractSenderHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeHandlerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\ProcessType;

/**
 * Handler que realiza el envío de los documentos mediante correo electrónico.
 *
 * El envío por correo solo se realiza si corresponde enviar lo que la bolsa con
 * los sobres tiene y solo si están los datos/opciones para hacerlo por correo.
 */
#[Handler(name: 'email_sender', worker: 'sender', component: 'exchange', package: 'billing')]
class EmailSenderHandler extends AbstractSenderHandler implements ExchangeHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    protected function shouldProcess(EnvelopeInterface $envelope): bool
    {
        // Revisar que los documentos que están en el sobre sean válidos para
        // los procesos que pueden enviar correo electrónico.
        $processes = [
            ProcessType::QUOTING,
            ProcessType::FULFILLMENT,
            ProcessType::BILLING,
            ProcessType::PAYMENT,
        ];
        $validForProcess = false;
        foreach ($processes as $process) {
            if ($envelope->getDocumentType()->isValidForProcess($process)) {
                $validForProcess = true;
                break;
            }
        }
        if (!$validForProcess) {
            return false;
        }

        // En cualquier otro caso indicar que se puede procesar el sobre.
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function hasRequiredData(EnvelopeInterface $envelope, ExchangeBagInterface $bag): bool
    {
        // Se necesitan los datos de transporte, comunes a todos los sobres
        // que se envíen en esta llamada.
        $transport = $bag->getOptions()->get('transport');
        $transport = is_array($transport) ? $transport : $transport?->all();
        if (empty($transport)) {
            return false;
        }

        // Nota: Cada estrategia deberá validar "qué" datos de transporte
        // específicos necesita en el método canSend().

        // En cualquier otro caso indicar que están los datos mínimos.
        return true;
    }
}
