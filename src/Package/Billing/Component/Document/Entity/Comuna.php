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

namespace libredte\lib\Core\Package\Billing\Component\Document\Entity;

use Derafu\Repository\Entity;
use Derafu\Repository\Mapping as DRM;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;

/**
 * Entidad de comuna.
 */
#[DRM\Entity(repositoryClass: ComunaRepository::class)]
class Comuna extends Entity
{
    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->getAttribute('nombre');
    }

    /**
     * Obtiene el código de la comuna.
     *
     * @return string
     */
    public function getCodigo(): string
    {
        return (string) $this->getAttribute('codigo');
    }

    /**
     * Entrega el nombre de la comuna.
     *
     * @return string
     */
    public function getNombre(): string
    {
        if ($this->hasAttribute('nombre')) {
            return $this->getAttribute('nombre');
        }

        return '';
    }

    /**
     * Obtiene la ciudad asociada a la comuna.
     *
     * @return string|false Nombre de la ciudad asociada o `false` si no se
     * encuentra.
     */
    public function getCiudad(): string|false
    {
        if ($this->hasAttribute('ciudad')) {
            return $this->getAttribute('ciudad');
        }

        return false;
    }

    /**
     * Obtiene la dirección regional del SII asociada a la comuna.
     *
     * Si el argumento es una cadena no numérica, intenta encontrar una
     * dirección regional correspondiente en el arreglo de direcciones. Si no se
     * encuentra, devuelve la cadena original en mayúsculas. Si el argumento es
     * numérico, asume que es un código de sucursal y devuelve un formato de
     * sucursal. Si el argumento es falso o vacío, devuelve 'N.N.' como valor
     * por defecto.
     *
     * @return string La dirección regional correspondiente, un formato de
     * sucursal para códigos numéricos, la misma entrada en mayúsculas si no se
     * encuentra en el arreglo, o 'N.N.' si la entrada es falsa o vacía.
     */
    public function getDireccionRegional(): string
    {
        $comuna = $this->getNombre();

        if (!$comuna) {
            return 'N.N.';
        }

        if (!is_numeric($comuna)) {
            if ($this->hasAttribute('direccion_regional')) {
                return $this->getAttribute('direccion_regional');
            }

            return $comuna;
        }

        return 'SUC ' . $comuna;
    }
}
