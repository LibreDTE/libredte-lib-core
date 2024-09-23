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

namespace libredte\lib\Tests\Functional\Signature;

use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\SignatureValidator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlException;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SignatureGenerator::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlException::class)]
#[CoversClass(XmlUtils::class)]
#[CoversClass(XmlSignatureNode::class)]
#[CoversClass(SignatureValidator::class)]
class SignatureGeneratorTest extends TestCase
{
    private string $xmlDir;

    protected function setUp(): void
    {
        $this->xmlDir = PathManager::getTestsPath() . '/resources/xml';
    }

    public function testSignXmlString(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();

        $xmlUnsigned = file_get_contents($this->xmlDir . '/unsigned.xml');
        $xmlSigned = SignatureGenerator::signXml($xmlUnsigned, $certificate);

        SignatureValidator::validateXml($xmlSigned);
        $this->assertTrue(true);
    }

    public function testSignXmlObject(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();

        $xmlUnsigned = file_get_contents($this->xmlDir . '/unsigned.xml');
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xmlUnsigned);
        $xmlSigned = SignatureGenerator::signXml($xmlDocument, $certificate);

        SignatureValidator::validateXml($xmlSigned);
        $this->assertTrue(true);
    }

    public function testSignXmlWithReference(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();

        $xmlUnsigned = file_get_contents($this->xmlDir . '/unsigned.xml');
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xmlUnsigned);
        $xmlSigned = SignatureGenerator::signXml($xmlDocument, $certificate, 'LibreDTE_SetDoc');

        SignatureValidator::validateXml($xmlSigned);
        $this->assertTrue(true);
    }

    public function testSignXmlWithInvalidReference(): void
    {
        $this->expectException(XmlException::class);

        $faker = new CertificateFaker();
        $certificate = $faker->create();

        $xmlUnsigned = file_get_contents($this->xmlDir . '/unsigned.xml');
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xmlUnsigned);
        $xmlSigned = SignatureGenerator::signXml($xmlDocument, $certificate, 'LibreDTE_SetDo');
    }
}
