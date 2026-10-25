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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Handler que realiza el envío de los documentos al SII.
 *
 * El envío al SII solo se realiza si corresponde enviar al SII lo que la bolsa
 * con los sobres tiene y solo si están los datos/opciones para hacerlo.
 */
#[Handler(name: 'sii_sender', worker: 'sender', component: 'exchange', package: 'billing')]
class SiiSenderHandler extends AbstractSenderHandler implements ExchangeHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    protected function shouldProcess(EnvelopeInterface $envelope): bool
    {
        // Si el proceso asociado al sobre no es de reporte fiscal no se puede
        // procesar el sobre.
        if ($envelope->getProcess() !== ProcessType::REPORTING) {
            return false;
        }

        // Si los documentos que están en el sobre no son válidos para reportes
        // no se procesa.
        if (!$envelope->getDocumentType()->isValidForProcess(ProcessType::REPORTING)) {
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
        // Si no hay firma electrónica no se podrá hacer el envío al SII.
        if (!$envelope->getMetadata()->get('certificate')) {
            return false;
        }

        // Si el que envía el sobre no está definido o no es una instancia de
        // EmisorInterface no se puede hacer el envío.
        $sender = $envelope->getMetadata()->get('sender');
        if (!$sender || !($sender instanceof EmisorInterface)) {
            return false;
        }

        // Si el que envía el sobre no tiene asignados los datos de autorización
        // de DTE (fecha y número resolución) no se puede hacer el envío.
        if (!$sender->getAutorizacionDte()) {
            return false;
        }

        // En cualquier otro caso indicar que están los datos mínimos.
        return true;
    }
}
