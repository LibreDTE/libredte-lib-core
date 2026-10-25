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

/**
 * Entidad de etiqueta XML.
 */
class TagXml extends Entity
{
    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->getAttribute('glosa');
    }

    /**
     * Entrega la entidad asociada al tag XML si existe una.
     *
     * La entidad estará disponible cuando exista un repositorio asociado que
     * maneje el tag XML (sus glosas).
     *
     * @return string|null
     */
    public function getEntity(): ?string
    {
        if (!$this->hasAttribute('entity')) {
            return null;
        }

        return $this->getAttribute('entity');
    }
}
