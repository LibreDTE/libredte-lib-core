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

use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\AutorizacionDteInterface;

/**
 * Entidad para representar la información de autorización que da el SII a un
 * contribuyente para ser emisor de documentos tributarios electrónicos.
 *
 * Cada contribuyente puede tener 2 autorizaciones, una por cada ambiente.
 */
class AutorizacionDte implements AutorizacionDteInterface
{
    /**
     * Constructor de la entidad.
     *
     * @param string $fecha_resolucion Fecha asignada por SII a la resolución.
     * @param int $numero_resolucion Número de resolución.
     */
    public function __construct(
        private string $fecha_resolucion,
        private int $numero_resolucion = 0
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaResolucion(): string
    {
        return $this->fecha_resolucion;
    }

    /**
     * {@inheritDoc}
     */
    public function getNumeroResolucion(): int
    {
        return $this->numero_resolucion;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvironment(): SiiEnvironment
    {
        return $this->numero_resolucion === 0
            ? SiiEnvironment::PRODUCTION
            : SiiEnvironment::STAGING
        ;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'FchResol' => $this->getFechaResolucion(),
            'NroResol' => $this->getNumeroResolucion(),
        ];
    }
}
