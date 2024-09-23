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

namespace libredte\lib\Tests\Functional\Sii\HttpClient;

use libredte\lib\Core\Helper\Arr;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Sii\HttpClient\ConnectionConfig;
use libredte\lib\Core\Sii\HttpClient\TokenManager;
use libredte\lib\Core\Sii\HttpClient\SiiClient;
use libredte\lib\Core\Sii\HttpClient\WsdlConsumer;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentUploader;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(WsdlConsumer::class)]
#[CoversClass(Arr::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
#[CoversClass(ConnectionConfig::class)]
#[CoversClass(TokenManager::class)]
#[CoversClass(DocumentUploader::class)]
#[CoversClass(DocumentValidator::class)]
#[CoversClass(SiiClient::class)]
class WsdlConsumerTest extends TestCase
{
    public function testConfigGetAmbientePorDefecto(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $siiClient = new SiiClient($certificate);

        $ambiente = $siiClient->getConfig()->getAmbiente();
        $this->assertEquals(0, $ambiente); // 0 es Producción.
    }

    public function testConfigGetWsdlCrSeedPalena(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $siiClient = new SiiClient($certificate);

        $expected = 'https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL';
        $ambiente = $siiClient->getWsdlConsumer()->getWsdlUri('CrSeed');
        $this->assertEquals($expected, $ambiente);
    }

    public function testConfigGetWsdlCrSeedMaullin(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $siiClient = new SiiClient($certificate, [
            'ambiente' => ConnectionConfig::CERTIFICACION,
        ]);

        $suffix = 'maullin/CrSeed.wsdl';
        $ambiente = $siiClient->getWsdlConsumer()->getWsdlUri('CrSeed');
        $this->assertStringEndsWith($suffix, $ambiente);
    }
}
