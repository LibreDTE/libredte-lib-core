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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Factory;

use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Support\Factory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ContribuyenteFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ContribuyenteInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;

/**
 * Fábrica de una entidad de contribuyente.
 */
class ContribuyenteFactory extends AbstractContribuyenteFactory implements ContribuyenteFactoryInterface
{
    /**
     * Clase de la entidad de los contribuyentes.
     *
     * @var class-string<ContribuyenteInterface>
     */
    protected string $class = Contribuyente::class;

    /**
     * {@inheritDoc}
     */
    public function create(array $data): ContribuyenteInterface
    {
        $data = $this->normalizeData($data);

        [$data['rut'], $data['dv']] = Rut::toArray($data['rut']);

        return Factory::create($data, $this->class);
    }
}
