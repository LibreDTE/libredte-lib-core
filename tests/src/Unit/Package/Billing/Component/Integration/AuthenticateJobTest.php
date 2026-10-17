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

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\AuthenticateException;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Pruebas de la autenticación en el SII (semilla y token) con las respuestas
 * que el SII puede entregar, incluyendo valores vacíos.
 */
#[CoversClass(AuthenticateJob::class)]
#[UsesClass(SiiException::class)]
class AuthenticateJobTest extends TestCase
{
    private function document(string $estado, string $body): XmlDocument
    {
        $document = new XmlDocument();
        $document->loadXml(
            '<SII:RESPUESTA xmlns:SII="http://www.sii.cl/XMLSchema">'
            . '<SII:RESP_HDR><ESTADO>' . $estado . '</ESTADO></SII:RESP_HDR>'
            . '<SII:RESP_BODY>' . $body . '</SII:RESP_BODY>'
            . '</SII:RESPUESTA>'
        );

        return $document;
    }

    private function authenticate(XmlDocument $seed, XmlDocument $token): string
    {
        $consume = $this->createStub(ConsumeWebserviceJob::class);
        $consume->method('sendRequest')->willReturnOnConsecutiveCalls($seed, $token);

        $signature = $this->createStub(SignatureServiceInterface::class);
        $signature->method('signXml')->willReturn('<getToken/>');

        $certificate = $this->createStub(CertificateInterface::class);
        $certificate->method('getId')->willReturn('11222333-9');

        $request = $this->createStub(SiiRequestInterface::class);
        $request->method('getCertificate')->willReturn($certificate);
        $request->method('getTokenKey')->willReturn('token_key');
        $request->method('getTokenTtl')->willReturn(60);
        $request->method('getTokenDefaultCache')->willReturn('array');

        $job = new AuthenticateJob(
            $signature,
            new XmlService(new XmlEncoder(), new XmlDecoder(), new XmlValidator()),
            $consume,
            new ArrayAdapter()
        );

        return $job->authenticate($request);
    }

    public function testItGetsTheToken(): void
    {
        $token = $this->authenticate(
            $this->document('00', '<SEMILLA>123</SEMILLA>'),
            $this->document('00', '<TOKEN>abc123</TOKEN>')
        );

        $this->assertSame('abc123', $token);
    }

    public function testAnEmptySeedIsAnError(): void
    {
        $this->expectException(AuthenticateException::class);
        $this->expectExceptionMessage('No fue posible obtener la semilla.');

        $this->authenticate(
            $this->document('00', '<SEMILLA/>'),
            $this->document('00', '<TOKEN>abc123</TOKEN>')
        );
    }

    public function testAnEmptyTokenIsAnError(): void
    {
        $this->expectException(AuthenticateException::class);
        $this->expectExceptionMessage('No fue posible obtener el token');

        $this->authenticate(
            $this->document('00', '<SEMILLA>123</SEMILLA>'),
            $this->document('00', '<TOKEN/>')
        );
    }
}
