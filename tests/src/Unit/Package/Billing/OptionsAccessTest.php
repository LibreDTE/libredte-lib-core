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
    public function testDocumentSectionsPreserveNestedArrays(): void
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

    public function testMissingOrNullOptionalSectionsReturnEmptyArray(): void
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

    public function testBookSectionsPreserveCustomOptions(): void
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

    public function testBatchAndEnvelopePreserveDefaultAndCustomOptions(): void
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
