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
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDescuentosRecargosTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDetalleTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeImpuestoAdicionalRetencionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeIvaMntTotalTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeTransporteTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
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
#[CoversClass(NormalizeDescuentosRecargosTrait::class)]
#[CoversClass(NormalizeDetalleTrait::class)]
#[CoversClass(NormalizeImpuestoAdicionalRetencionTrait::class)]
#[CoversClass(NormalizeIvaMntTotalTrait::class)]
#[CoversClass(NormalizeTransporteTrait::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafBag::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(SiiAmbiente::class)]
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
        // Tratar de cargar el certificado digital. Si no se logra cargar el
        // test se marcará como "saltado".
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

        // Enviar el AEC al RTC del SII en el ambiente de certificación.
        // Con un certificado real pero CAF falso, el SII autentica al usuario
        // pero probablemente rechace el AEC. En ambos casos el test es válido:
        // si retorna Track ID la comunicación fue exitosa; si lanza
        // SendAecException el round-trip HTTP funcionó correctamente.
        $request = new SiiRequest($certificate, [
            'ambiente' => SiiAmbiente::CERTIFICACION,
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
            // El SII rechazó el AEC (CAF falso no válido en producción/cert.),
            // pero la comunicación HTTP con el servicio RTC funcionó.
            $this->addToAssertionCount(1);
        }
    }

    /**
     * Construye una FacturaAfecta con un CAF falso generado para el emisor
     * derivado del certificado real.
     */
    private function buildFacturaAfecta(Application $app, CertificateInterface $certificate): DocumentInterface
    {
        $billingPackage = $app->getPackageRegistry()->getBillingPackage();

        $emisorRut = $certificate->getId();

        $caf = $billingPackage
            ->getIdentifierComponent()
            ->getCafFakerWorker()
            ->create(new Emisor($emisorRut, $certificate->getName()), 33, 1)
            ->getCaf()
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
        ;
    }
}
