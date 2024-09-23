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
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Sii\HttpClient\ConnectionConfig;
use libredte\lib\Core\Sii\HttpClient\SiiClient;
use libredte\lib\Core\Sii\HttpClient\TokenManager;
use libredte\lib\Core\Sii\HttpClient\WebService\AbstractWebServiceResponse;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentUploader;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentUploadStatusResponse;
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
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
#[CoversClass(SignatureGenerator::class)]
#[CoversClass(XmlSignatureNode::class)]
#[CoversClass(ConnectionConfig::class)]
#[CoversClass(SiiClient::class)]
#[CoversClass(TokenManager::class)]
#[CoversClass(AbstractWebServiceResponse::class)]
#[CoversClass(DocumentUploadStatusResponse::class)]
#[CoversClass(DocumentUploader::class)]
#[CoversClass(DocumentValidator::class)]
#[CoversClass(WsdlConsumer::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
class DocumentUploadStatusTest extends TestCase
{
    private SiiClient $siiClient;

    protected function setUp(): void
    {
        try {
            $certificate = CertificateLoader::createFromFile(
                getenv('LIBREDTE_CERTIFICATE_FILE'),
                getenv('LIBREDTE_CERTIFICATE_PASS'),
            );
        } catch (CertificateException $e) {
            $this->markTestSkipped($e->getMessage());
        }
        $this->siiClient = new SiiClient($certificate, [
            'ambiente' => ConnectionConfig::CERTIFICACION,
        ]);
    }

    public function testUploadStatusRfr(): void
    {
        $trackId = 230939274; // Error de firma.
        $company = '76192083-9';
        $documentUploadStatus = $this->siiClient
            ->getDocumentValidator()
            ->getDocumentUploadStatus($trackId, $company)
        ;
        $this->assertSame('RFR', $documentUploadStatus->getStatus());
    }

    public function testUploadStatusRfr2(): void
    {
        $trackId = 231039586; // Error de firma.
        $company = '76192083-9';
        $documentUploadStatus = $this->siiClient
            ->getDocumentValidator()
            ->getDocumentUploadStatus($trackId, $company)
        ;
        $this->assertSame('RFR - Rechazado por Error en Firma', $documentUploadStatus->getReviewStatus());
    }

    public function testUploadStatusWrongEnvironment(): void
    {
        $trackId = 9934380406; // Es de Producción.
        $company = '76192083-9';
        $documentUploadStatus = $this->siiClient
            ->getDocumentValidator()
            ->getDocumentUploadStatus($trackId, $company)
        ;
        $this->assertSame('-11', $documentUploadStatus->getStatus());
        $this->assertSame('Error de Proceso', $documentUploadStatus->getDescription());
    }

    public function testUploadStatusEpr(): void
    {
        $trackId = 76941002; // Set de pruebas con 8 documentos.
        $company = '76192083-9';
        $documentUploadStatus = $this->siiClient
            ->getDocumentValidator()
            ->getDocumentUploadStatus($trackId, $company)
        ;
        $this->assertSame('EPR - Envio Procesado', $documentUploadStatus->getReviewStatus());
    }
}
