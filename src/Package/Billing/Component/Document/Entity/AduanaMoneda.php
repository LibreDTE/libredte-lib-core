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

namespace libredte\lib\Core\Package\Billing\Component\Document\Entity;

use Derafu\Enum\Contract\CurrencyInterface;
use Derafu\Enum\Currency;
use Derafu\Repository\Entity;

/**
 * Entidad de una moneda de aduana (documentos de exportación).
 */
class AduanaMoneda extends Entity
{
    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->getAttribute('glosa');
    }

    /**
     * Entrega el código ISO 4217 asociado a la moneda.
     *
     * @return string
     */
    public function getCodigoISO(): string
    {
        return $this->getAttribute('codigo_iso');
    }

    /**
     * Obtiene la instancia de la moneda asociada a la moneda de aduaba.
     *
     * Si la moneda no fue encontrada en las monedas soportadas se devolverá la
     * moneda ISO 4217 XXX.
     *
     * @return CurrencyInterface
     */
    public function getCurrency(): CurrencyInterface
    {
        $moneda = Currency::tryFrom($this->getCodigoISO());

        if ($moneda !== null) {
            return $moneda;
        }

        return Currency::XXX;
    }
}
