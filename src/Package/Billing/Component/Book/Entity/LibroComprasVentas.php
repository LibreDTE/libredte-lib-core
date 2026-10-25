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
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LibroComprasVentasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoOperacion;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;

/**
 * Entidad que representa un Libro de Compras o Ventas (IECV).
 *
 * La diferencia entre compras y ventas reside exclusivamente en el campo
 * `TipoOperacion` de la carátula del XML.
 */
class LibroComprasVentas extends AbstractBook implements LibroComprasVentasInterface
{
    /**
     * {@inheritdoc}
     *
     * El tipo de libro se determina a partir del campo `TipoOperacion` de la
     * carátula del XML.
     */
    public function getTipo(): TipoLibro
    {
        return $this->getTipoOperacion()->getTipoLibro();
    }

    /**
     * {@inheritDoc}
     */
    public function getTipoOperacion(): TipoOperacion
    {
        $operacion = TipoOperacion::tryFrom(strtoupper(
            (string) $this->getXmlDocument()->query('//Caratula/TipoOperacion')
        ));

        if ($operacion === null) {
            throw new BookException([
                'The operation type "{operation}" is not valid.',
                'operation' => $operacion,
            ]);
        }

        return $operacion;
    }

    /**
     * {@inheritDoc}
     */
    public function isSimplificado(): bool
    {
        return $this->getXmlDocument()->getSchema() === 'LibroCVS_v10.xsd';
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalesPeriodo(): array
    {
        return $this->getResumen()['TotalesPeriodo'] ?? [];
    }
}
