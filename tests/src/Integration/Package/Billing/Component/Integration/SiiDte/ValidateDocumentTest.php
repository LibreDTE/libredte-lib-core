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

namespace libredte\lib\Tests\Integration\Package\Billing\Component\Integration\SiiDte;

use Derafu\Certificate\Exception\CertificateException;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiWsdlResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDteWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\IntegrationComponent;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\ValidateDocumentJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDteWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazyWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractSiiWsdlResponse::class)]
#[CoversClass(IntegrationComponent::class)]
#[CoversClass(SiiRequest::class)]
#[CoversClass(SiiDteWorker::class)]
#[CoversClass(SiiLazyWorker::class)]
#[CoversClass(AuthenticateJob::class)]
#[CoversClass(ConsumeWebserviceJob::class)]
#[CoversClass(SiiEnvironment::class)]
#[CoversClass(ValidateDocumentResponse::class)]
#[CoversClass(ValidateDocumentJob::class)]
class ValidateDocumentTest extends TestCase
{
    private SiiDteWorkerInterface $siiDteWorker;

    private SiiRequestInterface $siiRequest;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $certificateLoader = new CertificateLoader();

        $this->siiDteWorker = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getIntegrationComponent()
            ->getSiiDteWorker()
        ;

        // Load digital certificate.
        try {
            $certificate = $certificateLoader->loadFromFile(
                getenv('LIBREDTE_CERTIFICATE_FILE'),
                getenv('LIBREDTE_CERTIFICATE_PASS'),
            );
        } catch (CertificateException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        // Create the request that will be used as a base.
        $this->siiRequest = new SiiRequest(
            certificate: $certificate,
            options: [
                'environment' => SiiEnvironment::STAGING,
            ],
        );
    }

    public function testStatusDok(): void
    {
        $documentStatus = $this->siiDteWorker->validateDocument(
            $this->siiRequest,
            '76192083-9',
            33,
            747,
            '2020-01-14',
            5699863,
            '76192083-9'
        );

        $this->assertSame('DOK', $documentStatus->getStatus());
    }

    public function testStatusAnc(): void
    {
        $documentStatus = $this->siiDteWorker->validateDocument(
            $this->siiRequest,
            '76192083-9',
            33,
            746,
            '2020-01-14',
            2605918,
            '76192083-9',
        );

        $this->assertSame('ANC', $documentStatus->getStatus());
    }

    public function testStatusMmc(): void
    {
        $documentStatus = $this->siiDteWorker->validateDocument(
            $this->siiRequest,
            '76192083-9',
            33,
            745,
            '2020-01-14',
            13298612,
            '76192083-9',
        );

        $this->assertSame('MMC', $documentStatus->getStatus());
    }

    public function testStatusTmc(): void
    {
        $documentStatus = $this->siiDteWorker->validateDocument(
            $this->siiRequest,
            '76192083-9',
            33,
            744,
            '2020-01-14',
            1783121,
            '76192083-9',
        );

        $this->assertSame('TMC', $documentStatus->getStatus());
    }
}
