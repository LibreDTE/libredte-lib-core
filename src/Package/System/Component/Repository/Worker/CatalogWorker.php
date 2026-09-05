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
            throw new CatalogException($e->getMessage(), previous: $e);
        }
    }
}
