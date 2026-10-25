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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Backbone\Abstract\AbstractStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\NormalizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;

/**
 * Clase abstracta (base) para las estrategias de normalización de
 * documentos tributarios.
 */
abstract class AbstractNormalizerStrategy extends AbstractStrategy implements NormalizerStrategyInterface
{
    protected NormalizeDataPreDocumentNormalizationJob $normalizeDataPreDocumentNormalizationJob;

    protected NormalizeDataPostDocumentNormalizationJob $normalizeDataPostDocumentNormalizationJob;

    /**
     * {@inheritdoc}
     */
    public function normalize(DocumentBagInterface $bag): array
    {
        // Asignar inicialmente como datos normalizados los datos parseados.
        // Esto se hace porque son la base de los datos para normalizar y se van
        // normalizando en la misma variable de la bolsa durante todo el
        // proceso.
        $bag->setNormalizedData($bag->getParsedData());

        // Aplicar normalización inicial del DTE.
        $this->normalizeDataPreDocumentNormalizationJob->execute($bag);

        // Aplicar normalización del tipo de DTE.
        $this->normalizeDocument($bag);

        // Aplicar normalización final del DTE.
        $this->normalizeDataPostDocumentNormalizationJob->execute($bag);

        // Entregar los datos normalizados.
        return $bag->getNormalizedData();
    }

    /**
     * Normalización personalizada de cada estrategia.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    abstract protected function normalizeDocument(DocumentBagInterface $bag): void;
}
