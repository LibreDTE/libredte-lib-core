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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker;

use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use Throwable;

/**
 * Worker "billing.exchange.sender".
 */
class SenderWorker extends AbstractExchangeWorker implements SenderWorkerInterface
{
    /**
     * Esquema de las opciones.
     *
     * @var array<string,array|bool>
     */
    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
            'default' => 'email.smtp',
        ],
        'transport' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function send(ExchangeBagInterface $bag): array
    {
        $options = $this->resolveOptions($bag->getOptions());
        $strategy = $this->getStrategy($options->get('strategy'));

        assert($strategy instanceof SenderStrategyInterface);

        try {
            $results = $strategy->send($bag);
        } catch (Throwable $e) {
            throw new ExchangeException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        return $results;
    }
}
