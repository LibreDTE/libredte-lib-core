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

namespace libredte\lib\Core\Package\Billing\Component\Exchange;

use Derafu\Lib\Core\Foundation\Abstract\AbstractComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderWorkerInterface;

/**
 * Componente "billing.exchange".
 */
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
        private SenderWorkerInterface $senderWorker
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
