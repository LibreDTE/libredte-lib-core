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

namespace libredte\lib\Tests\Functional\Xml;

use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlException;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlException::class)]
#[CoversClass(XmlUtils::class)]
class XmlConverterTest extends TestCase
{
    /**
     * Atributo con los casos para cada test.
     *
     * @var array<string, array>
     */
    private static array $testCases;

    /**
     * Entrega los casos según el nombre del test.
     *
     * Este método lo utiliza cada proveedor de datos para obtener los datos de
     * los tests.
     *
     * @return array<string, array>
     */
    private static function dataProvider(string $testName): array
    {
        if (!isset(self::$testCases)) {
            $testsPath = PathManager::getTestsPath();
            self::$testCases = require $testsPath
                . '/resources/use_cases/xml_converter.php'
            ;
        }

        if (!isset(self::$testCases[$testName])) {
            self::fail(sprintf(
                'El test %s() no tiene casos asociados en el dataProvider().',
                $testName
            ));
        }

        return self::$testCases[$testName];
    }

    public static function arrayToXmlAndBackToArrayDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlAndBackToArray');
    }

    public static function arrayToXmlSaveXmlDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlSaveXml');
    }

    public static function arrayToXmlC14NDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlC14N');
    }

    public static function arrayToXmlC14NWithIsoEncodingDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlC14NWithIsoEncoding');
    }

    public static function xmlToArrayDataProvider(): array
    {
        return self::dataProvider('testXmlToArray');
    }

    public static function xmlToSaveXmlDataProvider(): array
    {
        return self::dataProvider('testXmlToSaveXml');
    }

    public static function xmlToC14NDataProvider(): array
    {
        return self::dataProvider('testXmlToC14N');
    }

    public static function xmlToC14NWithIsoEncodingDataProvider(): array
    {
        return self::dataProvider('testXmlToC14NWithIsoEncoding');
    }

    /**
     * Convierte un arreglo a un XmlDocument y luego de vuelta a un arreglo,
     * asegurando que la estructura original se mantiene.
     */
    #[DataProvider('arrayToXmlAndBackToArrayDataProvider')]
    public function testArrayToXmlAndBackToArray(array $data, array $expected, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xmlDocument = XmlConverter::arrayToXml($data);
        $arrayData = XmlConverter::xmlToArray($xmlDocument);

        // Validar estructura
        $this->assertEquals($expected, $arrayData);

        // Validar codificación en cada valor del arreglo
        $this->assertArrayEncoding($arrayData, 'UTF-8');
    }

    /**
     * Convierte un arreglo a un XmlDocument y lo guarda como un string XML
     * con saveXML(), asegurando que la codificación y contenido son correctos.
     */
    #[DataProvider('arrayToXmlSaveXmlDataProvider')]
    public function testArrayToXmlSaveXml(array $data, string $expected, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xmlDocument = XmlConverter::arrayToXml($data);
        $xmlString = $xmlDocument->saveXML();

        // Validar contenido
        $this->assertEquals($expected, $xmlString);

        // Validar codificación
        $this->assertEquals('ISO-8859-1', mb_detect_encoding($xmlString, 'ISO-8859-1', true));
    }

    /**
     * Convierte un arreglo a un XmlDocument y lo guarda como un string XML
     * con C14N(), asegurando que el contenido sea correcto.
     */
    #[DataProvider('arrayToXmlC14NDataProvider')]
    public function testArrayToXmlC14N(array $data, string $expected, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xmlDocument = XmlConverter::arrayToXml($data);
        $xmlString = $xmlDocument->C14N();
        $xmlString = XmlUtils::fixEntities($xmlString);

        // Validar contenido
        $this->assertEquals($expected, $xmlString);
    }

    /**
     * Convierte un arreglo a un XmlDocument y lo guarda como un string XML
     * con testArrayToXmlC14NWithIsoEncoding(), asegurando que la codificación
     * y contenido son correctos.
     */
    #[DataProvider('arrayToXmlC14NWithIsoEncodingDataProvider')]
    public function testArrayToXmlC14NWithIsoEncoding(array $data, string $expected, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xmlDocument = XmlConverter::arrayToXml($data);
        $xmlString = $xmlDocument->C14NWithIsoEncoding();

        // Validar contenido
        $this->assertEquals($expected, $xmlString);

        // Validar codificación
        $this->assertEquals('ISO-8859-1', mb_detect_encoding($xmlString, 'ISO-8859-1', true));
    }

    /**
     * Convierte un string XML a un XmlDocument y luego a un arreglo,
     * asegurando que la estructura se mantiene y los datos están en la
     * codificación correcta.
     */
    #[DataProvider('xmlToArrayDataProvider')]
    public function testXmlToArray(string $xmlContent, array $expected, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);
        $arrayData = XmlConverter::xmlToArray($doc);

        // Validar estructura
        $this->assertEquals($expected, $arrayData);

        // Validar codificación en cada valor del arreglo
        $this->assertArrayEncoding($arrayData, 'UTF-8');
    }

    /**
     * Convierte un string XML a un XmlDocument y lo guarda como un string XML
     * con saveXML(), asegurando que la codificación y contenido son correctos.
     */
    #[DataProvider('xmlToSaveXmlDataProvider')]
    public function testXmlToSaveXml(string $xmlContent, string $expected, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);
        $xmlString = $doc->saveXML();

        // Validar contenido
        $this->assertEquals($expected, $xmlString);

        // Validar codificación
        $this->assertEquals('ISO-8859-1', mb_detect_encoding($xmlString, 'ISO-8859-1', true));
    }

    /**
     * Convierte un string XML a un XmlDocument y lo guarda como un string XML
     * con C14N(), asegurando que el contenido sea correcto.
     */
    #[DataProvider('xmlToC14NDataProvider')]
    public function testXmlToC14N(string $xmlContent, string $expected, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);
        $xmlString = $doc->C14N();
        $xmlString = XmlUtils::fixEntities($xmlString);

        // Validar contenido
        $this->assertEquals($expected, $xmlString);
    }

    /**
     * Convierte un string XML a un XmlDocument y lo guarda como un string XML
     * con C14NWithIsoEncoding(), asegurando que la codificación y contenido
     * son correctos.
     */
    #[DataProvider('xmlToC14NWithIsoEncodingDataProvider')]
    public function testXmlToC14NWithIsoEncoding(string $xmlContent, string $expected, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);
        $xmlString = $doc->C14NWithIsoEncoding();

        // Validar contenido
        $this->assertEquals($expected, $xmlString);

        // Validar codificación
        $this->assertEquals('ISO-8859-1', mb_detect_encoding($xmlString, 'ISO-8859-1', true));
    }

    /**
     * Función auxiliar para verificar la codificación de los valores en un arreglo.
     *
     * @param array $data Arreglo que contiene los valores a verificar.
     * @param string $expectedEncoding Codificación esperada (por ejemplo, UTF-8).
     */
    private function assertArrayEncoding(array $data, string $expectedEncoding): void
    {
        array_walk_recursive($data, function($item) use ($expectedEncoding) {
            if (is_string($item)) {
                $this->assertEquals(
                    $expectedEncoding,
                    mb_detect_encoding($item, $expectedEncoding, true)
                );
            }
        });
    }
}
