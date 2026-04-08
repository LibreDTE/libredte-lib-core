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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\OwnershipTransfer;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
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
// CoverClass pendientes y sus use
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
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Abstract\AbstractOwnershipTransferDocument;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Contract\AecWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\Aec;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\Cesion;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\DteCedido;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\OwnershipTransferComponent;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Support\AecBag;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\Aec\Job\BuildAecJob;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\Aec\Job\BuildCesionJob;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\Aec\Job\BuildDteCedidoJob;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\AecWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AecWorker::class)]
#[CoversClass(BuildAecJob::class)]
#[CoversClass(BuildDteCedidoJob::class)]
#[CoversClass(BuildCesionJob::class)]
#[CoversClass(Aec::class)]
#[CoversClass(DteCedido::class)]
#[CoversClass(Cesion::class)]
#[CoversClass(AbstractOwnershipTransferDocument::class)]
#[CoversClass(AecBag::class)]
#[CoversClass(OwnershipTransferComponent::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
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
#[CoversClass(BuilderWorker::class)]
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
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
class AecTest extends TestCase
{
    private AecWorkerInterface $worker;

    private CertificateInterface $certificate;

    private DocumentInterface $dte;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->worker = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getOwnershipTransferComponent()
            ->getAecWorker()
        ;

        // Crear certificado falso con el RUT del emisor.
        $this->certificate = (new CertificateFaker(new CertificateLoader()))
            ->createFake(id: '76192083-9');

        // Construir una factura afecta mínima para usar como DTE a ceder.
        $this->dte = $this->buildFacturaAfecta($app);
    }

    /**
     * Genera el AEC (Archivo Electrónico de Cesión) a partir de una factura afecta.
     *
     * Caso de uso: el emisor de una factura afecta (código 33) desea ceder el
     * crédito representado en ese documento a un cesionario (factoring). El AEC
     * agrupa tres documentos XML firmados en capas:
     *   1. `DTECedido`: envuelve el XML del DTE original. ID: `LibreDTE_DTECedido`.
     *   2. `Cesion`: datos del cedente, cesionario, monto cedido y vencimiento.
     *      ID: `LibreDTE_Cesion_1`.
     *   3. `AEC`: documento raíz que contiene los anteriores. ID: `LibreDTE_AEC`.
     *
     * Campos requeridos en `cedente`:
     *   - `RUT`: RUT del cedente.
     *   - `RazonSocial`: razón social del cedente.
     *   - `Direccion`: dirección del cedente (mínimo 5 caracteres).
     *   - `eMail`: correo electrónico del cedente (mínimo 6 caracteres).
     *   - `RUTAutorizado`: arreglo con `RUT` y `Nombre` de persona(s) autorizada(s).
     *
     * Campos requeridos en `cesionario`:
     *   - `RUT`: RUT del cesionario.
     *   - `RazonSocial`: razón social del cesionario.
     *   - `Direccion`: dirección del cesionario (mínimo 5 caracteres).
     *   - `eMail`: correo electrónico del cesionario (mínimo 6 caracteres).
     *
     * Campos requeridos en `cesion`:
     *   - `MontoCesion`: monto del crédito cedido.
     *   - `UltimoVencimiento`: fecha de último vencimiento (YYYY-MM-DD).
     */
    public function testCesionFacturaAfecta(): void
    {
        $bag = new AecBag(
            source: $this->dte,
            cedente: [
                'RUT' => '76192083-9',
                'RazonSocial' => 'SASCO SpA',
                'Direccion' => 'Santa Cruz, Chile',
                'eMail' => 'contacto@sasco.cl',
                'RUTAutorizado' => [
                    'RUT' => '76192083-9',
                    'Nombre' => 'Administrador',
                ],
            ],
            cesionario: [
                'RUT' => '76354771-K',
                'RazonSocial' => 'Factoring S.A.',
                'Direccion' => 'Providencia, Santiago',
                'eMail' => 'cesiones@factoring.cl',
            ],
            cesion: [
                'MontoCesion' => $this->dte->getMontoTotal(),
                'UltimoVencimiento' => date('Y-m-d', strtotime('+30 days')),
            ],
            certificate: $this->certificate,
            seq: 1,
        );

        $aec = $this->worker->build($bag);

        $this->assertInstanceOf(Aec::class, $aec);
        $this->assertSame('LibreDTE_AEC', $aec->getId());

        $this->worker->validateSchema($aec);

        $results = $this->worker->validateSignature($aec);
        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertTrue($result->isValid());
        }
    }

    /**
     * Re-cede un AEC existente añadiendo una segunda cesión.
     *
     * Caso de uso: el cesionario que recibió un AEC quiere ceder el mismo
     * crédito a un tercero. El SII permite hasta 40 cesiones sobre el mismo
     * DTE. El nuevo AEC reutiliza el `DTECedido` original e incorpora todas
     * las `Cesion` anteriores más una nueva con `seq` auto-calculado (N+1).
     *
     * Para construir el bag de re-cesión:
     *   - `source`: el `Aec` existente (en lugar de un `DocumentInterface`).
     *   - `cedente`: quien cede ahora (era el cesionario anterior).
     *   - `cesionario`: quien recibe la nueva cesión.
     *   - `cesion`: monto y vencimiento de la nueva cesión.
     *   - `seq`: omitir para calcular automáticamente (2 en este caso).
     */
    public function testRecesionFacturaAfecta(): void
    {
        // Construir el AEC original (primera cesión).
        $bagPrimera = new AecBag(
            source: $this->dte,
            cedente: [
                'RUT' => '76192083-9',
                'RazonSocial' => 'SASCO SpA',
                'Direccion' => 'Santa Cruz, Chile',
                'eMail' => 'contacto@sasco.cl',
                'RUTAutorizado' => [
                    'RUT' => '76192083-9',
                    'Nombre' => 'Administrador',
                ],
            ],
            cesionario: [
                'RUT' => '76354771-K',
                'RazonSocial' => 'Factoring S.A.',
                'Direccion' => 'Providencia, Santiago',
                'eMail' => 'cesiones@factoring.cl',
            ],
            cesion: [
                'MontoCesion' => $this->dte->getMontoTotal(),
                'UltimoVencimiento' => date('Y-m-d', strtotime('+30 days')),
            ],
            certificate: $this->certificate,
        );
        $aecOriginal = $this->worker->build($bagPrimera);

        // Re-ceder el AEC: el cesionario original (Factoring S.A.) cede a otro.
        $bagRecesion = new AecBag(
            source: $aecOriginal,
            cedente: [
                'RUT' => '76354771-K',
                'RazonSocial' => 'Factoring S.A.',
                'Direccion' => 'Providencia, Santiago',
                'eMail' => 'cesiones@factoring.cl',
                'RUTAutorizado' => [
                    'RUT' => '76354771-K',
                    'Nombre' => 'Gerente Factoring',
                ],
            ],
            cesionario: [
                'RUT' => '77123456-7',
                'RazonSocial' => 'Fondo de Inversión SpA',
                'Direccion' => 'Las Condes, Santiago',
                'eMail' => 'inversiones@fondo.cl',
            ],
            cesion: [
                'MontoCesion' => $this->dte->getMontoTotal(),
                'UltimoVencimiento' => date('Y-m-d', strtotime('+60 days')),
            ],
            certificate: $this->certificate,
        );

        $aecRecedido = $this->worker->build($bagRecesion);

        $this->assertInstanceOf(Aec::class, $aecRecedido);
        $this->assertSame('LibreDTE_AEC', $aecRecedido->getId());

        // El XML debe contener ambas cesiones.
        $xml = $aecRecedido->getXml();
        $this->assertStringContainsString('LibreDTE_Cesion_1', $xml);
        $this->assertStringContainsString('LibreDTE_Cesion_2', $xml);

        $this->worker->validateSchema($aecRecedido);

        $results = $this->worker->validateSignature($aecRecedido);
        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertTrue($result->isValid());
        }
    }

    /**
     * Construye una factura afecta (código 33) mínima para los tests.
     */
    private function buildFacturaAfecta(Application $app): DocumentInterface
    {
        /** @var BuilderWorkerInterface $builder */
        $builder = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getBuilderWorker()
        ;

        /** @var CafFakerWorkerInterface $cafFaker */
        $cafFaker = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafFakerWorker()
        ;

        $emisor = new Emisor('76192083-9', 'SASCO SpA');

        $cafBag = $cafFaker->create($emisor, 33, 1);
        $caf = $cafBag->getCaf();

        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 33,
                    'Folio' => 1,
                ],
                'Emisor' => [
                    'RUTEmisor' => '76192083-9',
                    'RznSoc' => 'SASCO SpA',
                    'GiroEmis' => 'Tecnología, informática y telecomunicaciones',
                    'Acteco' => 726000,
                    'DirOrigen' => 'Santa Cruz',
                    'CmnaOrigen' => 'Santa Cruz',
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
                    'NmbItem' => 'Servicio de consultoría',
                    'QtyItem' => 1,
                    'PrcItem' => 100000,
                ],
            ],
        ];

        $bag = new DocumentBag(
            parsedData: $data,
            caf: $caf,
            certificate: $this->certificate
        );

        $builder->build($bag);

        return $bag->getDocument();
    }
}
