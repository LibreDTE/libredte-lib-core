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

use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlException;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
#[CoversClass(XmlException::class)]
class XmlDocumentTest extends TestCase
{
    /**
     * Verifica que el documento XML se carga correctamente.
     */
    public function testLoadXml(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $result = $doc->loadXML($xmlContent);

        $this->assertTrue($result);
        $this->assertEquals('root', $doc->documentElement->tagName);
    }

    /**
     * Verifica que se obtenga correctamente el nombre del tag raíz del
     * documento XML.
     */
    public function testGetName(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $this->assertEquals('root', $doc->getName());
    }

    /**
     * Verifica la obtención del espacio de nombres del documento XML cuando
     * existe.
     */
    public function testGetNamespace(): void
    {
        $xmlContent = <<<XML
        <root xmlns="http://example.com">
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $this->assertEquals('http://example.com', $doc->getNamespace());
    }

    /**
     * Verifica la obtención del espacio de nombres del documento XML cuando
     * no existe.
     */
    public function testGetNamespaceNull(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $this->assertNull($doc->getNamespace());
    }

    /**
     * Verifica la obtención del schema asociado al documento XML cuando
     * existe.
     */
    public function testGetSchema(): void
    {
        $xmlContent = <<<XML
        <root xsi:schemaLocation="http://example.com schema.xsd"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $this->assertEquals('schema.xsd', $doc->getSchema());
    }

    /**
     * Verifica la obtención del schema asociado al documento XML cuando no
     * existe.
     */
    public function testGetSchemaNull(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $this->assertNull($doc->getSchema());
    }

    /**
     * Verifica que el método saveXML() genera correctamente el XML.
     */
    public function testSaveXml(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $expectedXml = <<<XML
        <?xml version="1.0" encoding="ISO-8859-1"?>
        <root>
            <element>Value</element>
        </root>

        XML;

        $this->assertXmlStringEqualsXmlString($expectedXml, $doc->saveXML());
    }

    /**
     * Verifica que el método C14N() funcione correctamente, generando la
     * versión canónica del XML.
     */
    public function testC14N(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $canonicalXml = $doc->C14N();

        $this->assertNotEmpty($canonicalXml);
        $expectedXml = "<root>\n    <element>Value</element>\n</root>";
        $this->assertStringContainsString($expectedXml, $canonicalXml);
    }

    /**
     * Verifica que el método C14NWithIsoEncoding() aplane correctamente el documento
     * XML.
     */
    public function testC14NWithIsoEncoding(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $flattenedXml = $doc->C14NWithIsoEncodingFlattened();

        $expectedXml = '<root><element>Value</element></root>';
        $this->assertEquals($expectedXml, $flattenedXml);
    }

    /**
     * Verifica que C14NWithIsoEncoding() funcione correctamente cuando se proporciona
     * una expresión XPath.
     */
    public function testC14NWithIsoEncodingWithXPath(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
            <element2>Other Value</element2>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $flattenedXml = $doc->C14NWithIsoEncoding('//element2');

        $expectedXml = '<element2>Other Value</element2>';
        $this->assertEquals($expectedXml, $flattenedXml);
    }

    /**
     * Verifica que C14NWithIsoEncoding() retorne false cuando la expresión XPath no
     * coincide con ningún nodo.
     */
    public function testC14NWithIsoEncodingXPathNotFound(): void
    {
        $this->expectException(XmlException::class);

        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXML($xmlContent);

        $xml = $doc->C14NWithIsoEncoding('//nonexistent');
    }
}
