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
use libredte\lib\Core\Signature\CertificateException;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Signature\SignatureException;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\SignatureValidator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SignatureValidator::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(CertificateUtils::class)]
#[CoversClass(SignatureGenerator::class)]
#[CoversClass(XmlSignatureNode::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
#[CoversClass(CertificateException::class)]
class SignatureValidatorTest extends TestCase
{
    private string $xmlDir;

    protected function setUp(): void
    {
        $this->xmlDir = PathManager::getTestsPath() . '/resources/xml';
    }

    public function testValidXmlSignature(): void
    {
        $xml = file_get_contents($this->xmlDir . '/valid_signed.xml');
        SignatureValidator::validateXml($xml);
        $this->assertTrue(true);
    }

    public function testInvalidXmlSignature(): void
    {
        $this->expectException(SignatureException::class);

        $xml = file_get_contents($this->xmlDir . '/invalid_signed.xml');
        SignatureValidator::validateXml($xml);
    }
}
