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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;
use Throwable;

/**
 * Clase para los parsers de datos de entrada de los documentos tributarios.
 */
#[Worker(name: 'parser', component: 'document', package: 'billing')]
class ParserWorker extends AbstractWorker implements ParserWorkerInterface
{
    use StrategiesAwareTrait;

    public function __construct(
        iterable $strategies = []
    ) {
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
            'default' => 'default.json',
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
                    'inputData' => '...',
                    'options' => [
                        'strategy' => 'default.json',
                    ],
                ],
            ],
        ],
    )]
    public function parse(DocumentBagInterface $bag): DocumentBagInterface
    {
        $options = $this->resolveOptions($bag->getParserOptions());
        $strategy = $this->getStrategy($options->getReader()->requireString('strategy'));

        assert($strategy instanceof ParserStrategyInterface);

        try {
            $parsedData = $strategy->parse($bag->getInputData());
        } catch (Throwable $e) {
            throw new ParserException(
                message: [
                    'Could not interpret the document: {detail}',
                    'detail' => $e,
                ],
                documentBag: $bag,
                previous: $e
            );
        }

        $bag->setParsedData($parsedData);

        return $bag;
    }
}
