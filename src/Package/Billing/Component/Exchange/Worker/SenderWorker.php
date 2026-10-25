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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker;

use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\HandlersAwareTrait;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\ProcessType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use Throwable;

/**
 * Worker "billing.exchange.sender".
 */
#[Worker(name: 'sender', component: 'exchange', package: 'billing')]
class SenderWorker extends AbstractExchangeWorker implements SenderWorkerInterface
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
    public function getOptionsSchema(): array
    {
        return $this->optionsSchema;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'bag' => [
                'example' => [
                    'options' => [
                        'strategy' => 'email.smtp',
                        'transport' => [
                            'username' => '',
                            'password' => '',
                        ],
                    ],
                    'envelopes' => [
                        [
                            'sender' => [
                                'identifier' => [
                                    'value' => '76192083-9',
                                    'schemeId' => 'CL-RUT',
                                ],
                                'endpoints' => [
                                    [
                                        'value' => 'emisor@example.com',
                                        'schemeId' => 'EMAIL',
                                    ],
                                ],
                            ],
                            'receiver' => [
                                'identifier' => [
                                    'value' => '66666666-6',
                                    'schemeId' => 'CL-RUT',
                                ],
                                'endpoints' => [
                                    [
                                        'value' => 'cliente@example.com',
                                        'schemeId' => 'EMAIL',
                                    ],
                                ],
                            ],
                            'documentType' => DocumentType::INVOICE,
                            'process' => ProcessType::BILLING,
                            'businessMessageID' => 'envio-001',
                            'documents' => [
                                [
                                    'type' => DocumentType::INVOICE,
                                    'content' => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function send(ExchangeBagInterface $bag): array
    {
        $options = $this->resolveOptions($bag->getOptions());
        $strategy = $this->getStrategy($options->getReader()->requireString('strategy'));

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
