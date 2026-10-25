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
use libredte\lib\Core\Package\Billing\Component\Document\Exception\RendererException;

/**
 * Interfaz base de las estrategias de renderizado de documentos tributarios.
 */
interface RendererStrategyInterface extends StrategyInterface
{
    /**
     * Realiza el renderizado del documento.
     *
     * @param DocumentBagInterface $bag Contenedor con los datos del documento a
     * renderizar.
     * @return string Datos binarios del renderizado.
     * @throws RendererException
     */
    public function render(DocumentBagInterface $bag): string;
}
