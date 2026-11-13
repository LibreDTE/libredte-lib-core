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
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(SiiRequest::class)]
#[CoversClass(SiiEnvironment::class)]
class SiiRequestTest extends TestCase
{
    public function testDefaultConnectionAndTokenOptions(): void
    {
        $request = new SiiRequest();

        $this->assertSame(SiiEnvironment::PRODUCTION, $request->getEnvironment());
        $this->assertSame(3, $request->getRetries());
        $this->assertSame(20, $request->getTimeout());
        $this->assertSame(60, $request->getMaxDuration());
        $this->assertTrue($request->getVerifySsl());
        $this->assertSame('memory', $request->getTokenDefaultCache());
        $this->assertSame(2700, $request->getTokenTtl());
        $this->assertNull($request->getCertificate());
    }

    public function testCustomOptionsAndCertificateBasedTokenKey(): void
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
        $this->assertSame('empresa_' . $certificate->getId() . '_cert_token', $request->getTokenKey());
        $this->assertSame(120, $request->getTokenTtl());
        $this->assertSame($certificate, $request->getCertificate());
    }

    public function testNumericEnvironmentAndRetryLimits(): void
    {
        $request = new SiiRequest(options: [
            'environment' => (string) SiiEnvironment::STAGING->value,
            'retries' => 99,
        ]);

        $this->assertSame(SiiEnvironment::STAGING, $request->getEnvironment());
        $this->assertSame(10, $request->getRetries());
        $this->assertSame(1, $request->getRetries(-1));
        $this->assertSame(10, $request->getRetries(99));
        $this->assertSame(2, $request->getRetries(2));
        $this->assertSame(1, (new SiiRequest(options: ['retries' => -1]))->getRetries());
    }

    public function testTimeoutAndMaxDurationOptionsAndLimits(): void
    {
        $custom = new SiiRequest(options: ['timeout' => 30, 'max_duration' => 120]);

        $this->assertSame(30, $custom->getTimeout());
        $this->assertSame(120, $custom->getMaxDuration());

        $below = new SiiRequest(options: ['timeout' => 1, 'max_duration' => 0]);

        $this->assertSame(10, $below->getTimeout());
        $this->assertSame(10, $below->getMaxDuration());

        $above = new SiiRequest(options: ['timeout' => 999, 'max_duration' => 999]);

        $this->assertSame(60, $above->getTimeout());
        $this->assertSame(300, $above->getMaxDuration());
    }

    public function testTokenKeyRequiresCertificatePlaceholder(): void
    {
        $request = new SiiRequest(options: ['token' => ['key' => 'sin_placeholder']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The token key must allow assigning the ID associated with the token.');

        $request->getTokenKey();
    }

    public function testTokenKeyRequiresCertificate(): void
    {
        $request = new SiiRequest();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no digital certificate associated with the request.');

        $request->getTokenKey();
    }
}
