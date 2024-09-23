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

use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\CertificateException;
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CertificateFaker::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateException::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
class CertificateTest extends TestCase
{
    public function testCertificateDefaultData(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $expected = [
            'getID' => '11222333-9',
            'getName' => 'Daniel',
            'getEmail' => 'daniel.bot@example.com',
            'isActive' => true,
            'getIssuer' => 'LibreDTE Autoridad Certificadora de Pruebas',
        ];
        $actual = [
            'getID' => $certificate->getID(),
            'getName' => $certificate->getName(),
            'getEmail' => $certificate->getEmail(),
            'isActive' => $certificate->isActive(),
            'getIssuer' => $certificate->getIssuer(),
        ];
        $this->assertSame($expected, $actual);
    }

    public function testCertificateCreationWithValidSerialNumber(): void
    {
        $faker = new CertificateFaker();
        $faker->setSubject(serialNumber: '1-9');
        $certificate = $faker->create();
        $this->assertSame('1-9', $certificate->getID());
    }

    public function testCertificateCreationWithInvalidSerialNumber(): void
    {
        $faker = new CertificateFaker();
        $faker->setSubject(serialNumber: '1-2');
        $certificate = $faker->create();
        $this->assertNotSame('1-9', $certificate->getID());
    }

    public function testCertificateCreationWithKSerialNumber(): void
    {
        $faker = new CertificateFaker();
        $faker->setSubject(serialNumber: '10-k');
        $certificate = $faker->create();
        $this->assertSame('10-K', $certificate->getID());
    }

    public function testGetModulus(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $modulus = $certificate->getModulus();

        $this->assertNotEmpty($modulus);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\/+=\n]+$/', $modulus);
    }

    public function testGetExponent(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $exponent = $certificate->getExponent();

        $this->assertNotEmpty($exponent);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\/+=]+$/', $exponent);
    }

    public function testGetNameThrowsExceptionForInvalidCertificate(): void
    {
        $this->expectException(CertificateException::class);

        $faker = new CertificateFaker();
        $faker->setSubject(CN: '');
        $certificate = $faker->create();
        $certificate->getName();
    }

    public function testGetEmailThrowsExceptionForInvalidCertificate(): void
    {
        $this->expectException(CertificateException::class);

        $faker = new CertificateFaker();
        $faker->setSubject(emailAddress: '');
        $certificate = $faker->create();
        $certificate->getEmail();
    }

    public function testIsActiveForExpiredCertificate(): void
    {
        $faker = new CertificateFaker();
        $faker->setValidity(validTo: date('Y-m-d', strtotime('-1 year')));
        $certificate = $faker->create();

        $this->assertFalse($certificate->isActive());
    }

    public function testGetExpirationDays(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $days = $certificate->getExpirationDays();

        $this->assertGreaterThan(0, $days);
        $this->assertLessThanOrEqual(365, $days);
    }

    public function testGetDataReturnsParsedCertificateData(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        $data = $certificate->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('issuer', $data);
    }
}
