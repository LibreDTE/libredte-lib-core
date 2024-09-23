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
use libredte\lib\Core\Helper\Date;
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
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentSignatureStatusResponse;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentUploader;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentValidator;
use libredte\lib\Core\Sii\HttpClient\WsdlConsumer;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Arr::class)]
#[CoversClass(Date::class)]
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
#[CoversClass(DocumentSignatureStatusResponse::class)]
#[CoversClass(DocumentUploader::class)]
#[CoversClass(DocumentValidator::class)]
#[CoversClass(WsdlConsumer::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
class DocumentSignatureStatusTest extends TestCase
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

    public function testStatusDok(): void
    {
        $signature = 'pw4Cr2nD7kFpaWjgrw543UfGfg2VnE5bezV9qQn7AZlhgW78w94ldrauKSFjr1BC4CYXBvtttHQpPN4r2aCX4+j/D+4w32INaO61DzvtVu6Qt3SpJZhCdCYc02Z7yFGZHPFQQi4Drjcqlt0WpoigUARWFp7DuFoQOBxEKYP1l41S3jSt1/5QwtA9HQvOqpU8hK4ub29HSFVETQFCEhBRRnBPvQeXflQOarjFb/xqFs0/I3YQNwsXLwfEJ68YKY8bAOlRwTuWayA8Om+xwkCnOibspGIMLhXLZBM4SqSDJPdiCyFGDs7irqsnIRZSe386Us+WFYFCs58nWmB+vFwTrA==';
        $documentStatus = $this->siiClient
            ->getDocumentValidator()
            ->getDocumentSignatureStatus(
                '76192083-9',
                33,
                747,
                '2020-01-14',
                5699863,
                '76192083-9',
                $signature,
            )
        ;
        $this->assertEquals('DOK', $documentStatus->getStatus());
    }

    public function testStatusDnk(): void
    {
        $signature = 'pw4Cr2nD7kFpaWjgrw543UfGfg2VnE5bezV9qQn7AZlhgW78w94ldrauKSFjr1BC4CYXBvtttHQpPN4r2aCX4+j/D+4w32INaO61DzvtVu6Qt3SpJZhCdCYc02Z7yFGZHPFQQi4Drjcqlt0WpoigUARWFp7DuFoQOBxEKYP1l41S3jSt1/5QwtA9HQvOqpU8hK4ub29HSFVETQFCEhBRRnBPvQeXflQOarjFb/xqFs0/I3YQNwsXLwfEJ68YKY8bAOlRwTuWayA8Om+xwkCnOibspGIMLhXLZBM4SqSDJPdiCyFGDs7irqsnIRZSe386Us+WFYFCs58nWmB+vFwTrA==XXX';
        $documentStatus = $this->siiClient
            ->getDocumentValidator()
            ->getDocumentSignatureStatus(
                '76192083-9',
                33,
                747,
                '2020-01-14',
                5699863,
                '76192083-9',
                $signature,
            )
        ;
        $this->assertEquals('DNK', $documentStatus->getStatus());
    }
}
