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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\NormalizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\NormalizerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\NormalizerException;

/**
 * Clase para los normalizadores.
 */
#[Worker(name: 'normalizer', component: 'document', package: 'billing')]
class NormalizerWorker extends AbstractWorker implements NormalizerWorkerInterface
{
    use StrategiesAwareTrait;

    public function __construct(
        iterable $strategies = []
    ) {
        $this->setStrategies($strategies);
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(DocumentBagInterface $bag): array
    {
        // Si no hay tipo de documento no se podrá normalizar.
        if (!$bag->getTipoDocumento()) {
            throw new NormalizerException(
                'Cannot normalize without a TipoDocumento in the $bag.'
            );
        }

        // Buscar la estrategia para normalizar el tipo de documento tributario.
        $strategy = $this->getStrategy($bag->getTipoDocumento()->getAlias());
        assert($strategy instanceof NormalizerStrategyInterface);

        // Normalizar el documento usando la estrategia.
        $normalizedData = $strategy->normalize($bag);

        // Entregar los datos normalizados.
        return $normalizedData;
    }
}
