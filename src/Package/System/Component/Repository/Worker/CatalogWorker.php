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

    private const REPOSITORY_DESCRIPTION = <<<'MD'
        Repositorio que se consulta, identificado por el nombre completo de su entidad, por ejemplo `libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna`. Los repositorios disponibles se obtienen con `system.repository.catalog::list`; si el repositorio no existe, la operación falla.
        MD;

    private const CRITERIA_DESCRIPTION = <<<'MD'
        Filtro por igualdad: un objeto cuyas claves son campos del registro y cuyos valores son lo que se busca, por ejemplo `{"direccion_regional": "ARICA"}`. Con varios campos deben cumplirse todos. La comparación distingue mayúsculas de minúsculas, y un campo que no existe en el repositorio hace fallar la operación. Sin criterios se consideran todos los registros.
        MD;

    private const ORDER_BY_DESCRIPTION = <<<'MD'
        Orden del resultado: un objeto con el campo y la dirección, `ASC` o `DESC`, por ejemplo `{"nombre": "DESC"}`.
        MD;

    public function __construct(
        private readonly RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'list',
        summary: 'Lista los repositorios del catálogo',
        description: <<<'MD'
            Entrega los repositorios de datos que se pueden consultar con las demás operaciones del catálogo: tipos de documento, comunas, medios de pago, datos de aduana, etc.

            ## Cómo se usa

            No recibe parámetros. Cada elemento de la respuesta es el identificador de un repositorio, que se envía como `repository` a `system.repository.catalog::find`, `find_all`, `find_by`, `find_one_by` y `count`.

            ## Repositorios

            Todos los identificadores empiezan con `libredte\lib\Core\Package\Billing\Component\Document\`; la columna indica el resto.

            | Repositorio | Contenido | Campos |
            |---|---|---|
            | `Contract\TipoDocumentoInterface` | Tipos de documento (códigos del SII) | `codigo`, `nombre`, `nombre_corto`, `categoria`, `operacion`, `es_electronico`, `se_envia_al_sii`, `disponible_en_compras`, `disponible_en_ventas`, `es_cedible`, `es_exento`, `es_boleta`, `es_exportacion`, `es_guia_despacho` |
            | `Entity\Comuna` | Comunas de Chile | `codigo` (texto), `nombre`, `direccion_regional` |
            | `Entity\FormaPago` | Formas de pago | `codigo`, `glosa` |
            | `Entity\FormaPagoExportacion` | Formas de pago de exportación | `codigo`, `glosa` |
            | `Entity\MedioPago` | Medios de pago | `codigo` (texto), `glosa` |
            | `Entity\Traslado` | Tipos de traslado | `codigo`, `glosa` |
            | `Entity\ImpuestoAdicionalRetencion` | Impuestos adicionales y retenciones | `codigo`, `tipo`, `glosa`, `tasa` |
            | `Entity\TagXml` | Etiquetas XML de los documentos | `codigo` (texto), `glosa`, `entity` |
            | `Entity\AduanaClausulaVenta` | Cláusulas de venta de aduana | `codigo`, `glosa` |
            | `Entity\AduanaFormaPago` | Formas de pago de aduana | `codigo`, `glosa` |
            | `Entity\AduanaModalidadVenta` | Modalidades de venta de aduana | `codigo`, `glosa` |
            | `Entity\AduanaMoneda` | Monedas de aduana | `codigo`, `glosa`, `codigo_iso` |
            | `Entity\AduanaPais` | Países de aduana | `codigo`, `glosa`, `codigo_iso` |
            | `Entity\AduanaPuerto` | Puertos de aduana | `codigo`, `glosa` |
            | `Entity\AduanaTipoBulto` | Tipos de bulto de aduana | `codigo`, `glosa` |
            | `Entity\AduanaTransporte` | Vías de transporte de aduana | `codigo`, `glosa` |
            | `Entity\AduanaUnidad` | Unidades de aduana | `codigo`, `glosa` |
            MD,
        results: [
            'success' => [
                'description' => 'Lista con el identificador de cada repositorio disponible.',
                'example' => [
                    'libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface',
                    'libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaClausulaVenta',
                    'libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna',
                    'libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPago',
                    'libredte\lib\Core\Package\Billing\Component\Document\Entity\MedioPago',
                ],
            ],
        ],
    )]
    public function list(): array
    {
        return $this->repositoryManager->getAvailableRepositories();
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'find',
        summary: 'Obtiene un registro del catálogo por su código',
        description: <<<'MD'
            Entrega el registro de un repositorio que tiene el código indicado.

            ## Cómo se usa

            Se envía `repository` y el `id` (el campo `codigo` del registro). Los campos de la respuesta dependen del repositorio; se describen en `system.repository.catalog::list`.

            Si el código no existe, la mayoría de los repositorios responde `null`. Hay dos excepciones: las comunas (`Entity\Comuna`) y los impuestos adicionales y retenciones (`Entity\ImpuestoAdicionalRetencion`) devuelven un registro construido solo con ese código, sin los demás datos (en las comunas, con `nombre` igual al código). Para saber con certeza si un código existe, en cualquier repositorio, conviene usar `system.repository.catalog::find_one_by` o `system.repository.catalog::count`.
            MD,
        parameters: [
            'repository' => [
                'description' => self::REPOSITORY_DESCRIPTION,
                'example' => self::EXAMPLE_REPOSITORY,
            ],
            'id' => [
                'description' => 'Código del registro (`codigo`). Es un número en la mayoría de los repositorios y un texto en otros, como las comunas (`SANTIAGO`) y los medios de pago (`EF`).',
                'example' => 'SANTIAGO',
            ],
        ],
        results: [
            'success' => [
                'description' => 'Registro encontrado. Sus campos dependen del repositorio.',
                'example' => [
                    'codigo' => 'SANTIAGO',
                    'nombre' => 'SANTIAGO',
                    'direccion_regional' => 'SANTIAGO CENTRO',
                ],
            ],
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
        name: 'find_all',
        summary: 'Obtiene todos los registros de un repositorio',
        description: <<<'MD'
            Entrega todos los registros de un repositorio del catálogo, sin filtrar ni paginar.

            ## Cómo se usa

            Se envía solo `repository`. Algunos repositorios son grandes (por ejemplo, las comunas o los puertos de aduana tienen más de trescientos registros); para obtener una parte se usa `system.repository.catalog::find_by`.
            MD,
        parameters: [
            'repository' => [
                'description' => self::REPOSITORY_DESCRIPTION,
                'example' => self::EXAMPLE_REPOSITORY,
            ],
        ],
        results: [
            'success' => [
                'description' => 'Lista con todos los registros del repositorio. Los campos de cada registro dependen del repositorio.',
                'example' => [
                    [
                        'codigo' => 'ARICA',
                        'nombre' => 'ARICA',
                        'direccion_regional' => 'ARICA',
                    ],
                    [
                        'codigo' => 'CAMARONES',
                        'nombre' => 'CAMARONES',
                        'direccion_regional' => 'ARICA',
                    ],
                ],
            ],
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
        name: 'find_by',
        summary: 'Busca registros de un repositorio por criterios',
        description: <<<'MD'
            Entrega los registros de un repositorio que cumplen los criterios indicados, con orden y paginación opcionales.

            ## Cómo se usa

            Se envía `repository` y, opcionalmente, `criteria` para filtrar, `orderBy` para ordenar, y `limit` y `offset` para paginar. Sin `criteria` se consideran todos los registros. Si ninguno cumple, la respuesta es una lista vacía.

            Por ejemplo, para obtener las dos primeras comunas de la dirección regional de Arica, en orden alfabético descendente: `criteria` igual a `{"direccion_regional": "ARICA"}`, `orderBy` igual a `{"nombre": "DESC"}` y `limit` igual a `2`.
            MD,
        parameters: [
            'repository' => [
                'description' => self::REPOSITORY_DESCRIPTION,
                'example' => self::EXAMPLE_REPOSITORY,
            ],
            'criteria' => [
                'description' => self::CRITERIA_DESCRIPTION,
                'example' => ['direccion_regional' => 'ARICA'],
            ],
            'orderBy' => [
                'description' => self::ORDER_BY_DESCRIPTION,
                'example' => ['nombre' => 'DESC'],
            ],
            'limit' => [
                'description' => 'Cantidad máxima de registros a devolver. Sin valor, se devuelven todos.',
                'example' => 2,
            ],
            'offset' => [
                'description' => 'Cantidad de registros que se omiten antes de empezar a devolver, para paginar junto con `limit`. Sin valor, se parte desde el primero.',
                'example' => 0,
            ],
        ],
        results: [
            'success' => [
                'description' => 'Lista con los registros que cumplen los criterios, o vacía si ninguno los cumple. Los campos de cada registro dependen del repositorio.',
                'example' => [
                    [
                        'codigo' => 'PUTRE',
                        'nombre' => 'PUTRE',
                        'direccion_regional' => 'ARICA',
                    ],
                    [
                        'codigo' => 'GENERAL LAGOS',
                        'nombre' => 'GENERAL LAGOS',
                        'direccion_regional' => 'ARICA',
                    ],
                ],
            ],
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
        name: 'find_one_by',
        summary: 'Busca un registro de un repositorio por criterios',
        description: <<<'MD'
            Entrega el primer registro de un repositorio que cumple los criterios indicados, o `null` si ninguno los cumple.

            ## Cómo se usa

            Se envía `repository` y `criteria`, y opcionalmente `orderBy` para definir cuál es el primero cuando hay varios. A diferencia de `system.repository.catalog::find`, aquí un registro inexistente se responde con `null`, por lo que sirve para saber si un valor existe.
            MD,
        parameters: [
            'repository' => [
                'description' => self::REPOSITORY_DESCRIPTION,
                'example' => self::EXAMPLE_REPOSITORY,
            ],
            'criteria' => [
                'description' => self::CRITERIA_DESCRIPTION,
                'example' => ['nombre' => 'PUTRE'],
            ],
            'orderBy' => [
                'description' => self::ORDER_BY_DESCRIPTION,
                'example' => ['nombre' => 'ASC'],
            ],
        ],
        results: [
            'success' => [
                'description' => 'Primer registro que cumple los criterios, o `null` si ninguno los cumple. Los campos del registro dependen del repositorio.',
                'example' => [
                    'codigo' => 'PUTRE',
                    'nombre' => 'PUTRE',
                    'direccion_regional' => 'ARICA',
                ],
            ],
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
        name: 'count',
        summary: 'Cuenta los registros de un repositorio',
        description: <<<'MD'
            Entrega la cantidad de registros de un repositorio que cumplen los criterios indicados.

            ## Cómo se usa

            Se envía `repository` y, opcionalmente, `criteria`. Sin `criteria` se cuentan todos los registros del repositorio.
            MD,
        parameters: [
            'repository' => [
                'description' => self::REPOSITORY_DESCRIPTION,
                'example' => self::EXAMPLE_REPOSITORY,
            ],
            'criteria' => [
                'description' => self::CRITERIA_DESCRIPTION,
                'example' => ['direccion_regional' => 'ARICA'],
            ],
        ],
        results: [
            'success' => [
                'description' => 'Cantidad de registros que cumplen los criterios.',
                'example' => 4,
            ],
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
     * @return RepositoryInterface<object>
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
