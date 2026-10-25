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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;

/**
 * Fábrica de una entidad de receptor.
 */
class ReceptorFactory extends AbstractContribuyenteFactory implements ReceptorFactoryInterface
{
    /**
     * Clase de la entidad de los receptores.
     *
     * @var class-string<ReceptorInterface>
     */
    private string $class = Receptor::class;

    /**
     * {@inheritDoc}
     */
    public function create(array $data): ReceptorInterface
    {
        $data = $this->normalizeData($data);

        [$data['rut'], $data['dv']] = Rut::toArray($data['rut']);

        return Factory::create($data, $this->class);
    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeData(array $data): array
    {
        $normalized = parent::normalizeData($data);

        $normalized['codigo_interno'] = (
            (string) (
                $data['codigo_interno']
                ?? $data['CdgIntRecep']
                ?? null
            )
        ) ?: null;

        $normalized['nacionalidad'] = (
            (string) (
                $data['nacionalidad']
                ?? $data['Nacionalidad']
                ?? null
            )
        ) ?: null;

        $normalized['identificador_extranjero'] = (
            (string) (
                $data['identificador_extranjero']
                ?? $data['NumId']
                ?? null
            )
        ) ?: null;

        return $normalized;
    }
}
