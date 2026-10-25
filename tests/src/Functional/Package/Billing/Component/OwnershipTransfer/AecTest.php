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
// Pending CoversClass entries and their use statements
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
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
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
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

        // Create fake certificate with the issuer RUT.
        $this->certificate = (new CertificateFaker(new CertificateLoader()))
            ->createFake(id: '76192083-9');

        // Build a minimal taxable invoice to use as the DTE to assign.
        $this->dte = $this->buildFacturaAfecta($app);
    }

    /**
     * Generates the AEC (Electronic Assignment File) from a taxable invoice.
     *
     * Use case: the issuer of a taxable invoice (code 33) wants to assign the
     * credit represented in that document to an assignee (factoring). The AEC
     * groups three layered signed XML documents:
     *   1. `DTECedido`: wraps the original DTE XML. ID: `LibreDTE_DTECedido`.
     *   2. `Cesion`: assignor, assignee, assigned amount, and due date data.
     *      ID: `LibreDTE_Cesion_1`.
     *   3. `AEC`: root document containing the above. ID: `LibreDTE_AEC`.
     *
     * Required fields in `cedente`:
     *   - `RUT`: assignor RUT.
     *   - `RazonSocial`: assignor legal name.
     *   - `Direccion`: assignor address (minimum 5 characters).
     *   - `eMail`: assignor email (minimum 6 characters).
     *   - `RUTAutorizado`: array with `RUT` and `Nombre` of authorized person(s).
     *
     * Required fields in `cesionario`:
     *   - `RUT`: assignee RUT.
     *   - `RazonSocial`: assignee legal name.
     *   - `Direccion`: assignee address (minimum 5 characters).
     *   - `eMail`: assignee email (minimum 6 characters).
     *
     * Required fields in `cesion`:
     *   - `MontoCesion`: assigned credit amount.
     *   - `UltimoVencimiento`: last due date (YYYY-MM-DD).
     */
    public function testCesionFacturaAfecta(): void
    {
        $bag = new AecBag(
            source: $this->dte,
            cedente: [
                'RUT' => '76192083-9',
                'RazonSocial' => 'SASCO SpA',
                'Direccion' => 'Santa Cruz, Chile',
                'eMail' => 'cedente@example.com',
                'RUTAutorizado' => [
                    'RUT' => '76192083-9',
                    'Nombre' => 'Administrador',
                ],
            ],
            cesionario: [
                'RUT' => '76354771-K',
                'RazonSocial' => 'Factoring S.A.',
                'Direccion' => 'Providencia, Santiago',
                'eMail' => 'factoring@example.com',
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
     * Re-assigns an existing AEC by adding a second assignment.
     *
     * Use case: the assignee who received an AEC wants to assign the same
     * credit to a third party. The SII allows up to 40 assignments on the same
     * DTE. The new AEC reuses the original `DTECedido` and includes all prior
     * `Cesion` entries plus a new one with auto-calculated `seq` (N+1).
     *
     * To build the re-assignment bag:
     *   - `source`: the existing `Aec` (instead of a `DocumentInterface`).
     *   - `cedente`: who assigns now (was the previous assignee).
     *   - `cesionario`: who receives the new assignment.
     *   - `cesion`: amount and due date of the new assignment.
     *   - `seq`: omit to calculate automatically (2 in this case).
     */
    public function testRecesionFacturaAfecta(): void
    {
        // Build the original AEC (first assignment).
        $firstAssignmentBag = new AecBag(
            source: $this->dte,
            cedente: [
                'RUT' => '76192083-9',
                'RazonSocial' => 'SASCO SpA',
                'Direccion' => 'Santa Cruz, Chile',
                'eMail' => 'cedente@example.com',
                'RUTAutorizado' => [
                    'RUT' => '76192083-9',
                    'Nombre' => 'Administrador',
                ],
            ],
            cesionario: [
                'RUT' => '76354771-K',
                'RazonSocial' => 'Factoring S.A.',
                'Direccion' => 'Providencia, Santiago',
                'eMail' => 'factoring@example.com',
            ],
            cesion: [
                'MontoCesion' => $this->dte->getMontoTotal(),
                'UltimoVencimiento' => date('Y-m-d', strtotime('+30 days')),
            ],
            certificate: $this->certificate,
        );
        $aecOriginal = $this->worker->build($firstAssignmentBag);

        // Re-assign the AEC: the original assignee (Factoring S.A.) assigns to another party.
        $bagRecesion = new AecBag(
            source: $aecOriginal,
            cedente: [
                'RUT' => '76354771-K',
                'RazonSocial' => 'Factoring S.A.',
                'Direccion' => 'Providencia, Santiago',
                'eMail' => 'factoring@example.com',
                'RUTAutorizado' => [
                    'RUT' => '76354771-K',
                    'Nombre' => 'Gerente Factoring',
                ],
            ],
            cesionario: [
                'RUT' => '77123456-7',
                'RazonSocial' => 'Fondo de Inversión SpA',
                'Direccion' => 'Las Condes, Santiago',
                'eMail' => 'fondo@example.com',
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

        // The XML must contain both assignments.
        $xml = $aecRecedido->saveXml();
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
     * Builds a minimal taxable invoice (code 33) for tests.
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

        $caf = $cafFaker->create($emisor, 33, 1);

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
