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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Backbone\Contract\StrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;

/**
 * Interfaz para las estrategias de los procesadores de documentos en lote.
 *
 * Las estrategias son parsers: reciben el lote con los datos de entrada (un
 * archivo CSV, una planilla, etc.) y generan el listado de los documentos
 * tributarios en la estructura oficial del SII. No construyen los documentos,
 * eso se hace después, uno a uno, al procesar el lote.
 */
interface BatchProcessorStrategyInterface extends StrategyInterface
{
    /**
     * Parsea los datos de entrada del lote y genera los datos de los
     * documentos tributarios electrónicos.
     *
     * @param DocumentBatchInterface $batch Contenedor del lote a parsear.
     * @return array Arreglo con los datos de cada documento parseado.
     * @throws BatchProcessorException
     */
    public function parse(DocumentBatchInterface $batch): array;
}
