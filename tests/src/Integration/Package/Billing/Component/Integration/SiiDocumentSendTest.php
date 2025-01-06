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
use Derafu\Lib\Core\Package\Prime\Component\Xml\Entity\Xml as XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDocumentSenderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Entity\Ambiente;
use libredte\lib\Core\Package\Billing\Component\Integration\IntegrationComponent;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiConnectionOptions;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDocumentSenderWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazyWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiTokenManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiWsdlConsumerWorker;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(IntegrationComponent::class)]
#[CoversClass(SiiConnectionOptions::class)]
#[CoversClass(SiiDocumentSenderWorker::class)]
#[CoversClass(SiiLazyWorker::class)]
#[CoversClass(SiiTokenManagerWorker::class)]
#[CoversClass(SiiWsdlConsumerWorker::class)]
class SiiDocumentSendTest extends TestCase
{
    private string $xmlDir;

    private CertificateInterface $certificate;

    private SiiDocumentSenderWorkerInterface $documentSender;

    protected function setUp(): void
    {
        $app = libredte_lib();

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

        $this->documentSender = $app
            ->getBillingPackage()
            ->getIntegrationComponent()
            ->getSiiDocumentSenderWorker()
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

        $trackId = $this->documentSender->sendXml(
            $this->certificate,
            $xmlDocument,
            '76192083-9'
        );

        $this->assertGreaterThan(0, $trackId);
    }
}
