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

namespace libredte\lib\Core\Package\System\Component\Repository\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use libredte\lib\Core\Package\System\Component\Repository\Exception\CatalogException;

/**
 * Interfaz para la exploración genérica de repositorios de datos.
 *
 * No depende de ninguna entidad o repositorio específico: cualquier
 * repositorio registrado en el `RepositoryManagerInterface` de la aplicación
 * puede consultarse a través de este worker, identificándolo por su FQCN
 * (clase de entidad o interfaz), tal como se usa con
 * `RepositoryManagerInterface::getRepository()`.
 */
interface CatalogWorkerInterface extends WorkerInterface
{
    /**
     * Entrega los identificadores de los repositorios disponibles.
     *
     * No carga ningún repositorio, solo lista los identificadores que pueden
     * usarse con el resto de los métodos de este worker.
     *
     * @return string[]
     */
    public function list(): array;

    /**
     * Busca un elemento de un repositorio por su identificador.
     *
     * @param string $repository Identificador del repositorio (FQCN de la
     * entidad o de su interfaz).
     * @param int|string $id Identificador del elemento buscado.
     * @return object|null El elemento encontrado o `null` si no existe.
     * @throws CatalogException Si el repositorio no existe o no se puede
     * resolver.
     */
    public function find(string $repository, int|string $id): ?object;

    /**
     * Entrega todos los elementos de un repositorio.
     *
     * @param string $repository Identificador del repositorio.
     * @return object[]
     * @throws CatalogException Si el repositorio no existe o no se puede
     * resolver.
     */
    public function findAll(string $repository): array;

    /**
     * Busca elementos de un repositorio según criterios de coincidencia.
     *
     * @param string $repository Identificador del repositorio.
     * @param array $criteria Criterios de búsqueda, formato `['campo' =>
     * 'valor']` (o `['campo' => ['valor1', 'valor2']]` para coincidencia con
     * cualquiera de varios valores).
     * @param array|null $orderBy Orden de los resultados, formato `['campo'
     * => 'ASC'|'DESC']`.
     * @param int|null $limit Cantidad máxima de resultados.
     * @param int|null $offset Cantidad de resultados a saltar.
     * @return object[]
     * @throws CatalogException Si el repositorio no existe o no se puede
     * resolver.
     */
    public function findBy(
        string $repository,
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array;

    /**
     * Busca un único elemento de un repositorio según criterios de
     * coincidencia.
     *
     * @param string $repository Identificador del repositorio.
     * @param array $criteria Criterios de búsqueda (ver `findBy()`).
     * @param array|null $orderBy Orden usado para elegir el primer resultado.
     * @return object|null El primer elemento que cumple los criterios o
     * `null` si no hay ninguno.
     * @throws CatalogException Si el repositorio no existe o no se puede
     * resolver.
     */
    public function findOneBy(
        string $repository,
        array $criteria = [],
        ?array $orderBy = null
    ): ?object;

    /**
     * Cuenta los elementos de un repositorio que cumplen ciertos criterios.
     *
     * @param string $repository Identificador del repositorio.
     * @param array $criteria Criterios de búsqueda (ver `findBy()`). Vacío
     * para contar todos los elementos del repositorio.
     * @return int
     * @throws CatalogException Si el repositorio no existe o no se puede
     * resolver.
     */
    public function count(string $repository, array $criteria = []): int;
}
