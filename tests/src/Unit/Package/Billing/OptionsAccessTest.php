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

namespace libredte\lib\Tests\Unit\Package\Billing;

use Derafu\Config\Options;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Support\BookBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBatch;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BookBag::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(DocumentBatch::class)]
#[CoversClass(DocumentEnvelope::class)]
class OptionsAccessTest extends TestCase
{
    public function testSeccionesDelDocumentoConservanArreglosAnidados(): void
    {
        $section = ['strategy' => 'custom', 'config' => ['enabled' => false, 'values' => [1, null]]];
        $bag = new DocumentBag(options: array_fill_keys([
            'parser', 'builder', 'normalizer', 'sanitizer', 'validator', 'renderer',
        ], $section));

        $this->assertSame($section, $bag->getParserOptions());
        $this->assertSame($section, $bag->getBuilderOptions());
        $this->assertSame($section, $bag->getNormalizerOptions());
        $this->assertSame($section, $bag->getSanitizerOptions());
        $this->assertSame($section, $bag->getValidatorOptions());
        $this->assertSame($section, $bag->getRendererOptions());
    }

    public function testSeccionesOpcionalesAusentesONullEntreganArregloVacio(): void
    {
        foreach ([new Options(), new Options([
            'parser' => null, 'builder' => null, 'normalizer' => null,
            'sanitizer' => null, 'validator' => null, 'renderer' => null,
        ])] as $options) {
            $bag = new DocumentBag(options: $options);
            $this->assertSame([], $bag->getParserOptions());
            $this->assertSame([], $bag->getBuilderOptions());
            $this->assertSame([], $bag->getNormalizerOptions());
            $this->assertSame([], $bag->getSanitizerOptions());
            $this->assertSame([], $bag->getValidatorOptions());
            $this->assertSame([], $bag->getRendererOptions());

            $book = new BookBag(TipoLibro::VENTAS, options: $options);
            $this->assertSame([], $book->getLoaderOptions());
            $this->assertSame([], $book->getBuilderOptions());
            $this->assertSame([], $book->getRendererOptions());
            $this->assertSame([], $book->getValidatorOptions());
        }
    }

    public function testSeccionesDelLibroConservanOpcionesPersonalizadas(): void
    {
        $loader = ['format' => 'csv', 'csv' => ['delimiter' => ';']];
        $builder = ['encoding' => 'ISO-8859-1'];
        $book = new BookBag(TipoLibro::VENTAS, options: [
            'loader' => $loader,
            'builder' => $builder,
        ]);

        $this->assertSame($loader, $book->getLoaderOptions());
        $this->assertSame($builder, $book->getBuilderOptions());
        $this->assertSame([], $book->getRendererOptions());
        $this->assertSame([], $book->getValidatorOptions());
    }

    public function testLoteYSobreConservanOpcionesPredeterminadasYPersonalizadas(): void
    {
        $this->assertSame([], (new DocumentBatch(inputData: 'contenido'))->getBatchProcessorOptions());
        $this->assertSame([], (new DocumentEnvelope())->getDispatcherOptions());

        $section = ['strategy' => 'custom', 'config' => ['enabled' => false]];
        $batch = new DocumentBatch(inputData: 'contenido', options: ['batch_processor' => $section]);
        $envelope = (new DocumentEnvelope())->setOptions(['dispatcher' => $section]);

        $this->assertSame($section, $batch->getBatchProcessorOptions());
        $this->assertSame($section, $envelope->getDispatcherOptions());
    }
}
