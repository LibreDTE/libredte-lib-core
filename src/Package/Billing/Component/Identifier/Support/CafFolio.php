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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Support;

use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFolioInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;

/**
 * Clase que representa un contenedor de un archivo CAF y el folio asociado a
 * usar en un documento electrónico.
 */
class CafFolio implements CafFolioInterface
{
    public function __construct(
        private int $folio,
        private CafInterface $caf,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getFolio(): int
    {
        return $this->folio;
    }

    /**
     * {@inheritDoc}
     */
    public function getCaf(): CafInterface
    {
        return $this->caf;
    }
}
