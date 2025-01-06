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

use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Exception\CertificateException;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiWsdlResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDeliveryCheckerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Entity\Ambiente;
use libredte\lib\Core\Package\Billing\Component\Integration\IntegrationComponent;
use libredte\lib\Core\Package\Billing\Component\Integration\Response\SiiDocumentSentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiConnectionOptions;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDeliveryCheckerWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazyWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiTokenManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiWsdlConsumerWorker;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractSiiWsdlResponse::class)]
#[CoversClass(IntegrationComponent::class)]
#[CoversClass(SiiDocumentSentResponse::class)]
#[CoversClass(SiiConnectionOptions::class)]
#[CoversClass(SiiDeliveryCheckerWorker::class)]
#[CoversClass(SiiLazyWorker::class)]
#[CoversClass(SiiTokenManagerWorker::class)]
#[CoversClass(SiiWsdlConsumerWorker::class)]
class SiiDeliveryCheckSentStatusTest extends TestCase
{
    private CertificateInterface $certificate;

    private SiiDeliveryCheckerWorkerInterface $deliveryChecker;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $app
            ->getBillingPackage()
            ->getIntegrationComponent()
            ->getSiiLazyWorker()
            ->setOptions([
                'connection' => [
                    'ambiente' => Ambiente::CERTIFICACION,
                ],
            ])
        ;

        $certificateLoader = $app
            ->getPrimePackage()
            ->getCertificateComponent()
            ->getLoaderWorker()
        ;

        $this->deliveryChecker = $app
            ->getBillingPackage()
            ->getIntegrationComponent()
            ->getSiiDeliveryCheckerWorker()
        ;

        // Cargar certificado digital.
        try {
            $this->certificate = $certificateLoader->createFromFile(
                getenv('LIBREDTE_CERTIFICATE_FILE'),
                getenv('LIBREDTE_CERTIFICATE_PASS'),
            );
        } catch (CertificateException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    public function testUploadStatusRfr(): void
    {
        $trackId = 230939274; // Error de firma.
        $company = '76192083-9';

        $sentResponse = $this->deliveryChecker->checkSentStatus(
            $this->certificate,
            $trackId,
            $company
        );

        $this->assertSame('RFR', $sentResponse->getStatus());
    }

    public function testUploadStatusRfr2(): void
    {
        $trackId = 231039586; // Error de firma.
        $company = '76192083-9';

        $sentResponse = $this->deliveryChecker->checkSentStatus(
            $this->certificate,
            $trackId,
            $company
        );

        $this->assertSame('RFR - Rechazado por Error en Firma', $sentResponse->getReviewStatus());
    }

    public function testUploadStatusWrongEnvironment(): void
    {
        $trackId = 9934380406; // Es de Producción.
        $company = '76192083-9';

        $sentResponse = $this->deliveryChecker->checkSentStatus(
            $this->certificate,
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

        $sentResponse = $this->deliveryChecker->checkSentStatus(
            $this->certificate,
            $trackId,
            $company
        );

        $this->assertSame('EPR - Envio Procesado', $sentResponse->getReviewStatus());
    }
}
