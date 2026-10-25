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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Support;

use Derafu\Config\Contract\OptionsInterface;
use Derafu\Config\Trait\OptionsAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeResultInterface;

/**
 * Clase que representa una bolsa con sobres con documentos para ser
 * intercambiada.
 *
 * Una bolsa podrá contener sobres de diferentes emisores o receptores, pero los
 * documentos dentro de cada sobre serán del mismo emisor y receptor.
 */
class ExchangeBag implements ExchangeBagInterface
{
    use OptionsAwareTrait;

    /**
     * Reglas de esquema de las opciones del intercambio de documentos.
     *
     * Acá solo se indicarán los índices que deben pueden existir en las
     * opciones. No se define el esquema de cada opción pues cada clase que
     * utilice estas opciones deberá resolver y validar sus propias opciones.
     *
     * @var array
     */
    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
        ],
        'transport' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * Listado de sobres que se están intercambiando en este lote.
     *
     * @var array<string, EnvelopeInterface>
     */
    private array $envelopes = [];

    /**
     * Listado con los resultados del intercambio.
     *
     * @var array<string, ExchangeResultInterface>
     */
    private array $results = [];

    /**
     * Constructor de la bolsa de intercambio.
     *
     * @param array $options
     */
    public function __construct(OptionsInterface|array $options = [])
    {
        $this->setOptions($options);
    }

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
    public function addEnvelope(EnvelopeInterface $envelope): static
    {
        $this->envelopes[$envelope->getBusinessMessageID()] = $envelope;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvelopes(): array
    {
        return array_values($this->envelopes);
    }

    /**
     * {@inheritDoc}
     */
    public function hasEnvelopes(): bool
    {
        return !empty($this->envelopes);
    }

    /**
     * {@inheritDoc}
     */
    public function getResults(): array
    {
        return array_values($this->results);
    }

    /**
     * {@inheritDoc}
     */
    public function addResult(ExchangeResultInterface $result): static
    {
        $envelope = $result->getEnvelope();

        $this->results[$envelope->getBusinessMessageID()] = $result;

        if (!isset($this->envelopes[$envelope->getBusinessMessageID()])) {
            $this->envelopes[$envelope->getBusinessMessageID()] = $envelope;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>&array{
     *     envelopes: array<array-key, array<array-key, mixed>&array{
     *         documents: array<array-key, array<array-key, mixed>&array{
     *             id: string,
     *             content: string,
     *             attachments: array<array-key, array{
     *                 data: string,
     *                 name: string|null,
     *                 type: string,
     *                 size: int
     *             }>,
     *             metadata: array<array-key, mixed>
     *         }>,
     *         metadata: array<array-key, mixed>
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'options' => $this->getOptions()->all(),
            'envelopes' => array_map(
                fn (EnvelopeInterface $envelope) => $envelope->toArray(),
                $this->getEnvelopes(),
            ),
            'results' => array_map(
                fn (ExchangeResultInterface $result) => $result->toArray(),
                $this->getResults(),
            ),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'options' => $this->getOptions()->all(),
            'envelopes' => array_map(
                fn (EnvelopeInterface $envelope) => $envelope->jsonSerialize(),
                $this->getEnvelopes(),
            ),
            'results' => array_map(
                fn (ExchangeResultInterface $result) => $result->jsonSerialize(),
                $this->getResults(),
            ),
        ];
    }
}
