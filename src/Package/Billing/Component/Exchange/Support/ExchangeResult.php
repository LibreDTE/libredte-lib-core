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

use Derafu\Container\Bag;
use Derafu\Container\Contract\BagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeResultInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeStatusInterface;

/**
 * Clase que representan un resultado de intercambio de sobres.
 *
 * Este resultado está asociado a un único sobre, pero puede tener como
 * resultado múltiples estados. Loanterior ocurre porque un sobre puede haber
 * sido procesado por más de una estrategia y cada una asigna un estado.
 */
class ExchangeResult implements ExchangeResultInterface
{
    /**
     * Sobre al que está asociado el resultado.
     *
     * @var EnvelopeInterface
     */
    private EnvelopeInterface $envelope;

    /**
     * Listado de resultados de las estrategias que procesaron el sobre.
     *
     * Puede quedar vacío: por ejemplo, `ImapReceiverStrategy` crea un
     * `ExchangeResult` por cada sobre recibido sin llamar a `addStatus()`,
     * pues la recepción en sí no produce un estado de una estrategia.
     *
     * @var array<string, ExchangeStatusInterface>
     */
    private array $statuses = [];

    /**
     * Metadatos del resultado.
     *
     * @var BagInterface
     */
    private BagInterface $metadata;

    /**
     * Constructor del resultado del intercambio de un sobre.
     *
     * @param EnvelopeInterface $envelope
     * @param BagInterface|array $metadata
     */
    public function __construct(
        EnvelopeInterface $envelope,
        BagInterface|array $metadata = []
    ) {
        $this->envelope = $envelope;
        $this->setMetadata($metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvelope(): EnvelopeInterface
    {
        return $this->envelope;
    }

    /**
     * {@inheritDoc}
     */
    public function getStrategies(): array
    {
        return array_keys($this->statuses);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatuses(): array
    {
        return array_values($this->statuses);
    }

    /**
     * {@inheritDoc}
     */
    public function addStatus(ExchangeStatusInterface $status): static
    {
        $this->statuses[$status->getStrategy()] = $status;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMetadata(BagInterface|array $metadata): static
    {
        $this->metadata = is_array($metadata)
            ? new Bag($metadata)
            : $metadata
        ;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addMetadata(string $key, mixed $value): static
    {
        $this->metadata->set($key, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata(): BagInterface
    {
        return $this->metadata;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'envelope' => $this->envelope->getBusinessMessageID(),
            'statuses' => array_map(
                fn (ExchangeStatusInterface $status) => $status->toArray(),
                $this->getStatuses(),
            ),
            'metadata' => $this->metadata->all(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'envelope' => $this->envelope->getBusinessMessageID(),
            'statuses' => array_map(
                fn (ExchangeStatusInterface $status) => $status->jsonSerialize(),
                $this->getStatuses(),
            ),
            'metadata' => $this->metadata->all(),
        ];
    }
}
