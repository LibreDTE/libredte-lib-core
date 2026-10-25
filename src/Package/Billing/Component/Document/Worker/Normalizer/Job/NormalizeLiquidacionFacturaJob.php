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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;

/**
 * Normalizador del documento liquidación de factura.
 */
#[Job(name: 'normalize_liquidacion_factura', worker: 'normalizer', component: 'document', package: 'billing')]
class NormalizeLiquidacionFacturaJob extends AbstractJob implements JobInterface
{
    // Traits usados por este normalizador.
    // TODO: Agregar los traits que usa este normalizador.

    public function __construct(
        protected RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function execute(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // TODO: Implementar la normalización de los datos en $data.

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}
