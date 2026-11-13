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

namespace libredte\lib\Tests\Integration\Package\Billing\Component\Integration\SiiRtc;

use Derafu\Certificate\Exception\CertificateException;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiRtcQueryResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRtcWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\EstadoEnvioAec;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\IntegrationComponent;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\CheckAecSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\GetAssignmentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiAttempts;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazyWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\CheckAecSentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\GetAssignmentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\GetRelatedAssignmentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtcWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(IntegrationComponent::class)]
#[CoversClass(SiiRequest::class)]
#[UsesClass(SiiAttempts::class)]
#[CoversClass(SiiLazyWorker::class)]
#[CoversClass(AuthenticateJob::class)]
#[CoversClass(ConsumeWebserviceJob::class)]
#[CoversClass(SiiEnvironment::class)]
#[CoversClass(SiiRtcWorker::class)]
#[CoversClass(CheckAecSentStatusJob::class)]
#[CoversClass(GetAssignmentStatusJob::class)]
#[CoversClass(GetRelatedAssignmentStatusJob::class)]
#[CoversClass(AbstractSiiRtcQueryResponse::class)]
#[CoversClass(CheckAecSentStatusResponse::class)]
#[CoversClass(GetAssignmentStatusResponse::class)]
#[CoversClass(EstadoEnvioAec::class)]
class SiiRtcQueriesTest extends TestCase
{
    private SiiRtcWorkerInterface $siiRtcWorker;

    private SiiRequestInterface $siiRequest;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $certificateLoader = new CertificateLoader();

        $this->siiRtcWorker = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getIntegrationComponent()
            ->getSiiRtcWorker()
        ;

        // Load digital certificate.
        try {
            $certificate = $certificateLoader->loadFromFile(
                ValueCaster::requireString(getenv('LIBREDTE_CERTIFICATE_FILE')),
                ValueCaster::requireString(getenv('LIBREDTE_CERTIFICATE_PASS')),
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

    public function testCheckAecSentStatus(): void
    {
        // Track ID of an AEC with fake data, sent to the certification
        // environment: the SII rejects it because of the signature of the
        // envelope.
        $trackId = 263816426;

        $response = $this->siiRtcWorker->checkAecSentStatus($this->siiRequest, $trackId);

        $this->assertInstanceOf(CheckAecSentStatusResponse::class, $response);
        $this->assertSame($trackId, $response->getTrackId());
        $this->assertTrue($response->isRequestOk());
        $this->assertSame(EstadoEnvioAec::RFS, $response->getEstadoEnvioAec());
        $this->assertTrue($response->isFinal());
    }

    public function testGetAssignmentStatusOfADocumentThatIsNotAssigned(): void
    {
        $response = $this->siiRtcWorker->getAssignmentStatus(
            $this->siiRequest,
            '76192083-9',
            33,
            1000
        );

        $this->assertInstanceOf(GetAssignmentStatusResponse::class, $response);
        $this->assertFalse($response->isAssigned());
    }
}
