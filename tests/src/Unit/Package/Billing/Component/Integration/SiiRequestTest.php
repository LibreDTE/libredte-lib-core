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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Integration;

use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(SiiRequest::class)]
class SiiRequestTest extends TestCase
{
    public function testOpcionesPredeterminadasDeConexionYToken(): void
    {
        $request = new SiiRequest();

        $this->assertSame(SiiEnvironment::PRODUCTION, $request->getEnvironment());
        $this->assertSame(10, $request->getRetries());
        $this->assertTrue($request->getVerifySsl());
        $this->assertSame('memory', $request->getTokenDefaultCache());
        $this->assertSame(60, $request->getTokenTtl());
        $this->assertNull($request->getCertificate());
    }

    public function testOpcionesPersonalizadasYClaveDelCertificado(): void
    {
        $certificate = (new CertificateFaker(new CertificateLoader()))->createFake();
        $request = new SiiRequest($certificate, [
            'environment' => SiiEnvironment::STAGING,
            'retries' => 3,
            'verify_ssl' => false,
            'token' => [
                'cache' => 'filesystem',
                'key' => 'empresa_%s_token',
                'ttl' => 120,
            ],
        ]);

        $this->assertSame(SiiEnvironment::STAGING, $request->getEnvironment());
        $this->assertSame(3, $request->getRetries());
        $this->assertFalse($request->getVerifySsl());
        $this->assertSame('filesystem', $request->getTokenDefaultCache());
        $this->assertSame('empresa_' . $certificate->getId() . '_token', $request->getTokenKey());
        $this->assertSame(120, $request->getTokenTtl());
        $this->assertSame($certificate, $request->getCertificate());
    }

    public function testEntornoNumericoYLimitesDeReintentos(): void
    {
        $request = new SiiRequest(options: [
            'environment' => (string) SiiEnvironment::STAGING->value,
            'retries' => 99,
        ]);

        $this->assertSame(SiiEnvironment::STAGING, $request->getEnvironment());
        $this->assertSame(10, $request->getRetries());
        $this->assertSame(0, $request->getRetries(-1));
        $this->assertSame(10, $request->getRetries(99));
        $this->assertSame(2, $request->getRetries(2));
        $this->assertSame(0, (new SiiRequest(options: ['retries' => -1]))->getRetries());
    }

    public function testClaveDeTokenRequierePlaceholderDelCertificado(): void
    {
        $request = new SiiRequest(options: ['token' => ['key' => 'sin_placeholder']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La clave del token debe permitir asignar el ID asociado al token.');

        $request->getTokenKey();
    }

    public function testClaveDeTokenRequiereCertificado(): void
    {
        $request = new SiiRequest();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No hay certificado digital asociado a la solicitud.');

        $request->getTokenKey();
    }
}
