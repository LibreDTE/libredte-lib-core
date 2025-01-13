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

use Derafu\Lib\Core\Package\Prime\Component\Entity\Entity\Entity;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Mapping as DEM;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;

/**
 * Entidad de comuna.
 */
#[DEM\Entity(repositoryClass: ComunaRepository::class)]
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
     * Si el argumento es una cadena no numérica, intenta encontrar una dirección
     * regional correspondiente en el arreglo de direcciones. Si no se encuentra,
     * devuelve la cadena original en mayúsculas. Si el argumento es numérico,
     * asume que es un código de sucursal y devuelve un formato de sucursal. Si
     * el argumento es falso o vacío, devuelve 'N.N.' como valor por defecto.
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
