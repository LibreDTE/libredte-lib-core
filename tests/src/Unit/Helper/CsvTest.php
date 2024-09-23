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

namespace libredte\lib\Tests\Unit\Helper;

use LogicException;
use League\Csv\UnavailableStream;
use libredte\lib\Core\Helper\Csv;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Csv::class)]
class CsvTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        $this->testFile = __DIR__ . '/test.csv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testReadCsvSuccess(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', 'value2'],
            ['value3', 'value4'],
        ];

        Csv::write($data, $this->testFile);

        $result = Csv::read($this->testFile);

        $this->assertEquals($data, $result);
    }

    public function testReadCsvFileNotFound(): void
    {
        $this->expectException(UnavailableStream::class);

        Csv::read('/path/to/nonexistent/file.csv');
    }

    public function testGenerateCsvSuccess(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', 'value2'],
            ['value3', 'value4'],
        ];

        $result = Csv::generate($data);

        $expectedOutput = 'column1;column2' . "\n" .
                          'value1;value2' . "\n" .
                          'value3;value4' . "\n";

        $this->assertEquals($expectedOutput, $result);
    }

    public function testWriteCsvSuccess(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', 'value2'],
            ['value3', 'value4'],
        ];

        Csv::write($data, $this->testFile);

        $this->assertFileExists($this->testFile);
        $this->assertEquals($data, Csv::read($this->testFile));
    }

    public function testWriteCsvFileError(): void
    {
        $this->expectException(LogicException::class);

        Csv::write([], '/invalid/path/to/file.csv');
    }

    public function testSendCsvSuccess(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', 'value2'],
            ['value3', 'value4'],
        ];

        ob_start();
        Csv::send($data, $this->testFile, sendHttpHeaders: false);
        $result = ob_get_clean();

        $this->assertEquals($data, Csv::load($result));
    }

    public function testGenerateCsvWithSpecialCharacters(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value;"with;semicolon"', 'value2'],
            ['value "with quotes"', 'value with spaces'],
            ['value with newline', "value\nwith\nnewlines"],
        ];

        $result = Csv::generate($data, ';', '"');

        $expectedOutput = 'column1;column2' . "\n" .
                        '"value;""with;semicolon""";value2' . "\n" .
                        '"value ""with quotes""";"value with spaces"' . "\n" .
                        '"value with newline";"value' . "\n" . 'with' . "\n" . 'newlines"' . "\n";

        $this->assertEquals($expectedOutput, $result);
    }

    public function testReadCsvWithSpecialCharacters(): void
    {
        $csvContent = 'column1;column2' . "\n" .
                    '"value;""with;semicolon""";value2' . "\n" .
                    '"value ""with quotes""";value with spaces' . "\n" .
                    'value with newline;"value' . "\n" . 'with' . "\n" . 'newlines"' . "\n";

        file_put_contents($this->testFile, $csvContent);

        $expectedData = [
            ['column1', 'column2'],
            ['value;"with;semicolon"', 'value2'],
            ['value "with quotes"', 'value with spaces'],
            ['value with newline', "value\nwith\nnewlines"],
        ];

        $result = Csv::read($this->testFile);

        $this->assertEquals($expectedData, $result);
    }

    public function testGenerateCsvWithEmptyFields(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', ''],
            ['', 'value2'],
        ];

        $result = Csv::generate($data, ';', '"');

        $expectedOutput = 'column1;column2' . "\n" .
                        'value1;' . "\n" .
                        ';value2' . "\n";

        $this->assertEquals($expectedOutput, $result);
    }

    public function testReadCsvWithEmptyFields(): void
    {
        $csvContent = 'column1;column2' . "\n" .
                    'value1;' . "\n" .
                    ';value2' . "\n";

        file_put_contents($this->testFile, $csvContent);

        $expectedData = [
            ['column1', 'column2'],
            ['value1', ''],
            ['', 'value2'],
        ];

        $result = Csv::read($this->testFile);

        $this->assertEquals($expectedData, $result);
    }
}
