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

use Derafu\Backbone\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ExamplesException;

/**
 * Interfaz para el catálogo de ejemplos de documentos tributarios.
 */
interface ExamplesWorkerInterface extends WorkerInterface
{
    /**
     * Entrega el listado de ejemplos disponibles.
     *
     * Cada elemento entrega su identificador (a usar con `get()`), la
     * categoría (nombre de la carpeta del ejemplo) y el caso (nombre del
     * archivo del ejemplo, sin extensión).
     *
     * @return array<int, array{id: string, category: string, case: string}>
     */
    public function list(): array;

    /**
     * Entrega los datos de un ejemplo específico.
     *
     * `example` corresponde al YAML de entrada tal como se utilizaría con
     * `BuilderWorker::build()` (dentro de un `DocumentBag`). `expected`
     * entrega los valores esperados del caso (`Test.ExpectedValues` en el
     * archivo de origen), separados de `example` para no mezclar el dato del
     * documento con la aserción de la suite de tests.
     *
     * @param string $id Identificador del ejemplo (ver `list()`).
     * @return array{id: string, example: array, expected: array} Datos del
     * ejemplo.
     * @throws ExamplesException Si el ejemplo solicitado no existe.
     */
    public function get(string $id): array;
}
