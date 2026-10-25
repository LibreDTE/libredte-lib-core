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

namespace libredte\lib\Core\Package\System\Component\Repository;

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use libredte\lib\Core\Package\System\Component\Repository\Contract\CatalogWorkerInterface;
use libredte\lib\Core\Package\System\Component\Repository\Contract\RepositoryComponentInterface;

/**
 * Componente "system.repository".
 *
 * Este componente se encarga de la exploración genérica de los repositorios
 * de datos registrados en la aplicación, sin depender de ningún paquete de
 * negocio en particular.
 */
#[Component(name: 'repository', package: 'system')]
class RepositoryComponent extends AbstractComponent implements RepositoryComponentInterface
{
    public function __construct(
        private CatalogWorkerInterface $catalogWorker
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'catalog' => $this->catalogWorker,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCatalogWorker(): CatalogWorkerInterface
    {
        return $this->catalogWorker;
    }
}
