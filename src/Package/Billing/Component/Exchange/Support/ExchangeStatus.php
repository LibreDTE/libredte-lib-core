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
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeStatusInterface;
use Throwable;

/**
 * Clase que representa el estado del resultado de la ejecución de una
 * estrategia de intercambio.
 */
class ExchangeStatus implements ExchangeStatusInterface
{
    /**
     * Código de la estrategia a la que está asociado este estado.
     *
     * @var string
     */
    private string $strategy;

    /**
     * El error que la estrategia generó al ser ejecutada.
     *
     * Por ejemplo: una excepción lanzada.
     *
     * @var Throwable|null
     */
    private ?Throwable $error;

    /**
     * Metadatos del estado de resultado.
     *
     * @var BagInterface
     */
    private BagInterface $metadata;

    /**
     * Constructor del estado de la estrategia.
     *
     * @param string $strategy
     * @param Throwable|null $error
     * @param BagInterface|array $metadata
     */
    public function __construct(
        string $strategy,
        ?Throwable $error = null,
        BagInterface|array $metadata = []
    ) {
        $this->strategy = $strategy;
        $this->error = $error;
        $this->setMetadata($metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * {@inheritDoc}
     */
    public function isOk(): bool
    {
        return !$this->hasError();
    }

    /**
     * {@inheritDoc}
     */
    public function setError(Throwable $error): static
    {
        $this->error = $error;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     */
    public function hasError(): bool
    {
        return isset($this->error);
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
            'strategy' => $this->strategy,
            'error' => $this->error !== null
                ? ['message' => $this->error->getMessage()]
                : null,
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
        return $this->toArray();
    }
}
