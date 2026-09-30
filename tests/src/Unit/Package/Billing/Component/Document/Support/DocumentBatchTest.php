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
