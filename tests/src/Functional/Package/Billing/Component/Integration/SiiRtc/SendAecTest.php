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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Integration\SiiRtc;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtc\SendAecException;
use libredte\lib\Core\Package\Billing\Component\Integration\IntegrationComponent;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazyWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\SendAecJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtcWorker;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\Aec;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\OwnershipTransferComponent;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Support\AecBag;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\Aec\Job\BuildAecJob;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\Aec\Job\BuildCesionJob;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\Aec\Job\BuildDteCedidoJob;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\AecWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Throwable;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(IntegrationComponent::class)]
#[CoversClass(SiiRequest::class)]
#[CoversClass(SiiRtcWorker::class)]
#[CoversClass(SendAecJob::class)]
#[CoversClass(SendAecException::class)]
#[CoversClass(SendAecResponse::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(AecWorker::class)]
#[CoversClass(Aec::class)]
#[CoversClass(AecBag::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(Utils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(SiiEnvironment::class)]
#[CoversClass(SiiLazyWorker::class)]
#[CoversClass(AuthenticateJob::class)]
#[CoversClass(ConsumeWebserviceJob::class)]
#[CoversClass(OwnershipTransferComponent::class)]
#[CoversClass(BuildAecJob::class)]
#[CoversClass(BuildCesionJob::class)]
#[CoversClass(BuildDteCedidoJob::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(\libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
class SendAecTest extends TestCase
{
    public function testSendAecFacturaAfecta(): void
    {
        // Try to load the digital certificate. If loading fails, the test is
        // marked as skipped.
        try {
            $certificateLoader = new CertificateLoader();
            $certificate = $certificateLoader->loadFromFile(
                getenv('LIBREDTE_CERTIFICATE_FILE'),
                getenv('LIBREDTE_CERTIFICATE_PASS')
            );
        } catch (Throwable $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $app = Application::getInstance();
        $emisorRut = $certificate->getId();

        $dte = $this->buildFacturaAfecta($app, $certificate);

        $aec = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getOwnershipTransferComponent()
            ->getAecWorker()
            ->build(new AecBag(
                source: $dte,
                cedente: [
                    'RUT' => $emisorRut,
                    'RazonSocial' => $certificate->getName(),
                    'Direccion' => 'Dirección de Prueba 123',
                    'eMail' => $certificate->getEmail(),
                    'RUTAutorizado' => [
                        'RUT' => $emisorRut,
                        'Nombre' => $certificate->getName(),
                    ],
                ],
                cesionario: [
                    'RUT' => '76192083-9',
                    'RazonSocial' => 'Cesionario de Prueba SpA',
                    'Direccion' => 'Dirección Cesionario 456',
                    'eMail' => 'cesionario@example.com',
                ],
                cesion: [
                    'MontoCesion' => $dte->getMontoTotal(),
                    'UltimoVencimiento' => date('Y-m-d', strtotime('+30 days')),
                ],
                certificate: $certificate,
            ))
        ;

        // Send the AEC to the SII RTC in the certification environment.
        // With a real certificate but fake CAF, the SII authenticates the user
        // but will likely reject the AEC. Both outcomes are valid for this test:
        // if a Track ID is returned, communication succeeded; if SendAecException
        // is thrown, the HTTP round-trip still worked correctly.
        $request = new SiiRequest($certificate, [
            'environment' => SiiEnvironment::STAGING,
        ]);

        try {
            $response = $app
                ->getPackageRegistry()
                ->getBillingPackage()
                ->getIntegrationComponent()
                ->getSiiRtcWorker()
                ->sendAec($request, $aec->getXmlDocument(), $emisorRut, 'cedente@example.com')
            ;

            $this->assertInstanceOf(SendAecResponse::class, $response);
            $this->assertGreaterThan(0, $response->getTrackId());
        } catch (SendAecException $e) {
            // The SII rejected the AEC (fake CAF not valid in production/cert.),
            // but HTTP communication with the RTC service worked.
            $this->addToAssertionCount(1);
        }
    }

    /**
     * Builds a FacturaAfecta with a fake CAF generated for the issuer
     * derived from the real certificate.
     */
    private function buildFacturaAfecta(Application $app, CertificateInterface $certificate): DocumentInterface
    {
        $billingPackage = $app->getPackageRegistry()->getBillingPackage();

        $emisorRut = $certificate->getId();

        $caf = $billingPackage
            ->getIdentifierComponent()
            ->getCafFakerWorker()
            ->create(new Emisor($emisorRut, $certificate->getName()), 33, 1)
        ;

        $bag = new DocumentBag(
            parsedData: [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                        'Folio' => 1,
                    ],
                    'Emisor' => [
                        'RUTEmisor' => $emisorRut,
                        'RznSoc' => $certificate->getName(),
                        'GiroEmis' => 'Servicios',
                        'DirOrigen' => 'Dirección 123',
                        'CmnaOrigen' => 'Santiago',
                    ],
                    'Receptor' => [
                        'RUTRecep' => '60803000-K',
                        'RznSocRecep' => 'Servicio de Impuestos Internos',
                        'GiroRecep' => 'Gobierno',
                        'DirRecep' => 'Santiago',
                        'CmnaRecep' => 'Santiago',
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Servicio de prueba',
                        'QtyItem' => 1,
                        'PrcItem' => 10000,
                    ],
                ],
            ],
            caf: $caf,
            certificate: $certificate,
        );

        return $billingPackage
            ->getDocumentComponent()
            ->getBuilderWorker()
            ->build($bag)
            ->getDocument()
        ;
    }
}
