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

namespace libredte\lib\Core\Package\System\Component\Repository\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Repository\Contract\RepositoryInterface;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Repository\Exception\ManagerException;
use libredte\lib\Core\Package\System\Component\Repository\Contract\CatalogWorkerInterface;
use libredte\lib\Core\Package\System\Component\Repository\Exception\CatalogException;

/**
 * Clase para la exploración genérica de repositorios de datos.
 */
#[Worker(name: 'catalog', component: 'repository', package: 'system')]
class CatalogWorker extends AbstractWorker implements CatalogWorkerInterface
{
    private const EXAMPLE_REPOSITORY =
        'libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna';

    public function __construct(
        private readonly RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation]
    public function list(): array
    {
        return $this->repositoryManager->getAvailableRepositories();
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'repository' => ['example' => self::EXAMPLE_REPOSITORY],
            'id' => ['example' => 'RM'],
        ],
    )]
    public function find(string $repository, int|string $id): ?object
    {
        return $this->resolveRepository($repository)->find($id);
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'repository' => ['example' => self::EXAMPLE_REPOSITORY],
        ],
    )]
    public function findAll(string $repository): array
    {
        return $this->resolveRepository($repository)->findAll();
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'repository' => ['example' => self::EXAMPLE_REPOSITORY],
        ],
    )]
    public function findBy(
        string $repository,
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        return $this->resolveRepository($repository)->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'repository' => ['example' => self::EXAMPLE_REPOSITORY],
        ],
    )]
    public function findOneBy(
        string $repository,
        array $criteria = [],
        ?array $orderBy = null
    ): ?object {
        return $this->resolveRepository($repository)->findOneBy(
            $criteria,
            $orderBy
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'repository' => ['example' => self::EXAMPLE_REPOSITORY],
        ],
    )]
    public function count(string $repository, array $criteria = []): int
    {
        return $this->resolveRepository($repository)->count($criteria);
    }

    /**
     * Resuelve el repositorio solicitado, traduciendo cualquier error de
     * resolución a una excepción propia de este worker.
     *
     * @param string $repository Identificador del repositorio.
     * @return RepositoryInterface
     * @throws CatalogException Si el repositorio no existe o no se puede
     * resolver.
     */
    private function resolveRepository(string $repository): RepositoryInterface
    {
        try {
            return $this->repositoryManager->getRepository($repository);
        } catch (ManagerException $e) {
            throw new CatalogException([
                'Could not resolve the repository: {detail}',
                'detail' => $e,
            ], previous: $e);
        }
    }
}
