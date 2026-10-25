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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Strategy\Sii;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeResultInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeStatus;

/**
 * Envío de documentos tributarios (excepto boletas) al SII.
 */
#[Strategy(name: 'sii.dte', worker: 'sender', component: 'exchange', package: 'billing')]
class DteSenderStrategy extends AbstractStrategy implements SenderStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function send(ExchangeBagInterface $bag): array
    {
        // Procesar cada sobre por separado.
        foreach ($bag->getEnvelopes() as $envelope) {
            $result = $this->sendEnvelope($envelope);
            $bag->addResult($result);
        }

        // Entregar los resultados de la recepción de documentos.
        return $bag->getResults();
    }

    /**
     * Envía los documentos de un sobre al SII en el XML EnvioDTE.
     *
     * @param EnvelopeInterface $envelope Sobre con documentos a enviar.
     * @return ExchangeResultInterface Resultado del envío del sobre.
     */
    private function sendEnvelope(
        EnvelopeInterface $envelope
    ): ExchangeResultInterface {
        // TODO: Implementar el envío de los DTE al SII.
        // El envío de los DTE ya se hace con el LazyWorker, hay que unir eso
        // con esta estrategia.
        throw new ExchangeException(
            'The sii.dte send strategy is not implemented.'
        );

        // // Crear resultado del envío del sobre al SII.
        // $result = new ExchangeResult($envelope);
        // $result->addStatus(new ExchangeStatus(
        //     'sii.dte',
        //     //$message->getError()
        // ));

        // // Entregar resultado del envío.
        // return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function canSend(EnvelopeInterface $envelope, ExchangeBagInterface $bag): void
    {
        // Todo OK. Las validaciones que se requieren se hicieron en el handler
        // SiiSenderHandler.
    }
}
