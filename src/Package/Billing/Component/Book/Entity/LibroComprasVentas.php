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
            (string) $this->xmlDocument->query('//Caratula/TipoOperacion')
        ));

        if ($operacion === null) {
            throw new BookException(sprintf(
                'El tipo de operación "%s" no es válido.',
                $operacion
            ));
        }

        return $operacion;
    }

    /**
     * {@inheritDoc}
     */
    public function isSimplificado(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalesPeriodo(): array
    {
        return $this->getResumen()['TotalesPeriodo'] ?? [];
    }
}
