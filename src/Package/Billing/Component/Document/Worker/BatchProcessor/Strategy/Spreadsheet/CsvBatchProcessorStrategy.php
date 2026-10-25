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
 * Formato del archivo: separador de columnas `;` y codificación UTF-8. Si el
 * contenido no es UTF-8 válido (por ejemplo un CSV exportado desde Excel en
 * Windows) se asume Windows-1252, que para los caracteres del español coincide
 * con ISO-8859-1.
 */
#[Strategy(name: 'spreadsheet.csv', worker: 'batch_processor', component: 'document', package: 'billing')]
class CsvBatchProcessorStrategy extends AbstractSpreadsheetBatchProcessorStrategy
{
    /**
     * {@inheritDoc}
     */
    protected function readRows(DocumentBatchInterface $batch): array
    {
        $data = $batch->getInputData();
        $encoding = mb_check_encoding($data, 'UTF-8') ? 'UTF-8' : 'Windows-1252';

        return Csv::load($data, encoding: $encoding);
    }
}
