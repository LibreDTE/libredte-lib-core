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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy;

use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Normalizer\Strategy\NotaCreditoNormalizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoJob;

/**
 * Normalizador del documento nota de crédito.
 */
#[Strategy(name: 'nota_credito', worker: 'normalizer', component: 'document', package: 'billing')]
class NotaCreditoNormalizerStrategy extends AbstractNormalizerStrategy implements NotaCreditoNormalizerStrategyInterface
{
    public function __construct(
        protected NormalizeDataPreDocumentNormalizationJob $normalizeDataPreDocumentNormalizationJob,
        protected NormalizeDataPostDocumentNormalizationJob $normalizeDataPostDocumentNormalizationJob,
        private NormalizeNotaCreditoJob $normalizeNotaCreditoJob
    ) {
    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeDocument(DocumentBagInterface $bag): void
    {
        $this->normalizeNotaCreditoJob->execute($bag);
    }
}
