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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Document\Support;

use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBatch;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Prueba del contenedor del lote de documentos y de sus orígenes (contenido o
 * archivo).
 */
#[CoversClass(DocumentBatch::class)]
#[CoversClass(BatchProcessorException::class)]
#[CoversClass(DocumentException::class)]
class DocumentBatchTest extends TestCase
{
    public function testSinOrigenLanzaExcepcion(): void
    {
        $this->expectException(BatchProcessorException::class);

        new DocumentBatch();
    }

    public function testConContenidoYArchivoLanzaExcepcion(): void
    {
        $this->expectException(BatchProcessorException::class);

        new DocumentBatch(inputData: 'contenido', inputFile: '/ruta/archivo.csv');
    }

    public function testContenidoEntregaElContenidoYNoTieneArchivo(): void
    {
        $batch = new DocumentBatch(inputData: 'contenido');

        $this->assertSame('contenido', $batch->getInputData());
        $this->assertNull($batch->getInputFile());
    }

    public function testArchivoEntregaLaRutaYLeeElContenido(): void
    {
        $file = self::getFixturesPath('emision_masiva/sin_documentos.csv');
        $batch = new DocumentBatch(inputFile: $file);

        $this->assertSame($file, $batch->getInputFile());
        $this->assertSame(file_get_contents($file), $batch->getInputData());
    }

    public function testArchivoInexistenteLanzaExcepcionAlLeerElContenido(): void
    {
        $batch = new DocumentBatch(inputFile: '/ruta/que/no/existe.csv');

        $this->expectException(BatchProcessorException::class);

        $batch->getInputData();
    }
}
