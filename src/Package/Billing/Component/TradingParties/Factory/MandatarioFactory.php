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
use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Mandatario;

/**
 * Fábrica de una entidad de mandatario.
 */
class MandatarioFactory implements MandatarioFactoryInterface
{
    /**
     * Clase de la entidad de los mandatarios.
     *
     * @var class-string<MandatarioInterface>
     */
    private string $class = Mandatario::class;

    /**
     * {@inheritDoc}
     */
    public function create(array $data): MandatarioInterface
    {
        $data = $this->normalizeData($data);

        [$data['run'], $data['dv']] = Rut::toArray($data['run']);

        return Factory::create($data, $this->class);
    }

    /**
     * Normaliza los datos del mandatario para su creación.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        $data['run'] = $data['run'] ?? $data['rut'] ?? null;
        unset($data['rut']);

        if (empty($data['run']) || empty($data['nombre']) || empty($data['email'])) {
            throw new LogicException(
                'The agent run, name and email attributes are required.'
            );
        }

        return $data;
    }
}
