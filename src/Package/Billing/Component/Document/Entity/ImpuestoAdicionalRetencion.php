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
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;

/**
 * Entidad de impuesto adicional y retención.
 */
#[DEM\Entity(repositoryClass: ImpuestoAdicionalRetencionRepository::class)]
class ImpuestoAdicionalRetencion extends Entity
{
    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->getAttribute('glosa');
    }

    /**
     * Obtiene el código del impuesto o retención.
     *
     * @return int
     */
    public function getCodigo(): int
    {
        return (int) $this->getAttribute('codigo');
    }

    /**
     * Obtiene el tipo de entidad: impuesto adicional o retención.
     *
     * @return string|false A: adicional, R: retención o `false` si no se pudo
     * determinar.
     */
    public function getTipo(): string|false
    {
        if ($this->hasAttribute('tipo')) {
            return $this->getAttribute('tipo');
        }

        return false;
    }

    /**
     * Obtiene la glosa del impuesto adicional o retención.
     *
     * @return string Glosa del impuesto o glosa estándar si no se encontró una.
     */
    public function getGlosa(): string
    {
        if ($this->hasAttribute('glosa')) {
            return $this->getAttribute('glosa');
        }

        return 'Impto. cód. ' . $this->getCodigo();
    }

    /**
     * Obtiene la tasa del impuesto adicional o retención.
     *
     * @return float|false Tasa del impuesto o =false si no se pudo determinar.
     */
    public function getTasa(): float|false
    {
        if ($this->hasAttribute('tasa')) {
            return (float) $this->getAttribute('tasa');
        }

        return false;
    }
}
