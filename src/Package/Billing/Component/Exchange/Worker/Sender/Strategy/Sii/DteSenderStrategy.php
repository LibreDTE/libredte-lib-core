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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Strategy\Sii;

use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
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
        throw new ExchangeException(
            'Estrategia de envío sii.dte no está implementada.'
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
    public function canSend(ExchangeBagInterface|EnvelopeInterface $what): void
    {
        // Todo OK. Las validaciones que se requieren se hicieron en el handler
        // SiiSenderHandler.
    }
}
