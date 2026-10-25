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

namespace libredte\lib\Core\Package\Billing\Component\Exchange;

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\DocumentResponseWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderWorkerInterface;

/**
 * Componente "billing.exchange".
 *
 * Este componente se encarga de la gestión de intercambio de documentos tributarios.
 */
#[Component(name: 'exchange', package: 'billing')]
class ExchangeComponent extends AbstractComponent implements ExchangeComponentInterface
{
    /**
     * Constructor del componente con sus dependencias.
     *
     * @param ReceiverWorkerInterface $receiverWorker
     * @param SenderWorkerInterface $senderWorker
     */
    public function __construct(
        private ReceiverWorkerInterface $receiverWorker,
        private SenderWorkerInterface $senderWorker,
        private DocumentResponseWorkerInterface $documentResponseWorker,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'receiver' => $this->receiverWorker,
            'sender' => $this->senderWorker,
            'document_response' => $this->documentResponseWorker,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getReceiverWorker(): ReceiverWorkerInterface
    {
        return $this->receiverWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getSenderWorker(): SenderWorkerInterface
    {
        return $this->senderWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentResponseWorker(): DocumentResponseWorkerInterface
    {
        return $this->documentResponseWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function receive(ExchangeBagInterface $bag): array
    {
        return $this->receiverWorker->receive($bag);
    }

    /**
     * {@inheritDoc}
     */
    public function send(ExchangeBagInterface $bag): array
    {
        return $this->senderWorker->send($bag);
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ExchangeBagInterface $bag): array
    {
        if (!$bag->hasEnvelopes()) {
            return $this->receiverWorker->handle($bag);
        } else {
            return $this->senderWorker->handle($bag);
        }
    }
}
