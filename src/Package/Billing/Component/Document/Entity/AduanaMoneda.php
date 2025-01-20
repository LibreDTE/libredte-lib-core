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

namespace libredte\lib\Core\Package\Billing\Component\Document\Entity;

use Derafu\Lib\Core\Enum\Currency;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Entity\Entity;

/**
 * Entidad de una moneda de aduana (documentos de exportación).
 */
class AduanaMoneda extends Entity
{
    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->getAttribute('glosa');
    }

    /**
     * Entrega el código ISO 4217 asociado a la moneda.
     *
     * @return string
     */
    public function getCodigoISO(): string
    {
        return $this->getAttribute('codigo_iso');
    }

    /**
     * Obtiene la instancia de la moneda asociada a la moneda de aduaba.
     *
     * Si la moneda no fue encontrada en las monedas soportadas se devolverá la
     * moneda ISO 4217 XXX.
     *
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        $moneda = Currency::tryFrom($this->getCodigoISO());

        if ($moneda !== null) {
            return $moneda;
        }

        return Currency::XXX;
    }
}
