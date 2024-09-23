<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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

namespace libredte\lib\Tests\Unit\Helper;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use libredte\lib\Core\Helper\File;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(File::class)]
class FileTest extends TestCase
{
    private string $testDir;
    private string $testFile;

    protected function setUp(): void
    {
        $this->testDir = __DIR__ . '/testDir';
        $this->testFile = $this->testDir . '/testFile.txt';

        // Create test directory and file.
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->testDir);
        file_put_contents($this->testFile, 'Test content');
    }

    protected function tearDown(): void
    {
        // Clean up test directory and file.
        $filesystem = new Filesystem();
        if ($filesystem->exists($this->testDir)) {
            $filesystem->remove($this->testDir);
        }
    }

    public function testRmdir(): void
    {
        File::rmdir($this->testDir);

        $this->assertDirectoryDoesNotExist($this->testDir);
    }

    public function testMimetype(): void
    {
        $result = File::mimetype($this->testFile);

        $this->assertEquals('text/plain', $result);
    }

    public function testMimetypeFileNotFound(): void
    {
        $result = File::mimetype('/path/to/nonexistent/file.txt');

        $this->assertFalse($result);
    }

    public function testCompressFileSuccess(): void
    {
        $compressedFile = $this->testFile . '.zip';

        File::compress($this->testFile, download: false);

        $this->assertFileExists($compressedFile);
        unlink($compressedFile); // Clean up.
    }

    public function testCompressDirSuccess(): void
    {
        $compressedFile = $this->testDir . '.zip';

        File::compress($this->testDir, download: false);

        $this->assertFileExists($compressedFile);
        unlink($compressedFile); // Clean up.
    }

    public function testCompressFileError(): void
    {
        $this->expectException(RuntimeException::class);

        File::compress('/path/to/nonexistent/file.txt', download: false);
    }
}
