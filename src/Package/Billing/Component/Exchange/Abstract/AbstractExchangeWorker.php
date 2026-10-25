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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Abstract;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeHandlerInterface;

/**
 * Clase base para los workers de intercambio: ReceiverWorker y SenderWorker.
 */
abstract class AbstractExchangeWorker extends AbstractWorker implements WorkerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(ExchangeBagInterface $bag): array
    {
        // Obtener el listado de handlers que se deben utilizar con la bolsa.
        $handlers = $bag->getOptions()->get('handlers', null)
            ?? array_keys($this->getHandlers())
        ;
        $bag->getOptions()->clear('handlers');

        // Entregar la bolsa a cada handler para que maneje su transporte.
        $results = [];
        foreach ($handlers as $handlerCode) {
            $handler = $this->getHandler($handlerCode);
            assert($handler instanceof ExchangeHandlerInterface);
            $results = array_merge($results, $handler->handle($bag));
        }

        // Entregar el resultado del manejo de la bolsa.
        return $results;
    }
}
