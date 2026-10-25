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
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;

/**
 * Entidad de impuesto adicional y retención.
 */
#[DRM\Entity(repositoryClass: ImpuestoAdicionalRetencionRepository::class)]
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
