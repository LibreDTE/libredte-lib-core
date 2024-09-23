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

 namespace libredte\lib\Tests\Unit\Signature;

use libredte\lib\Core\Signature\CertificateUtils;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CertificateUtils::class)]
class CertificateUtilsTest extends TestCase
{
    /**
     * Verifica que `normalizePublicKey` agregue correctamente los encabezados
     * y pies cuando el certificado no los tiene.
     */
    public function testNormalizePublicKeyWithoutHeaders(): void
    {
        $certBody = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...";
        $expectedCert = "-----BEGIN CERTIFICATE-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh\n...\n-----END CERTIFICATE-----\n";

        $normalizedCert = CertificateUtils::normalizePublicKey($certBody);

        $this->assertEquals($expectedCert, $normalizedCert);
    }

    /**
     * Verifica que `normalizePublicKey` no modifique un certificado que ya
     * tiene los encabezados y pies.
     */
    public function testNormalizePublicKeyWithHeaders(): void
    {
        $cert = <<<CERT
        -----BEGIN CERTIFICATE-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...
        -----END CERTIFICATE-----
        CERT;

        $normalizedCert = CertificateUtils::normalizePublicKey($cert);

        $this->assertEquals($cert, $normalizedCert);
    }

    /**
     * Verifica que `normalizePublicKey` respete el `wordwrap` al agregar
     * encabezados y pies.
     */
    public function testNormalizePublicKeyWithCustomWordwrap(): void
    {
        $certBody = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...";
        $wordwrap = 10;
        $expectedCert = "-----BEGIN CERTIFICATE-----\nMIIBIjANBg\nkqhkiG9w0B\nAQEFAAOCAQ\n8AMIIBCgKC\nAQEA7sN2a9\nz8/PQleNzl\n+Tbh...\n-----END CERTIFICATE-----\n";

        $normalizedCert = CertificateUtils::normalizePublicKey($certBody, $wordwrap);

        $this->assertEquals($expectedCert, $normalizedCert);
    }

    /**
     * Verifica que `normalizePrivateKey` agregue correctamente los encabezados
     * y pies cuando el certificado no los tiene.
     */
    public function testNormalizePrivateKeyWithoutHeaders(): void
    {
        $certBody = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...";
        $expectedCert = "-----BEGIN PRIVATE KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh\n...\n-----END PRIVATE KEY-----\n";

        $normalizedCert = CertificateUtils::normalizePrivateKey($certBody);

        $this->assertEquals($expectedCert, $normalizedCert);
    }

    /**
     * Verifica que `normalizePrivateKey` no modifique un certificado que ya tiene
     * los encabezados y pies.
     */
    public function testNormalizePrivateKeyWithHeaders(): void
    {
        $cert = <<<CERT
        -----BEGIN PRIVATE KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...
        -----END PRIVATE KEY-----
        CERT;

        $normalizedCert = CertificateUtils::normalizePrivateKey($cert);

        $this->assertEquals($cert, $normalizedCert);
    }

    /**
     * Verifica que `normalizePrivateKey` respete el `wordwrap` al agregar
     * encabezados y pies.
     */
    public function testNormalizePrivateKeyWithCustomWordwrap(): void
    {
        $certBody = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...";
        $wordwrap = 10;
        $expectedCert = "-----BEGIN PRIVATE KEY-----\nMIIBIjANBg\nkqhkiG9w0B\nAQEFAAOCAQ\n8AMIIBCgKC\nAQEA7sN2a9\nz8/PQleNzl\n+Tbh...\n-----END PRIVATE KEY-----\n";

        $normalizedCert = CertificateUtils::normalizePrivateKey($certBody, $wordwrap);

        $this->assertEquals($expectedCert, $normalizedCert);
    }

    /**
     * Verifica que `generatePublicKeyFromModulusExponent` genere correctamente
     * una clave pública a partir del módulo y exponente.
     */
    public function testGeneratePublicKeyFromModulusExponent(): void
    {
        // Estos valores son solo ejemplos; en la práctica usarías valores reales.
        $modulus = base64_encode((new BigInteger('1234567890'))->toBytes());
        $exponent = base64_encode((new BigInteger('65537'))->toBytes());

        // Generar la clave pública esperada manualmente.
        $rsa = PublicKeyLoader::load([
            'n' => new BigInteger(base64_decode($modulus), 256),
            'e' => new BigInteger(base64_decode($exponent), 256),
        ]);
        $expectedPublicKey = $rsa->toString('PKCS1');

        $publicKey = CertificateUtils::generatePublicKeyFromModulusExponent($modulus, $exponent);

        $this->assertEquals($expectedPublicKey, $publicKey);
    }
}
