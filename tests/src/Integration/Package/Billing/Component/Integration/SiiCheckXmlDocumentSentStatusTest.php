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

namespace libredte\lib\Tests\Integration\Package\Billing\Component\Integration;

use Derafu\Lib\Core\Package\Prime\Component\Certificate\Exception\CertificateException;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiWsdlResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\IntegrationComponent;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiCheckXmlDocumentSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\CheckXmlDocumentSentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazyWorker;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractSiiWsdlResponse::class)]
#[CoversClass(IntegrationComponent::class)]
#[CoversClass(SiiRequest::class)]
#[CoversClass(SiiLazyWorker::class)]
#[CoversClass(AuthenticateJob::class)]
#[CoversClass(ConsumeWebserviceJob::class)]
#[CoversClass(SiiAmbiente::class)]
#[CoversClass(SiiCheckXmlDocumentSentStatusResponse::class)]
#[CoversClass(CheckXmlDocumentSentStatusJob::class)]
class SiiCheckXmlDocumentSentStatusTest extends TestCase
{
    private SiiLazyWorkerInterface $siiLazyWorker;

    private SiiRequestInterface $siiRequest;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $certificateLoader = $app
            ->getPrimePackage()
            ->getCertificateComponent()
            ->getLoaderWorker()
        ;

        $this->siiLazyWorker = $app
            ->getBillingPackage()
            ->getIntegrationComponent()
            ->getSiiLazyWorker()
        ;

        // Cargar certificado digital.
        try {
            $certificate = $certificateLoader->createFromFile(
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
                'ambiente' => SiiAmbiente::CERTIFICACION,
            ],
        );
    }

    public function testUploadStatusRfr(): void
    {
        $trackId = 230939274; // Error de firma.
        $company = '76192083-9';

        $sentResponse = $this->siiLazyWorker->checkXmlDocumentSentStatus(
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

        $sentResponse = $this->siiLazyWorker->checkXmlDocumentSentStatus(
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

        $sentResponse = $this->siiLazyWorker->checkXmlDocumentSentStatus(
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

        $sentResponse = $this->siiLazyWorker->checkXmlDocumentSentStatus(
            $this->siiRequest,
            $trackId,
            $company
        );

        $this->assertSame('EPR - Envio Procesado', $sentResponse->getReviewStatus());
    }
}
