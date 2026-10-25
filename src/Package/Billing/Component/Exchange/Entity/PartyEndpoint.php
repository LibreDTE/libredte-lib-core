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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Entity;

use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\PartyEndpointInterface;

/**
 * Entidad para representar un punto de recepción de documentos.
 */
class PartyEndpoint implements PartyEndpointInterface
{
    /**
     * Identificador del esquema del valor.
     *
     * @var string
     */
    protected string $schemeId;

    /**
     * Valor del punto de recepción de documentos.
     *
     * @var string
     */
    protected string $value;

    /**
     * Mapa de identificadores de esquema a su nombre.
     *
     * @var array <string, string>
     */
    protected const SCHEME_NAMES = [
        'EMAIL' => 'Correo electrónico',
    ];

    /**
     * Constructor del punto de recepción de documentos de un participante.
     *
     * @param string $value
     * @param string $schemeId
     */
    public function __construct(string $value, string $schemeId = 'EMAIL')
    {
        $this->schemeId = $schemeId;
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->schemeId . ':' . $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchemeId(): string
    {
        return $this->schemeId;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchemeName(): string
    {
        return self::SCHEME_NAMES[$this->schemeId] ?? $this->schemeId;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'value' => $this->getValue(),
            'schemeId' => $this->getSchemeId(),
            'schemeName' => $this->getSchemeName(),
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
