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

use Derafu\Backbone\Attribute\ApiResource;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\HandlersAwareTrait;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use Throwable;

/**
 * Worker "billing.exchange.receiver".
 */
#[Worker(name: 'receiver', component: 'exchange', package: 'billing')]
class ReceiverWorker extends AbstractExchangeWorker implements ReceiverWorkerInterface
{
    use HandlersAwareTrait;
    use StrategiesAwareTrait;

    public function __construct(
        iterable $handlers = [],
        iterable $strategies = []
    ) {
        $this->setHandlers($handlers);
        $this->setStrategies($strategies);
    }

    /**
     * Esquema de las opciones.
     *
     * @var array<string,array|bool>
     */
    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
            'default' => 'email.imap',
        ],
        'transport' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    #[ApiResource()]
    public function receive(ExchangeBagInterface $bag): array
    {
        $options = $this->resolveOptions($bag->getOptions());
        $strategy = $this->getStrategy($options->get('strategy'));

        assert($strategy instanceof ReceiverStrategyInterface);

        try {
            $results = $strategy->receive($bag);
        } catch (Throwable $e) {
            throw new ExchangeException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        return $results;
    }
}
