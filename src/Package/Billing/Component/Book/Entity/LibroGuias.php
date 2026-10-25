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

namespace libredte\lib\Core\Package\Billing\Component\Book\Entity;

use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LibroGuiasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;

/**
 * Entidad que representa un Libro de Guías de Despacho.
 *
 * El resumen del período distingue entre guías de venta (TpoOper=1),
 * guías de traslado (TpoOper>1) y guías anuladas.
 */
class LibroGuias extends AbstractBook implements LibroGuiasInterface
{
    /**
     * Tipo de libro.
     */
    protected TipoLibro $tipo = TipoLibro::GUIAS;

    /**
     * {@inheritDoc}
     */
    public function getFolioNotificacion(): ?int
    {
        $folio = $this->getXmlDocument()->query('//Caratula/FolioNotificacion');

        return $folio !== null ? (int) $folio : null;
    }
}
