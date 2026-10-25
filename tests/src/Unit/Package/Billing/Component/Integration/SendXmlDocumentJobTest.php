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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Integration;

use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\SendXmlDocumentException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiException;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\SendXmlDocumentJob;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;

/**
 * Tests for validating the SII response when sending an XML.
 *
 * Validation is private and the real response is obtained by uploading a file
 * to the SII, so it is tested directly with the already decoded response.
 */
#[CoversClass(SendXmlDocumentJob::class)]
#[UsesClass(SiiException::class)]
class SendXmlDocumentJobTest extends TestCase
{
    /**
     * Validates the SII response (XML) as when it is received.
     */
    private function validate(string $xml): void
    {
        $document = new XmlDocument();
        $document->loadXml($xml);
        $response = (new XmlService(new XmlEncoder(), new XmlDecoder(), new XmlValidator()))
            ->decode($document)
        ;

        $job = (new \ReflectionClass(SendXmlDocumentJob::class))->newInstanceWithoutConstructor();
        $validate = new ReflectionMethod($job, 'validateUploadXmlResponse');
        $validate->invoke($job, $this->createStub(SiiRequestInterface::class), $response);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function responsesWithoutStatusProvider(): array
    {
        return [
            'without the element' => ['<RECEPCIONDTE><TRACKID>1</TRACKID></RECEPCIONDTE>'],
            'with the element empty' => ['<RECEPCIONDTE><STATUS/><TRACKID>1</TRACKID></RECEPCIONDTE>'],
        ];
    }

    #[DataProvider('responsesWithoutStatusProvider')]
    public function testAResponseWithoutStatusIsNotValid(string $xml): void
    {
        $this->expectException(SendXmlDocumentException::class);
        $this->expectExceptionMessage(
            'La respuesta del envío del XML al SII no trae un código de estado válido.'
        );

        $this->validate($xml);
    }

    public function testAnUploadWithStatusZeroIsValid(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validate('<RECEPCIONDTE><STATUS>0</STATUS><TRACKID>1</TRACKID></RECEPCIONDTE>');
    }

    public function testAnUploadWithAnErrorStatusThrowsItsMessage(): void
    {
        $this->expectException(SendXmlDocumentException::class);
        $this->expectExceptionMessage('El esquema del XML es inválido.');

        $this->validate('<RECEPCIONDTE><STATUS>7</STATUS></RECEPCIONDTE>');
    }
}
