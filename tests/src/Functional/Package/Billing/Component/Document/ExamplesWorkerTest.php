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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Document;

use Derafu\Array\ArrayReader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ExamplesWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ExamplesException;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ExamplesWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(ExamplesWorker::class)]
#[CoversClass(ExamplesException::class)]
#[CoversClass(DocumentException::class)]
#[CoversClass(RepositorySourceProvider::class)]
class ExamplesWorkerTest extends TestCase
{
    private ExamplesWorkerInterface $examples;

    private string $tmpDir = '';

    protected function setUp(): void
    {
        $this->examples = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getExamplesWorker()
        ;
    }

    protected function tearDown(): void
    {
        if ($this->tmpDir !== '' && is_dir($this->tmpDir)) {
            $this->removeDir($this->tmpDir);
        }
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Crea un directorio temporal con ejemplos reales (archivos YAML) y
     * entrega el worker apuntando a él.
     *
     * @param array<string, array<string, mixed>|string> $files Contenido por
     * ID de ejemplo (`categoria/caso`).
     */
    private function workerWith(array $files): ExamplesWorker
    {
        $this->tmpDir = sys_get_temp_dir() . '/libredte-examples-' . uniqid('', true);
        mkdir($this->tmpDir . '/examples', 0777, true);

        foreach ($files as $id => $content) {
            $file = $this->tmpDir . '/examples/' . $id . '.yaml';
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }
            file_put_contents(
                $file,
                is_string($content) ? $content : SymfonyYaml::dump($content, 6)
            );
        }

        return new ExamplesWorker($this->tmpDir . '/examples');
    }

    /**
     * @return list<string>
     */
    private function ids(ExamplesWorker $worker): array
    {
        return array_column($worker->list(), 'id');
    }

    public function testListOfRealExamplesHasOneEntryPerFile(): void
    {
        $files = glob(self::getFixturesPath('yaml/documentos_ok') . '/*/*.yaml') ?: [];

        $list = $this->examples->list();

        $this->assertCount(count($files), $list);
        $this->assertCount(count($list), array_unique(array_column($list, 'id')));
        foreach ($list as $example) {
            $this->assertSame($example['category'] . '/' . $example['case'], $example['id']);
            $this->assertFileExists(
                self::getFixturesPath('yaml/documentos_ok') . '/' . $example['id'] . '.yaml'
            );
        }
    }

    public function testListOfRealExamplesPutsEveryDependencyBeforeItsDependant(): void
    {
        $position = array_flip(array_column($this->examples->list(), 'id'));
        $withDependencies = 0;

        foreach ($position as $id => $index) {
            $data = ArrayReader::fromValue(SymfonyYaml::parseFile(
                self::getFixturesPath('yaml/documentos_ok') . '/' . $id . '.yaml'
            ));
            foreach ($data->getStringList('Test.DependsOn') ?? [] as $dependency) {
                $withDependencies++;
                $this->assertArrayHasKey($dependency, $position);
                $this->assertLessThan($index, $position[$dependency], $id . ' depends on ' . $dependency);
            }
        }

        $this->assertGreaterThan(0, $withDependencies);
    }

    public function testGetRealExample(): void
    {
        $example = $this->examples->get('033_factura_afecta/033_001_simple');

        $this->assertSame('033_factura_afecta/033_001_simple', $example['id']);
        $this->assertArrayHasKey('Encabezado', $example['example']);
        $this->assertArrayNotHasKey('Test', $example['example']);
        $this->assertNotEmpty($example['expected']);
    }

    public function testGetExampleThatDoesNotExistThrows(): void
    {
        $this->expectException(ExamplesException::class);
        $this->examples->get('033_factura_afecta/no_existe');
    }

    public function testListIsDeterministicAndAlphabeticalWithoutDependencies(): void
    {
        $worker = $this->workerWith([
            'b/b_2' => ['Encabezado' => []],
            'a/a_2' => ['Encabezado' => []],
            'a/a_1' => ['Encabezado' => []],
            'b/b_1' => ['Encabezado' => []],
        ]);

        $this->assertSame(['a/a_1', 'a/a_2', 'b/b_1', 'b/b_2'], $this->ids($worker));
    }

    public function testListOfEmptyDirectoryIsEmpty(): void
    {
        $this->assertSame([], $this->workerWith([])->list());
    }

    public function testListMovesTheDependantAfterItsDependencies(): void
    {
        $worker = $this->workerWith([
            'a/a_1' => ['Test' => ['DependsOn' => ['b/b_1', 'z/z_1']]],
            'b/b_1' => ['Test' => ['DependsOn' => ['z/z_1']]],
            'c/c_1' => ['Encabezado' => []],
            'z/z_1' => ['Encabezado' => []],
        ]);

        $this->assertSame(['z/z_1', 'b/b_1', 'a/a_1', 'c/c_1'], $this->ids($worker));
    }

    public function testListWithSharedDependencyListsItOnce(): void
    {
        $worker = $this->workerWith([
            'a/a_1' => ['Test' => ['DependsOn' => ['z/z_1']]],
            'b/b_1' => ['Test' => ['DependsOn' => ['z/z_1']]],
            'z/z_1' => ['Encabezado' => []],
        ]);

        $this->assertSame(['z/z_1', 'a/a_1', 'b/b_1'], $this->ids($worker));
    }

    public function testListWithDependencyOnUnknownExampleThrows(): void
    {
        $worker = $this->workerWith([
            'a/a_1' => ['Test' => ['DependsOn' => ['x/no_existe']]],
        ]);

        $this->expectException(ExamplesException::class);
        $this->expectExceptionMessage('x/no_existe');
        $worker->list();
    }

    public function testListWithCircularDependencyThrows(): void
    {
        $worker = $this->workerWith([
            'a/a_1' => ['Test' => ['DependsOn' => ['b/b_1']]],
            'b/b_1' => ['Test' => ['DependsOn' => ['c/c_1']]],
            'c/c_1' => ['Test' => ['DependsOn' => ['a/a_1']]],
        ]);

        $this->expectException(ExamplesException::class);
        $this->expectExceptionMessage('a/a_1');
        $worker->list();
    }

    public function testListWithExampleThatDependsOnItselfThrows(): void
    {
        $worker = $this->workerWith([
            'a/a_1' => ['Test' => ['DependsOn' => ['a/a_1']]],
        ]);

        $this->expectException(ExamplesException::class);
        $worker->list();
    }

    public function testListIgnoresFilesThatAreNotYamlOrAreNotInACategory(): void
    {
        $worker = $this->workerWith([
            'a/a_1' => ['Encabezado' => []],
            'suelto' => ['Encabezado' => []],
        ]);
        file_put_contents($this->tmpDir . '/examples/a/notas.txt', 'no es un ejemplo');

        $this->assertSame(['a/a_1'], $this->ids($worker));
    }

    public function testGetWithoutTestSectionHasNoExpectedValues(): void
    {
        $worker = $this->workerWith([
            'a/a_1' => ['Encabezado' => ['IdDoc' => ['Folio' => 1]]],
        ]);

        $example = $worker->get('a/a_1');

        $this->assertSame(['Encabezado' => ['IdDoc' => ['Folio' => 1]]], $example['example']);
        $this->assertSame([], $example['expected']);
    }

    public function testGetSeparatesTheExampleFromItsExpectedValues(): void
    {
        $worker = $this->workerWith([
            'a/a_1' => [
                'Encabezado' => ['IdDoc' => ['Folio' => 1]],
                'Test' => ['ExpectedValues' => ['Encabezado' => ['Totales' => ['MntTotal' => 119]]]],
            ],
        ]);

        $example = $worker->get('a/a_1');

        $this->assertSame(['Encabezado' => ['IdDoc' => ['Folio' => 1]]], $example['example']);
        $this->assertSame(['Encabezado' => ['Totales' => ['MntTotal' => 119]]], $example['expected']);
    }

    public function testGetDoesNotAllowReadingFilesOutsideTheExamplesDirectory(): void
    {
        $worker = $this->workerWith(['a/a_1' => ['Encabezado' => []]]);

        // Archivo YAML válido, pero fuera del directorio de ejemplos.
        file_put_contents($this->tmpDir . '/secreto.yaml', "clave: valor\n");

        $this->expectException(ExamplesException::class);
        $worker->get('../secreto');
    }

    public function testGetDoesNotAllowReadingASiblingDirectoryWithTheSamePrefix(): void
    {
        $worker = $this->workerWith(['a/a_1' => ['Encabezado' => []]]);

        // `examples_privado` empieza igual que `examples`.
        mkdir($this->tmpDir . '/examples_privado');
        file_put_contents($this->tmpDir . '/examples_privado/x.yaml', "clave: valor\n");

        $this->expectException(ExamplesException::class);
        $worker->get('../examples_privado/x');
    }
}
