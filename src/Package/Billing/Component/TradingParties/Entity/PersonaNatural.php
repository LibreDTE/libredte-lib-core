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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Entity;

use Derafu\L10n\Cl\Rut\Rut;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\PersonaNaturalInterface;

/**
 * Clase para representar una persona natural de Chile.
 *
 * Proporciona información básica de una persona natural. Principalmente, los
 * datos básicos necesarios para los certificados digitales.
 */
class PersonaNatural implements PersonaNaturalInterface
{
    /**
     * RUN de la persona natural.
     *
     * @var int
     */
    protected int $run;

    /**
     * Dígito verificador (DV) del RUT.
     *
     * @var string
     */
    protected string $dv;

    /**
     * Nombre de la persona.
     *
     * @var string|null
     */
    protected ?string $nombre;

    /**
     * Dirección de correo electrónico de la persona.
     *
     * @var string|null
     */
    protected ?string $email;

    /**
     * Constructor de la clase PersonaNatural.
     *
     * @param string|int $run RUN del contribuyente.
     * @param string $nombre Nombre de la persona natural.
     * @param string $email Correo electrónico de la persona natural.
     */
    public function __construct(
        string|int $run,
        ?string $nombre = null,
        ?string $email = null
    ) {
        $run = Rut::format($run);
        [$this->run, $this->dv] = Rut::toArray($run);

        $this->nombre = $nombre;
        $this->email = $email;

        // Validar el RUN asignado (independiente del origen).
        Rut::validate($this->getRun());
    }

    /**
     * {@inheritDoc}
     */
    public function getRun(): string
    {
        return $this->run . '-' . $this->dv;
    }

    /**
     * {@inheritDoc}
     */
    public function getNombre(): string
    {
        return $this->nombre ?? $this->getRun();
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'run' => $this->getRun(),
            'nombre' => $this->getNombre(),
            'email' => $this->getEmail(),
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
