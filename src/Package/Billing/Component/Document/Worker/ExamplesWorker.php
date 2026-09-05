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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
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
    #[Operation]
    public function list(): array
    {
        $files = glob($this->fixturesDir . '/*/*' . self::EXTENSION) ?: [];

        $examples = [];
        foreach ($files as $file) {
            $id = $this->toId($file);
            $examples[] = [
                'id' => $id,
                'category' => dirname($id),
                'case' => basename($id),
            ];
        }

        return $examples;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'id' => ['example' => '033_factura_afecta/033_001_simple'],
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
            throw new ExamplesException(sprintf(
                'El ejemplo "%s" no existe.',
                $id
            ));
        }

        $data = (array) Yaml::parseFile($file);
        $example = $data;
        unset($example['Test']);

        return [
            'id' => $id,
            'example' => $example,
            'expected' => $data['Test']['ExpectedValues'] ?? [],
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
