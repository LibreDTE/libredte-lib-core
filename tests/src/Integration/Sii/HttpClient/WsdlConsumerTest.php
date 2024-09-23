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

namespace libredte\lib\Tests\Integration\Sii\HttpClient;

use libredte\lib\Core\Helper\Arr;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\CertificateException;
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Sii\HttpClient\ConnectionConfig;
use libredte\lib\Core\Sii\HttpClient\TokenManager;
use libredte\lib\Core\Sii\HttpClient\SiiClient;
use libredte\lib\Core\Sii\HttpClient\SiiClientException;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentUploader;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentValidator;
use libredte\lib\Core\Sii\HttpClient\WsdlConsumer;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SiiClient::class)]
#[CoversClass(Arr::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
#[CoversClass(SignatureGenerator::class)]
#[CoversClass(XmlSignatureNode::class)]
#[CoversClass(ConnectionConfig::class)]
#[CoversClass(TokenManager::class)]
#[CoversClass(DocumentUploader::class)]
#[CoversClass(DocumentValidator::class)]
#[CoversClass(WsdlConsumer::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
class WsdlConsumerTest extends TestCase
{
    public function testGetSeed(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $siiClient = new SiiClient($certificate);

        $semilla = $siiClient->getWsdlConsumer()->getSeed();
        $this->assertGreaterThan(0, $semilla);
    }

    public function testGetTokenFail(): void
    {
        $this->expectException(SiiClientException::class);

        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $siiClient = new SiiClient($certificate);

        $token = $siiClient->getWsdlConsumer()->getToken(); // Lanzará Excepción.
    }

    public function testGetTokenOk(): void
    {
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

        $token = $siiClient->getWsdlConsumer()->getToken();
        $this->assertIsString($token);
    }
}
