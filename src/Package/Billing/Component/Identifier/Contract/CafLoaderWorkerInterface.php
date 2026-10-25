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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlParseException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafLoaderException;

/**
 * Interfaz para el worker que permite cargar un archivo XML con el CAF.
 */
interface CafLoaderWorkerInterface extends WorkerInterface
{
    /**
     * Carga el XML de un CAF y lo entrega como entidad CAF.
     *
     * @param string|XmlDocumentInterface $xml XML del CAF a cargar, como
     * string o como documento ya construido.
     * @return CafInterface El CAF cargado, con sus datos y folios
     * disponibles.
     * @throws XmlParseException Si el XML del CAF está vacío o mal formado.
     * @throws CafLoaderException Si el código de tipo de documento del CAF
     * no existe en el repositorio.
     */
    public function load(string|XmlDocumentInterface $xml): CafInterface;
}
