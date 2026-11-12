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

namespace libredte\lib\Tests\Unit\Package\System\Component\Rendering;

use Derafu\Array\ArrayReader;
use libredte\lib\Core\Package\System\Component\Rendering\Exception\RenderResultException;
use libredte\lib\Core\Package\System\Component\Rendering\Support\RenderedDocument;
use libredte\lib\Core\Package\System\Component\Rendering\Support\RenderResult;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RenderResult::class)]
#[CoversClass(RenderedDocument::class)]
#[CoversClass(RenderResultException::class)]
class RenderResultTest extends TestCase
{
    public function testRenderedDocumentKeepsItsData(): void
    {
        $document = new RenderedDocument('contenido', 'text/plain', 'a.txt', 'texto', 3);

        $this->assertSame('contenido', $document->getContent());
        $this->assertSame('text/plain', $document->getMimeType());
        $this->assertSame('a.txt', $document->getFilename());
        $this->assertSame('texto', $document->getLabel());
        $this->assertSame(3, $document->getCopies());
        $this->assertSame(1, (new RenderedDocument('x', 'text/plain'))->getCopies());
        $this->assertNull((new RenderedDocument('x', 'text/plain'))->getFilename());
        $this->assertNull((new RenderedDocument('x', 'text/plain'))->getLabel());
    }

    public function testRenderedDocumentSerialization(): void
    {
        $document = new RenderedDocument('contenido', 'text/plain', 'a.txt', 'texto');

        $this->assertSame([
            'content' => 'contenido',
            'mime_type' => 'text/plain',
            'filename' => 'a.txt',
            'label' => 'texto',
            'copies' => 1,
        ], $document->toArray());
        $this->assertSame(base64_encode('contenido'), $document->jsonSerialize()['content']);
    }

    public function testRenderingsAreFoundByTheirLabel(): void
    {
        $html = new RenderedDocument('<p/>', 'text/html', 'a.html', 'html');
        $pdf = new RenderedDocument('%PDF', 'application/pdf', 'a.pdf', 'pdf');
        $sinEtiqueta = new RenderedDocument('x', 'text/plain');
        $result = new RenderResult($html, $pdf, $sinEtiqueta);

        $this->assertTrue($result->hasRendering('html'));
        $this->assertFalse($result->hasRendering('xml'));
        $this->assertSame($pdf, $result->getRendering('pdf'));
    }

    public function testRenderingWithALabelThatDoesNotExistThrows(): void
    {
        $result = new RenderResult(new RenderedDocument('x', 'text/plain', null, 'html'));

        $this->expectException(RenderResultException::class);
        $this->expectExceptionMessage('"pdf"');
        $result->getRendering('pdf');
    }

    public function testResultWithASingleRenderingIsItsContent(): void
    {
        $this->assertSame('hola', (string) new RenderResult(new RenderedDocument('hola', 'text/plain')));
    }

    public function testResultWithNoneOrSeveralRenderingsCannotBeAString(): void
    {
        $this->expectException(RenderResultException::class);
        (string) new RenderResult(new RenderedDocument('a', 'text/plain'), new RenderedDocument('b', 'text/plain'));
    }

    public function testResultWithoutRenderingsCannotBeAString(): void
    {
        $this->expectException(RenderResultException::class);
        (string) new RenderResult();
    }

    public function testCopiesAreExpandedWithTheirNumberInTheFilename(): void
    {
        $result = new RenderResult(
            new RenderedDocument('c', 'application/pdf', 'factura.pdf', 'pdf', 2),
            new RenderedDocument('d', 'text/plain', 'sin_extension', 'txt', 2),
            new RenderedDocument('e', 'text/plain', null, null, 2),
            new RenderedDocument('f', 'text/plain', 'una.txt', null, 1),
        );

        $renderings = ArrayReader::fromArray($result->toArray())->requireList('renderings');

        $this->assertSame(
            ['factura_1.pdf', 'factura_2.pdf', 'sin_extension_1', 'sin_extension_2', null, null, 'una.txt'],
            array_map(fn (mixed $r) => ArrayReader::fromValue($r)->getString('filename'), $renderings)
        );
        $this->assertSame(
            [1, 2, 1, 2, 1, 2, 1],
            array_map(fn (mixed $r) => ArrayReader::fromValue($r)->requireAsInt('copy_number'), $renderings)
        );
    }

    public function testJsonSerializationEncodesTheContentOfEveryCopy(): void
    {
        $result = new RenderResult(new RenderedDocument('contenido', 'text/plain', 'a.txt', 'texto', 2));

        $renderings = ArrayReader::fromValue($result->jsonSerialize())->requireList('renderings');

        $this->assertCount(2, $renderings);
        foreach ($renderings as $i => $rendering) {
            $reader = ArrayReader::fromValue($rendering);
            $this->assertSame(base64_encode('contenido'), $reader->requireString('content'));
            $this->assertSame('a_' . ($i + 1) . '.txt', $reader->requireString('filename'));
        }
        $this->assertJson((string) json_encode($result));
    }
}
