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
