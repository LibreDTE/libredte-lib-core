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

namespace libredte\lib\Tests\Functional\Signature;

use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\CertificateException;
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Signature\CertificateValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CertificateValidator::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateException::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
class CertificateValidatorTest extends TestCase
{
    public function testValidCertificate(): void
    {
        $faker = new CertificateFaker();
        $certificate = $faker->create();
        CertificateValidator::validate($certificate);
        $this->assertTrue(true);
    }

    public function testInvalidCertificate(): void
    {
        $this->expectException(CertificateException::class);
        $faker = new CertificateFaker();
        $faker->setSubject(serialNumber: '123');
        $certificate = $faker->create();
        CertificateValidator::validate($certificate);
    }
}
