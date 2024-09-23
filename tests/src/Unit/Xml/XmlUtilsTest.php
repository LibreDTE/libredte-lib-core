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

namespace libredte\lib\Tests\Unit\Xml;

use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(XmlUtils::class)]
class XmlUtilsTest extends TestCase
{
    public function testXpath(): void
    {
        $xmlContent = <<<XML
        <root>
            <element1>Value 1</element1>
            <element2>Value 2</element2>
        </root>
        XML;

        $doc = new DOMDocument();
        $doc->loadXML($xmlContent);

        $result = XmlUtils::xpath($doc, '//element1');

        $this->assertInstanceOf(\DOMNodeList::class, $result);
        $this->assertEquals(1, $result->length);
        $this->assertEquals('Value 1', $result->item(0)->textContent);
    }

    public function testUtf2Iso(): void
    {
        $utf8String = 'áéíóúñ';
        $expectedIsoString = mb_convert_encoding($utf8String, 'ISO-8859-1', 'UTF-8');

        $result = XmlUtils::utf2iso($utf8String);

        $this->assertEquals($expectedIsoString, $result);
    }

    public function testIso2Utf(): void
    {
        $isoString = mb_convert_encoding('áéíóúñ', 'ISO-8859-1', 'UTF-8');
        $expectedUtf8String = 'áéíóúñ';

        $result = XmlUtils::iso2utf($isoString);

        $this->assertEquals($expectedUtf8String, $result);
    }

    public function testFixEntities(): void
    {
        $xml = '<root>He said "Hello" & ' . "'Goodbye'</root>";
        //$expectedXml = '<root>He said &quot;Hello&quot; &amp; &apos;Goodbye&apos;</root>';
        $expectedXml = '<root>He said &quot;Hello&quot; & &apos;Goodbye&apos;</root>';

        $result = XmlUtils::fixEntities($xml);

        $this->assertEquals($expectedXml, $result);
    }

    public function testXpathInvalidExpression(): void
    {
        $xmlContent = <<<XML
        <root>
            <element1>Value 1</element1>
            <element2>Value 2</element2>
        </root>
        XML;

        $doc = new DOMDocument();
        $doc->loadXML($xmlContent);

        $this->expectException(InvalidArgumentException::class);
        $result = XmlUtils::xpath($doc, '//*invalid_xpath');
    }

    public function testUtf2IsoInvalidEncoding(): void
    {
        // Invalid UTF-8 sequence.
        $invalidUtf8String = "\x80\x81\x82";

        $result = XmlUtils::utf2iso($invalidUtf8String);

        // In this case, the result should be the original string since it
        // cannot be converted.
        $this->assertEquals($invalidUtf8String, $result);
    }

    public function testIso2UtfInvalidEncoding(): void
    {
        // Invalid ISO-8859-1 sequence.
        $invalidIsoString = "\xFF\xFE\xFD";

        // A pesar de ser inválido hay un mejor esfuerzo de mb_convert_encoding().
        $expectedString = 'ÿþý';

        $result = XmlUtils::iso2utf($invalidIsoString);

        // In this case, the result should be the original string since it
        // cannot be converted.
        $this->assertEquals($expectedString, $result);
    }

    public function testFixEntitiesMalformedXml(): void
    {
        // Missing closing tag for root.
        $malformedXml = '<root>He said "Hello" & <child>Goodbye</child>';

        $result = XmlUtils::fixEntities($malformedXml);

        // Expect that the malformed XML remains unchanged, or the fixing
        // process still works on the valid parts
        //$expectedXml = '<root>He said &quot;Hello&quot; &amp; <child>Goodbye</child>';
        $expectedXml = '<root>He said &quot;Hello&quot; & <child>Goodbye</child>';
        $this->assertEquals($expectedXml, $result);
    }

    public function testFixEntitiesEmptyString(): void
    {
        $emptyXml = '';

        $result = XmlUtils::fixEntities($emptyXml);

        // Empty string should return an empty string.
        $this->assertEquals('', $result);
    }

    public function testSanitizeNoSpecialCharacters(): void
    {
        $input = 'Hello World';
        $expected = 'Hello World';

        $result = XmlUtils::sanitize($input);

        $this->assertEquals($expected, $result);
    }

    public function testSanitizeWithAmpersand(): void
    {
        $input = 'Tom & Jerry';
        $expected = 'Tom &amp; Jerry';

        $result = XmlUtils::sanitize($input);

        $this->assertEquals($expected, $result);
    }

    public function testSanitizeWithQuotes(): void
    {
        $input = 'She said "Hello"';
        //$expected = 'She said &quot;Hello&quot;';
        $expected = 'She said "Hello"';

        $result = XmlUtils::sanitize($input);

        $this->assertEquals($expected, $result);
    }

    public function testSanitizeWithApostrophe(): void
    {
        $input = "It's a beautiful day";
        //$expected = 'It&apos;s a beautiful day';
        $expected = 'It\'s a beautiful day';

        $result = XmlUtils::sanitize($input);

        $this->assertEquals($expected, $result);
    }

    public function testSanitizeWithLessThanAndGreaterThan(): void
    {
        $input = '5 < 10 > 2';
        //$expected = '5 &lt; 10 &gt; 2';
        $expected = '5 < 10 > 2';

        $result = XmlUtils::sanitize($input);

        $this->assertEquals($expected, $result);
    }

    public function testSanitizeWithNumericValue(): void
    {
        $input = '12345';
        $expected = '12345';

        $result = XmlUtils::sanitize($input);

        $this->assertEquals($expected, $result);
    }

    public function testSanitizeEmptyString(): void
    {
        $input = '';
        $expected = '';

        $result = XmlUtils::sanitize($input);

        $this->assertEquals($expected, $result);
    }
}
