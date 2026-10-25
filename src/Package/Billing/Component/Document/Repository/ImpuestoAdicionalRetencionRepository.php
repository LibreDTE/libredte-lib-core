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

namespace libredte\lib\Core\Package\Billing\Component\Document\Repository;

use Derafu\Repository\Repository;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;

/**
 * Repositorio para trabajar con los impuestos adicionales y retenciones.
 */
class ImpuestoAdicionalRetencionRepository extends Repository
{
    /**
     * {@inheritDoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null): ImpuestoAdicionalRetencion
    {
        // Buscar la entidad solicitada.
        $entity = parent::find($id, $lockMode, $lockVersion);

        // Si la entidad existe se retorna directamente.
        if ($entity) {
            return $entity;
        }

        // Si no existe se entregará una entidad "falsa" que sirve para mostrar
        // datos de prueba con menor control dónde se llame a find().
        return $this->createEntity([
            'codigo' => $id,
        ]);
    }
}
