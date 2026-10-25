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
     * El orden entregado garantiza que todo ejemplo aparece después de los
     * ejemplos de los que depende (ej. una Nota de Crédito que anula una
     * Factura aparece después de esa Factura) — un consumidor que arme cada
     * ejemplo secuencialmente, en el orden recibido, siempre podrá resolver
     * esas referencias contra ejemplos ya procesados.
     *
     * @return array<int, array{id: string, category: string, case: string}>
     * @throws ExamplesException Si algún ejemplo declara una dependencia
     * circular o hacia un ejemplo inexistente.
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
