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

use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\PartyEndpointInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\PartyIdentifierInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\PartyInterface;
use Symfony\Component\Mime\Address;

/**
 * Clase base para los participantes del intercambio de documentos.
 */
abstract class AbstractParty implements PartyInterface
{
    /**
     * Identificador único del participante.
     *
     * @var PartyIdentifierInterface
     */
    private PartyIdentifierInterface $identifier;

    /**
     * Listado de puntos de recepción de documentos del participante.
     *
     * @var PartyEndpointInterface[]
     */
    private array $endpoints;

    /**
     * Constructor del participante.
     *
     * @param PartyIdentifierInterface $identifier
     * @param PartyEndpointInterface[] $endpoints
     */
    public function __construct(
        PartyIdentifierInterface $identifier,
        array $endpoints = []
    ) {
        $this->identifier = $identifier;
        $this->endpoints = $endpoints;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): PartyIdentifierInterface
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function addEndpoint(PartyEndpointInterface $endpoint): static
    {
        $this->endpoints[] = $endpoint;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmails(): array
    {
        $emails = [];

        foreach ($this->endpoints as $endpoint) {
            if ($endpoint->getSchemeId() === 'EMAIL') {
                $aux = explode(':', $endpoint->getValue(), 2);
                $emails[] = new Address($aux[0], $aux[1] ?? '');
            }
        }

        return $emails;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier->toArray(),
            'endpoints' => array_map(
                fn (PartyEndpointInterface $endpoint) => $endpoint->toArray(),
                $this->endpoints,
            ),
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
            'identifier' => $this->identifier->jsonSerialize(),
            'endpoints' => array_map(
                fn (PartyEndpointInterface $endpoint) => $endpoint->jsonSerialize(),
                $this->endpoints,
            ),
        ];
    }
}
