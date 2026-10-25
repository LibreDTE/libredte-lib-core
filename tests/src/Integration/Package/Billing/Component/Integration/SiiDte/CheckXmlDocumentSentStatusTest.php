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
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\CheckXmlDocumentSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\CheckXmlDocumentSentStatusJob;
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
#[CoversClass(CheckXmlDocumentSentStatusResponse::class)]
#[CoversClass(CheckXmlDocumentSentStatusJob::class)]
class CheckXmlDocumentSentStatusTest extends TestCase
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

        // Cargar certificado digital.
        try {
            $certificate = $certificateLoader->loadFromFile(
                getenv('LIBREDTE_CERTIFICATE_FILE'),
                getenv('LIBREDTE_CERTIFICATE_PASS'),
            );
        } catch (CertificateException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        // Crear solicitud que se usará de base.
        $this->siiRequest = new SiiRequest(
            certificate: $certificate,
            options: [
                'environment' => SiiEnvironment::STAGING,
            ],
        );
    }

    public function testUploadStatusRfr(): void
    {
        $trackId = 230939274; // Error de firma.
        $company = '76192083-9';

        $sentResponse = $this->siiDteWorker->checkXmlDocumentSentStatus(
            $this->siiRequest,
            $trackId,
            $company
        );

        $this->assertSame('RFR', $sentResponse->getStatus());
    }

    public function testUploadStatusRfr2(): void
    {
        $trackId = 231039586; // Error de firma.
        $company = '76192083-9';

        $sentResponse = $this->siiDteWorker->checkXmlDocumentSentStatus(
            $this->siiRequest,
            $trackId,
            $company
        );

        $this->assertSame('RFR - Rechazado por Error en Firma', $sentResponse->getReviewStatus());
    }

    public function testUploadStatusWrongEnvironment(): void
    {
        $trackId = 9934380406; // Es de Producción.
        $company = '76192083-9';

        $sentResponse = $this->siiDteWorker->checkXmlDocumentSentStatus(
            $this->siiRequest,
            $trackId,
            $company
        );

        $this->assertSame('-11', $sentResponse->getStatus());
        $this->assertSame('Error de Proceso', $sentResponse->getDescription());
    }

    public function testUploadStatusEpr(): void
    {
        $trackId = 76941002; // Set de pruebas con 8 documentos.
        $company = '76192083-9';

        $sentResponse = $this->siiDteWorker->checkXmlDocumentSentStatus(
            $this->siiRequest,
            $trackId,
            $company
        );

        $this->assertSame('EPR - Envio Procesado', $sentResponse->getReviewStatus());
    }
}
