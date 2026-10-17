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
 * Pruebas de la validación de la respuesta del SII al enviar un XML.
 *
 * La validación es privada y la respuesta real se obtiene subiendo un archivo
 * al SII, por eso se prueba directamente con la respuesta ya decodificada.
 */
#[CoversClass(SendXmlDocumentJob::class)]
#[UsesClass(SiiException::class)]
class SendXmlDocumentJobTest extends TestCase
{
    /**
     * Valida la respuesta del SII (XML) como se hace al recibirla.
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
