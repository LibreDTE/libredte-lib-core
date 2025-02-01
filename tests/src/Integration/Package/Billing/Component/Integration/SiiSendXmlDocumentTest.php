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
use Derafu\Lib\Core\Package\Prime\Component\Xml\Entity\Xml as XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\IntegrationComponent;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\SendXmlDocumentJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazyWorker;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(IntegrationComponent::class)]
#[CoversClass(SiiRequest::class)]
#[CoversClass(SiiLazyWorker::class)]
#[CoversClass(AuthenticateJob::class)]
#[CoversClass(ConsumeWebserviceJob::class)]
#[CoversClass(SiiAmbiente::class)]
#[CoversClass(SendXmlDocumentJob::class)]
class SiiSendXmlDocumentTest extends TestCase
{
    private string $xmlDir;

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

        $this->xmlDir = self::getFixturesPath('xml');
    }

    public function testSendXmlDteForUploadOkTrackId(): void
    {
        // Corrorborar que el archivo XML del DTE exista.
        $file = $this->xmlDir . '/upload/dte_for_upload_1.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped(sprintf('Archivo %s no existe.', $file));
        }

        // Cargar XML del DTE en un documento XML.
        $xml = file_get_contents($file);
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        $trackId = $this->siiLazyWorker->sendXmlDocument(
            $this->siiRequest,
            $xmlDocument,
            '76192083-9'
        );

        $this->assertGreaterThan(0, $trackId);
    }
}
