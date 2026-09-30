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
