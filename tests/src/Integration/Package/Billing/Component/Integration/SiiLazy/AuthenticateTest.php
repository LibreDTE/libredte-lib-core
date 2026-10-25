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

namespace libredte\lib\Tests\Integration\Package\Billing\Component\Integration\SiiLazy;

use Derafu\Certificate\Exception\CertificateException;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\IntegrationComponent;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazyWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(IntegrationComponent::class)]
#[CoversClass(SiiRequest::class)]
#[CoversClass(SiiLazyWorker::class)]
#[CoversClass(AuthenticateJob::class)]
#[CoversClass(ConsumeWebserviceJob::class)]
#[CoversClass(SiiEnvironment::class)]
class AuthenticateTest extends TestCase
{
    public function testGetTokenOk(): void
    {
        $app = Application::getInstance();

        $certificateLoader = new CertificateLoader();

        $siiLazyWorker = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getIntegrationComponent()
            ->getSiiLazyWorker()
        ;

        // Cargar certificado digital.
        try {
            $certificate = $certificateLoader->loadFromFile(
                getenv('LIBREDTE_CERTIFICATE_FILE'),
                getenv('LIBREDTE_CERTIFICATE_PASS'),
            );
        } catch (CertificateException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        // Solicitar token ambiente producción.
        $token = $siiLazyWorker->authenticate(new SiiRequest($certificate));
        $this->assertNotEmpty($token);
    }
}
