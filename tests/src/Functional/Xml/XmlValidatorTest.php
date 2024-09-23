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
use libredte\lib\Core\Xml\XmlValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlValidator::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlException::class)]
class XmlValidatorTest extends TestCase
{
    /**
     * Verifica que la validación del XML contra el esquema pase correctamente.
     */
    public function testValidateSchemaSuccess(): void
    {
        // Crear el esquema XML (XSD).
        $xsdSchema = <<<XSD
        <?xml version="1.0" encoding="UTF-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="root">
                <xs:complexType>
                    <xs:sequence>
                        <xs:element name="element" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:element>
        </xs:schema>
        XSD;

        // Guardar el esquema en un archivo temporal.
        $schemaPath = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($schemaPath, $xsdSchema);

        // Crear un XML válido según el esquema.
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        // Cargar el XML en un XmlDocument.
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXML($xmlContent);

        // Validar el XML contra el esquema.
        try {
            XmlValidator::validateSchema($xmlDocument, $schemaPath);
            $this->assertTrue(true); // Si no lanza excepción, la validación pasó.
        } catch (XmlException $e) {
            $message = sprintf(
                'La validación del XML no debería fallar, pero ocurrió un error: %s',
                $e->getMessage()
            );
            $this->fail($message);
        }

        // Eliminar el archivo temporal del esquema.
        unlink($schemaPath);
    }

    /**
     * Verifica que la validación del XML contra el esquema falle correctamente.
     */
    public function testValidateSchemaFailure(): void
    {
        // Crear el esquema XML (XSD).
        $xsdSchema = <<<XSD
        <?xml version="1.0" encoding="UTF-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="root">
                <xs:complexType>
                    <xs:sequence>
                        <xs:element name="element" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:element>
        </xs:schema>
        XSD;

        // Guardar el esquema en un archivo temporal.
        $schemaPath = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($schemaPath, $xsdSchema);

        // Crear un XML inválido según el esquema.
        $invalidXmlContent = <<<XML
        <root>
            <wrongElement>Value</wrongElement>
        </root>
        XML;

        // Cargar el XML en un XmlDocument.
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXML($invalidXmlContent);

        // Validar el XML contra el esquema y esperar que falle.
        $this->expectException(XmlException::class);
        XmlValidator::validateSchema($xmlDocument, $schemaPath);

        // Eliminar el archivo temporal del esquema.
        unlink($schemaPath);
    }
}
