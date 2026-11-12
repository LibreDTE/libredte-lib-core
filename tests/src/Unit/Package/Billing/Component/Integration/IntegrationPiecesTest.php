<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Integration;

use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiResponseInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\RcvAccion;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\IntegrationException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiException;
use libredte\lib\Core\Package\Billing\Component\Integration\Helper\SiiEnvioAyudas;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RcvAccion::class)]
#[CoversClass(SiiEnvironment::class)]
#[CoversClass(SiiEnvioAyudas::class)]
#[CoversClass(SiiException::class)]
#[CoversClass(IntegrationException::class)]
#[CoversClass(SiiRequest::class)]
class IntegrationPiecesTest extends TestCase
{
    public function testRcvActionsDescribeThemselves(): void
    {
        $expected = [
            'ERM' => ['Entrega Real de Mercaderías o Servicios (Ley 19.983)', true, false],
            'ACD' => ['Acepta Contenido del Documento', true, false],
            'RCD' => ['Reclamo al Contenido del Documento', false, true],
            'RFP' => ['Reclamo por Falta Parcial de Mercaderías', false, true],
            'RFT' => ['Reclamo por Falta Total de Mercaderías', false, true],
        ];

        $this->assertCount(count($expected), RcvAccion::cases());
        foreach (RcvAccion::cases() as $accion) {
            [$label, $favorable, $reclamo] = $expected[$accion->value];
            $this->assertSame($label, $accion->label());
            $this->assertSame($favorable, $accion->isFavorableParaEmisor());
            $this->assertSame($reclamo, $accion->isReclamo());
        }
    }

    public function testHelpForARejectedSendDependsOnTheDetailOfTheSii(): void
    {
        $this->assertStringContainsString(
            'T33F12',
            (string) SiiEnvioAyudas::get('RCH - DTE Rechazado', '(REF-3-750) Referencia no existe', 33, 12)
        );
        $this->assertStringContainsString(
            'folio 12',
            (string) SiiEnvioAyudas::get('RCH - DTE Rechazado', '(DTE-3-101) El folio ya fue usado', 33, 12)
        );
        $this->assertSame(
            'La fecha de emisión del DTE es previa a la fecha de autorización del documento.',
            SiiEnvioAyudas::get('RCH - DTE Rechazado', '(HED-3-305) Fecha', 33, 12)
        );
        $this->assertNull(SiiEnvioAyudas::get('RCH - DTE Rechazado', '(XXX-0-0) Desconocido', 33, 12));
        $this->assertNull(SiiEnvioAyudas::get('RCH - DTE Rechazado', null, 33, 12));
    }

    public function testHelpForARejectedSignatureDoesNotNeedTheDetail(): void
    {
        $help = SiiEnvioAyudas::get('RFR - Rechazado por Error en Firma', null, 33, 12);

        $this->assertStringStartsWith('Problema con la firma', (string) $help);
        $this->assertNull(SiiEnvioAyudas::get('EPR - Envío Procesado', null, 33, 12));
    }

    public function testEnvironmentDescribesItself(): void
    {
        $this->assertSame('Producción', SiiEnvironment::PRODUCTION->getLabel());
        $this->assertSame('Certificación', SiiEnvironment::STAGING->getLabel());
        $this->assertTrue(SiiEnvironment::PRODUCTION->isProduction());
        $this->assertFalse(SiiEnvironment::STAGING->isProduction());
        $this->assertSame('prod', SiiEnvironment::PRODUCTION->getCode());
        $this->assertSame('cert', SiiEnvironment::STAGING->getCode());
    }

    public function testEnvironmentFromTheCodeAndFromTheIdkOfTheCaf(): void
    {
        $this->assertSame(SiiEnvironment::PRODUCTION, SiiEnvironment::tryFromCode('prod'));
        $this->assertSame(SiiEnvironment::STAGING, SiiEnvironment::tryFromCode('cert'));
        $this->assertNull(SiiEnvironment::tryFromCode('otro'));

        $this->assertSame(300, SiiEnvironment::PRODUCTION->getCafIdk());
        $this->assertSame(100, SiiEnvironment::STAGING->getCafIdk());
        $this->assertSame(SiiEnvironment::PRODUCTION, SiiEnvironment::tryFromCafIdk(300));
        $this->assertSame(SiiEnvironment::STAGING, SiiEnvironment::tryFromCafIdk(100));
        $this->assertNull(SiiEnvironment::tryFromCafIdk(999));
    }

    public function testEnvironmentUrlsUseTheServerOfTheEnvironment(): void
    {
        $this->assertSame('https://maullin.sii.cl/cgi_dte/UPL/DTEUpload', SiiEnvironment::STAGING->getUrl('/cgi_dte/UPL/DTEUpload'));
        $this->assertSame('https://palena.sii.cl/cgi_dte/UPL/DTEUpload', SiiEnvironment::PRODUCTION->getUrl('/cgi_dte/UPL/DTEUpload'));
        $this->assertSame('https://www4c.sii.cl/anulacionMsvDteInternet', SiiEnvironment::STAGING->getUrl('/anulacionMsvDteInternet'));
        $this->assertSame('https://www4.sii.cl/anulacionMsvDteInternet', SiiEnvironment::PRODUCTION->getUrl('/anulacionMsvDteInternet'));
    }

    public function testStagingUsesTheWsdlThatLibreDteProvidesForTheTestServer(): void
    {
        $path = SiiEnvironment::STAGING->getWsdlPath('CrSeed');

        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertStringEndsWith('/wsdl/maullin/CrSeed.wsdl', $path);
        $this->assertNull(SiiEnvironment::STAGING->getWsdlPath('ServicioInexistente'));
    }

    public function testSiiExceptionKeepsTheRequestAndTheResponse(): void
    {
        $request = new SiiRequest();
        $response = new class () implements SiiResponseInterface {
        };

        $exception = new SiiException('falló', 3, null, $request, $response);

        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($response, $exception->getResponse());
        $this->assertNull((new SiiException('sin datos'))->getRequest());
        $this->assertNull((new SiiException('sin datos'))->getResponse());
    }

    public function testRequestWithoutCertificateCannotProvideIt(): void
    {
        $this->assertNull((new SiiRequest())->getCertificate());

        $this->expectException(IntegrationException::class);
        (new SiiRequest())->requireCertificate();
    }

    public function testRequestWithCertificateProvidesIt(): void
    {
        $certificate = (new CertificateFaker(new CertificateLoader()))->createFake();

        $this->assertSame($certificate, (new SiiRequest($certificate))->requireCertificate());
    }
}
