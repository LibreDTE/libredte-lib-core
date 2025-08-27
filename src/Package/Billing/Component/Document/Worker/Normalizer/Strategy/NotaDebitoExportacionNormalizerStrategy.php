<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy;

use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Normalizer\Strategy\NotaDebitoExportacionNormalizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaDebitoExportacionJob;

/**
 * Normalizador del documento nota de débito de exportación.
 */
#[Strategy(name: 'nota_debito_exportacion', worker: 'normalizer', component: 'document', package: 'billing')]
class NotaDebitoExportacionNormalizerStrategy extends AbstractNormalizerStrategy implements NotaDebitoExportacionNormalizerStrategyInterface
{
    public function __construct(
        protected NormalizeDataPreDocumentNormalizationJob $normalizeDataPreDocumentNormalizationJob,
        protected NormalizeDataPostDocumentNormalizationJob $normalizeDataPostDocumentNormalizationJob,
        private NormalizeNotaDebitoExportacionJob $normalizeNotaDebitoExportacionJob
    ) {
    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeDocument(DocumentBagInterface $bag): void
    {
        $this->normalizeNotaDebitoExportacionJob->execute($bag);
    }
}
