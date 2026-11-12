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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Array\ArrayReader;
use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ExamplesWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ExamplesException;
use Symfony\Component\Yaml\Yaml;

/**
 * Clase para el catálogo de ejemplos de documentos tributarios.
 *
 * Los ejemplos provienen de los casos de prueba en YAML utilizados por los
 * tests funcionales del builder de documentos. El archivo de origen mezcla
 * los datos del documento con la clave `Test` (valores esperados usados por
 * la suite de tests para sus aserciones); acá se separan en `example`
 * (entrada válida para `BuilderWorker::build()`) y `expected` (los valores
 * esperados), sin exponer el vocabulario `Test`/`ExpectedValues` propio de
 * la suite de tests.
 *
 * Algunos ejemplos referencian a otro (ej. una Nota de Crédito que anula una
 * Factura previamente emitida) vía `Test.DependsOn` en el YAML de origen —
 * una lista de IDs de otros ejemplos. `list()` entrega los ejemplos en un
 * orden donde toda dependencia aparece antes que quien depende de ella, para
 * que un consumidor que emita los ejemplos secuencialmente (ej. un demo/seed
 * de una aplicación) siempre pueda resolver esas referencias contra
 * documentos que ya emitió.
 */
#[Worker(name: 'examples', component: 'document', package: 'billing')]
class ExamplesWorker extends AbstractWorker implements ExamplesWorkerInterface
{
    /**
     * Extensión de los archivos de ejemplos.
     */
    private const EXTENSION = '.yaml';

    public function __construct(
        private readonly string $fixturesDir
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'list',
        summary: 'Lista los ejemplos de documentos tributarios',
        description: <<<'MD'
            Entrega el catálogo de ejemplos de documentos tributarios electrónicos: casos de cada tipo de documento (facturas, boletas, guías de despacho, notas de crédito y de débito, documentos de exportación) con sus datos de entrada ya preparados. Sirven para aprender el formato o para probar la emisión.

            ## Cómo se usa

            No recibe parámetros. Cada elemento trae un `id`, la `category` (el tipo de documento) y el `case` (el caso). Para obtener los datos de un ejemplo se envía su `id` a `billing.document.examples::get`.

            Los ejemplos vienen en un orden en que cada ejemplo aparece después de aquellos de los que depende (por ejemplo, una nota de crédito que anula una factura aparece después de esa factura), de modo que se pueden emitir en secuencia.
            MD,
        results: [
            'success' => [
                'description' => 'Lista de ejemplos. `id` identifica el ejemplo y es el valor que se envía a `billing.document.examples::get`.',
                'example' => [
                    [
                        'id' => '033_factura_afecta/033_001_simple',
                        'category' => '033_factura_afecta',
                        'case' => '033_001_simple',
                    ],
                    [
                        'id' => '052_guia_despacho/052_001_constituye_venta',
                        'category' => '052_guia_despacho',
                        'case' => '052_001_constituye_venta',
                    ],
                ],
            ],
        ],
    )]
    public function list(): array
    {
        $files = glob($this->fixturesDir . '/*/*' . self::EXTENSION) ?: [];
        sort($files);

        $dependsOnById = [];
        foreach ($files as $file) {
            $id = $this->toId($file);
            $data = ArrayReader::fromValue(Yaml::parseFile($file), $file);
            $dependsOnById[$id] = $data->getStringList('Test.DependsOn') ?? [];
        }

        $orderedIds = $this->sortByDependencies($dependsOnById);

        $examples = [];
        foreach ($orderedIds as $id) {
            $examples[] = [
                'id' => $id,
                'category' => dirname($id),
                'case' => basename($id),
            ];
        }

        return $examples;
    }

    /**
     * Ordena los IDs de ejemplos de forma que cada uno aparezca después de
     * todos los ejemplos de los que depende (`Test.DependsOn` en el YAML de
     * origen).
     *
     * Recorrido DFS post-order sobre el orden de entrada (`$dependsOnById`,
     * ya determinístico por venir de `$files` ordenado alfabéticamente):
     * los ejemplos sin dependencias, o cuyas dependencias ya se visitaron,
     * mantienen ese mismo orden entre sí; solo se reordenan los que
     * dependen de otro ejemplo que todavía no aparecía.
     *
     * @param array<string, list<string>> $dependsOnById IDs de ejemplo mapeados
     * a la lista de IDs de los que dependen (posiblemente vacía).
     * @return list<string> IDs en el orden final.
     * @throws ExamplesException Si un ejemplo depende de un ID inexistente,
     * o si hay una dependencia circular entre ejemplos.
     */
    private function sortByDependencies(array $dependsOnById): array
    {
        $visited = [];
        $ordered = [];

        foreach (array_keys($dependsOnById) as $id) {
            $this->visitDependency($id, [], $dependsOnById, $visited, $ordered);
        }

        return $ordered;
    }

    /**
     * Visita un ejemplo y sus dependencias antes de agregarlo al resultado.
     *
     * @param string $id ID del ejemplo que se visita.
     * @param list<string> $path IDs del recorrido actual para detectar ciclos.
     * @param array<string, list<string>> $dependsOnById Dependencias de cada ejemplo.
     * @param array<string, true> $visited IDs cuyo recorrido ya terminó, compartidos por referencia.
     * @param list<string> $ordered IDs ordenados, compartidos por referencia.
     * @return void
     * @throws ExamplesException Si una dependencia no existe o forma un ciclo.
     */
    private function visitDependency(
        string $id,
        array $path,
        array $dependsOnById,
        array &$visited,
        array &$ordered
    ): void {
        if (isset($visited[$id])) {
            return;
        }

        if (in_array($id, $path, true)) {
            throw new ExamplesException([
                'Circular dependency between examples: {path} -> {id}.',
                'path' => implode(' -> ', $path),
                'id' => $id,
            ]);
        }

        if (!array_key_exists($id, $dependsOnById)) {
            throw new ExamplesException([
                'Example "{id}" does not exist (referenced in DependsOn).',
                'id' => $id,
            ]);
        }

        $path[] = $id;
        foreach ($dependsOnById[$id] as $dependencyId) {
            $this->visitDependency($dependencyId, $path, $dependsOnById, $visited, $ordered);
        }

        $visited[$id] = true;
        $ordered[] = $id;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'get',
        summary: 'Obtiene un ejemplo de documento tributario',
        description: <<<'MD'
            Entrega los datos de un ejemplo de documento tributario electrónico: los datos de entrada, listos para enviarse a `billing.document.builder::build`, y los valores que se esperan del documento construido.

            ## Cómo se usa

            Se envía el `id` del ejemplo, que se obtiene de `billing.document.examples::list`. Si el `id` no existe, la operación falla.

            En la respuesta, `example` tiene los datos del documento en el formato del SII (se envían como `parsedData`), y `expected` los valores que se esperan en el documento ya construido (por ejemplo, los totales calculados).
            MD,
        parameters: [
            'id' => [
                'description' => 'Identificador del ejemplo, de la forma `categoría/caso`, por ejemplo `033_factura_afecta/033_001_simple`. Los disponibles se obtienen con `billing.document.examples::list`.',
                'example' => '033_factura_afecta/033_001_simple',
            ],
        ],
        results: [
            'success' => [
                'description' => 'Ejemplo encontrado. `example` son los datos de entrada del documento y `expected` los valores que se esperan del documento construido con ellos.',
                'example' => [
                    'id' => '033_factura_afecta/033_001_simple',
                    'example' => [
                        'Encabezado' => [
                            'IdDoc' => [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                            ],
                            'Emisor' => [
                                'RUTEmisor' => '76192083-9',
                                'RznSoc' => 'SASCO SpA',
                                'GiroEmis' => 'Tecnología, informática y telecomunicaciones',
                                'Acteco' => 726000,
                                'DirOrigen' => 'Santa Cruz',
                                'CmnaOrigen' => 'Santa Cruz',
                                'CdgVendedor' => 'vl',
                            ],
                            'Receptor' => [
                                'RUTRecep' => '60803000-K',
                                'RznSocRecep' => 'Servicio de Impuestos Internos',
                                'GiroRecep' => 'Gobierno',
                                'DirRecep' => 'Santiago',
                                'CmnaRecep' => 'Santiago',
                            ],
                        ],
                        'Detalle' => [
                            [
                                'NmbItem' => 'Conectores RJ45',
                                'QtyItem' => 450,
                                'PrcItem' => 70,
                            ],
                        ],
                    ],
                    'expected' => [
                        'Encabezado' => [
                            'Emisor' => [
                                'CdgVendedor' => 'vl',
                            ],
                            'Totales' => [
                                'MntNeto' => 31500,
                                'IVA' => 5985,
                                'MntTotal' => 37485,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function get(string $id): array
    {
        $file = realpath($this->fixturesDir . '/' . $id . self::EXTENSION);
        $baseDir = realpath($this->fixturesDir);

        // Se verifica que el archivo resuelto exista y esté efectivamente
        // dentro del directorio de ejemplos (evita path traversal a través
        // del ID recibido).
        if (
            $file === false
            || $baseDir === false
            || !str_starts_with($file, $baseDir . DIRECTORY_SEPARATOR)
        ) {
            throw new ExamplesException([
                'Example "{id}" does not exist.',
                'id' => $id,
            ]);
        }

        $data = ValueType::map()->parse(Yaml::parseFile($file), '', $file);
        $example = $data;
        unset($example['Test']);

        return [
            'id' => $id,
            'example' => $example,
            'expected' => ArrayReader::fromArray($data, $file)->getMap('Test.ExpectedValues') ?? [],
        ];
    }

    /**
     * Determina el identificador de un ejemplo a partir de la ruta de su
     * archivo.
     *
     * @param string $file Ruta absoluta del archivo del ejemplo.
     * @return string Identificador del ejemplo (ruta relativa sin extensión).
     */
    private function toId(string $file): string
    {
        $prefix = $this->fixturesDir . '/';
        $id = str_starts_with($file, $prefix)
            ? substr($file, strlen($prefix))
            : $file
        ;

        return substr($id, 0, -strlen(self::EXTENSION));
    }
}
