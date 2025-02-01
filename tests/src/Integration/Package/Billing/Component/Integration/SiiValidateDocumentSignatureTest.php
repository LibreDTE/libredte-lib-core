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
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiValidateDocumentSignatureResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ValidateDocumentSignatureJob;
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
#[CoversClass(SiiValidateDocumentSignatureResponse::class)]
#[CoversClass(ValidateDocumentSignatureJob::class)]
class SiiValidateDocumentSignatureTest extends TestCase
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

    public function testStatusDok(): void
    {
        $signature = 'pw4Cr2nD7kFpaWjgrw543UfGfg2VnE5bezV9qQn7AZlhgW78w94ldrauKSFjr1BC4CYXBvtttHQpPN4r2aCX4+j/D+4w32INaO61DzvtVu6Qt3SpJZhCdCYc02Z7yFGZHPFQQi4Drjcqlt0WpoigUARWFp7DuFoQOBxEKYP1l41S3jSt1/5QwtA9HQvOqpU8hK4ub29HSFVETQFCEhBRRnBPvQeXflQOarjFb/xqFs0/I3YQNwsXLwfEJ68YKY8bAOlRwTuWayA8Om+xwkCnOibspGIMLhXLZBM4SqSDJPdiCyFGDs7irqsnIRZSe386Us+WFYFCs58nWmB+vFwTrA==';

        $documentStatus = $this->siiLazyWorker->validateDocumentSignature(
            $this->siiRequest,
            '76192083-9',
            33,
            747,
            '2020-01-14',
            5699863,
            '76192083-9',
            $signature,
        );

        $this->assertSame('DOK', $documentStatus->getStatus());
    }

    public function testStatusDnk(): void
    {
        $signature = 'pw4Cr2nD7kFpaWjgrw543UfGfg2VnE5bezV9qQn7AZlhgW78w94ldrauKSFjr1BC4CYXBvtttHQpPN4r2aCX4+j/D+4w32INaO61DzvtVu6Qt3SpJZhCdCYc02Z7yFGZHPFQQi4Drjcqlt0WpoigUARWFp7DuFoQOBxEKYP1l41S3jSt1/5QwtA9HQvOqpU8hK4ub29HSFVETQFCEhBRRnBPvQeXflQOarjFb/xqFs0/I3YQNwsXLwfEJ68YKY8bAOlRwTuWayA8Om+xwkCnOibspGIMLhXLZBM4SqSDJPdiCyFGDs7irqsnIRZSe386Us+WFYFCs58nWmB+vFwTrA==XXX';

        $documentStatus = $this->siiLazyWorker->validateDocumentSignature(
            $this->siiRequest,
            '76192083-9',
            33,
            747,
            '2020-01-14',
            5699863,
            '76192083-9',
            $signature,
        );

        $this->assertSame('DNK', $documentStatus->getStatus());
    }
}
