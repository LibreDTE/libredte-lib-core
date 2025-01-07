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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
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
