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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessor\Strategy\Spreadsheet;

use Derafu\Backbone\Attribute\Strategy;
use Derafu\Support\Csv;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSpreadsheetBatchProcessorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBatchInterface;

/**
 * Estrategia "billing.document.batch_processor.strategy:spreadsheet.csv".
 *
 * Parsea un lote de documentos tributarios entregado como CSV con el formato
 * estándar de LibreDTE. El formato de las columnas está descrito en
 * `AbstractSpreadsheetBatchProcessorStrategy`.
 *
 * Formato del archivo: separador de columnas `;` y codificación UTF-8.
 */
#[Strategy(name: 'spreadsheet.csv', worker: 'batch_processor', component: 'document', package: 'billing')]
class CsvBatchProcessorStrategy extends AbstractSpreadsheetBatchProcessorStrategy
{
    /**
     * {@inheritDoc}
     */
    protected function readRows(DocumentBatchInterface $batch): array
    {
        return Csv::load($batch->getInputData());
    }
}
