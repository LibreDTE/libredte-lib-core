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

namespace libredte\lib\Tests\Integration\Sii\HttpClient\WebService;

use libredte\lib\Core\Helper\Arr;
use libredte\lib\Core\Helper\Rut;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\CertificateException;
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Sii\HttpClient\ConnectionConfig;
use libredte\lib\Core\Sii\HttpClient\SiiClient;
use libredte\lib\Core\Sii\HttpClient\SiiClientException;
use libredte\lib\Core\Sii\HttpClient\TokenManager;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentUploader;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentValidator;
use libredte\lib\Core\Sii\HttpClient\WsdlConsumer;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Arr::class)]
#[CoversClass(Rut::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
#[CoversClass(SignatureGenerator::class)]
#[CoversClass(XmlSignatureNode::class)]
#[CoversClass(ConnectionConfig::class)]
#[CoversClass(SiiClient::class)]
#[CoversClass(TokenManager::class)]
#[CoversClass(DocumentUploader::class)]
#[CoversClass(DocumentValidator::class)]
#[CoversClass(WsdlConsumer::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
class DocumentUploaderTest extends TestCase
{
    private string $xmlDir;

    protected function setUp(): void
    {
        $this->xmlDir = PathManager::getTestsPath() . '/resources/xml';
    }

    public function testSendXmlFakeCertificate(): void
    {
        $this->expectException(SiiClientException::class);

        $company = getenv('LIBREDTE_COMPANY');
        $faker = new CertificateFaker();
        $certificate = $faker->create();

        $siiClient = new SiiClient($certificate, [
            'ambiente' => ConnectionConfig::CERTIFICACION,
        ]);

        // El XML es vacío pues es irrelevante pues fallará por el certificado.
        $xml = new XmlDocument();

        $result = $siiClient->getDocumentUploader()->sendXml($xml, $company);
    }

    public function testSendXmlDteForUploadOkTrackId(): void
    {
        // Cargar certificado digital.
        try {
            $certificate = CertificateLoader::createFromFile(
                getenv('LIBREDTE_CERTIFICATE_FILE'),
                getenv('LIBREDTE_CERTIFICATE_PASS'),
            );
        } catch (CertificateException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $siiClient = new SiiClient($certificate, [
            'ambiente' => ConnectionConfig::CERTIFICACION,
        ]);

        $file = $this->xmlDir . '/dte_for_upload.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped(sprintf('Archivo %s no existe.', $file));
        }
        $xmlDte = file_get_contents($file);
        $xml = new XmlDocument();
        $xml->loadXML($xmlDte);
        $company = getenv('LIBREDTE_COMPANY');

        $trackId = $siiClient->getDocumentUploader()->sendXml($xml, $company);
        $this->assertGreaterThan(0, $trackId);
    }
}
