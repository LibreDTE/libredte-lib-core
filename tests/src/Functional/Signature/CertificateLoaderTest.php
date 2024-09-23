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
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Signature\CertificateException;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CertificateLoader::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateException::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateUtils::class)]
class CertificateLoaderTest extends TestCase
{
    public function testCreateFromFile(): void
    {
        $faker = new CertificateFaker();
        $data = $faker->createAsString();
        $tempFile = tempnam(sys_get_temp_dir(), 'cert');
        file_put_contents($tempFile, $data);
        $certificate = CertificateLoader::createFromFile(
            $tempFile,
            $faker->getPassword()
        );
        $this->assertInstanceOf(Certificate::class, $certificate);
        unlink($tempFile);
    }

    public function testCreateFromData(): void
    {
        $faker = new CertificateFaker();
        $data = $faker->createAsString();
        $certificate = CertificateLoader::createFromData(
            $data,
            $faker->getPassword()
        );
        $this->assertInstanceOf(Certificate::class, $certificate);
    }

    public function testCreateFromArray(): void
    {
        $faker = new CertificateFaker();
        $certs = $faker->createAsArray();
        $certificate = CertificateLoader::createFromArray($certs);
        $this->assertInstanceOf(Certificate::class, $certificate);
    }

    /**
     * Asegura que se lance una excepción cuando se intenta cargar un archivo
     * de certificado que no es legible.
     */
    public function testCreateFromFileThrowsExceptionForUnreadableFile(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('No fue posible leer el archivo del certificado digital desde');
        CertificateLoader::createFromFile('/path/no/existe/cert.p12', 'testpass');
    }

    /**
     * Valida que se lance una excepción cuando se intenta cargar un
     * certificado desde datos corruptos o no válidos.
     */
    public function testCreateFromDataThrowsExceptionForInvalidData(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('No fue posible leer los datos del certificado digital.');
        $invalidData = 'datos_corruptos';
        CertificateLoader::createFromData($invalidData, 'testpass');
    }

    /**
     * Valida que se lance una excepción cuando el array no contiene una clave
     * pública.
     */
    public function testCreateFromArrayThrowsExceptionForMissingPublicKey(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('La clave pública del certificado no fue encontrada.');
        $faker = new CertificateFaker();
        $certs = $faker->createAsArray();
        unset($certs['cert']); // Eliminar la clave pública para simular un array inválido.
        CertificateLoader::createFromArray($certs);
    }

    /**
     * Valida que se lance una excepción cuando el array no contiene una clave
     * privada.
     */
    public function testCreateFromArrayThrowsExceptionForMissingPrivateKey(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('La clave privada del certificado no fue encontrada.');
        $faker = new CertificateFaker();
        $certs = $faker->createAsArray();
        unset($certs['pkey']); // Eliminar la clave privada para simular un array inválido.
        CertificateLoader::createFromArray($certs);
    }
}
