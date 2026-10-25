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
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentBagManagerException;

/**
 * Interfaz para que administra una bolsa con los datos de un documento
 * tributario.
 */
interface DocumentBagManagerWorkerInterface extends WorkerInterface
{
    /**
     * Crea la bolsa con los datos del documento a partir de datos de origen.
     *
     * Se pueden pasar diferentes tipos de datos de origen:
     *
     *   - `string`: Datos de entrada para ser parseados y normalizados. Debe
     *     empezar con el prefijo `parser.strategy.xyz:` para que los datos sean
     *     parseados.
     *   - `array`: Datos ya normalizados.
     *   - `XmlDocumentInterface`: Una instancia del documento XML con sus datos
     *     normalizado.
     *   - `DocumentInterface`: Una instancia del documento tributario con sus
     *     datos normalizados.
     *
     * @param string|array|XmlDocumentInterface|DocumentInterface $source Datos de origen.
     * @param bool $normalizeAll Indica si se deben normalizar todos los datos
     * de la bolsa al crearla o solo los mínimos.
     * @return DocumentBagInterface
     * @throws DocumentBagManagerException
     */
    public function create(
        string|array|XmlDocumentInterface|DocumentInterface $source,
        bool $normalizeAll = true
    ): DocumentBagInterface;

    /**
     * Normaliza una bolsa con datos de un documento tributario.
     *
     * Se completará el contenido que falte con lo que se pueda completar según
     * sea el contenido de la bolsa.
     *
     * @param DocumentBagInterface $bag
     * @param bool $all Indica si se deben aplicar todas las normalizaciones o
     * solo la de la asignación del tipo de documento.
     * @return DocumentBagInterface
     */
    public function normalize(
        DocumentBagInterface $bag,
        bool $all = false
    ): DocumentBagInterface;
}
