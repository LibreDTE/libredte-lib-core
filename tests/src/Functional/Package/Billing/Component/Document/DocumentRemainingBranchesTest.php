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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Document;

use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Support\Factory;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSpreadsheetBatchProcessorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaClausulaVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaFormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaMoneda;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPuerto;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTipoBulto;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTransporte;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaUnidad;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPagoExportacion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\SobreEnvio;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TagXml;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoReferencia;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\TipoDocumentoRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBatch;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessor\Strategy\Spreadsheet\CsvBatchProcessorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessorWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ExamplesWorker;
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
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateArrayJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Stringable;

#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractSpreadsheetBatchProcessorStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(AduanaClausulaVenta::class)]
#[CoversClass(AduanaFormaPago::class)]
#[CoversClass(AduanaMoneda::class)]
#[CoversClass(AduanaPuerto::class)]
#[CoversClass(AduanaTipoBulto::class)]
#[CoversClass(AduanaTransporte::class)]
#[CoversClass(AduanaUnidad::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(FormaPagoExportacion::class)]
#[CoversClass(TagXml::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoReferencia::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(BatchProcessorWorker::class)]
#[CoversClass(CsvBatchProcessorStrategy::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(ExamplesWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(Utils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversTrait(NormalizeDescuentosRecargosTrait::class)]
#[CoversTrait(NormalizeDetalleTrait::class)]
#[CoversTrait(NormalizeImpuestoAdicionalRetencionTrait::class)]
#[CoversTrait(NormalizeIvaMntTotalTrait::class)]
#[CoversTrait(NormalizeTransporteTrait::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CreateArrayJob::class)]
#[CoversClass(CreateXmlJob::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(Application::class)]
#[CoversClass(TipoDocumentoRepository::class)]
#[CoversClass(SobreEnvio::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(DocumentBatch::class)]
#[CoversClass(BatchProcessorException::class)]
#[CoversClass(AutorizacionDte::class)]
class DocumentRemainingBranchesTest extends TestCase
{
    use BuildsDocumentsTrait;

    private const FACTURA = '033_factura_afecta/033_001_simple';

    private function tiposDocumento(): TipoDocumentoRepository
    {
        $manager = Application::getInstance()->getService(RepositoryManagerInterface::class);
        $this->assertInstanceOf(RepositoryManagerInterface::class, $manager);
        $repository = $manager->getRepository(TipoDocumentoInterface::class);
        $this->assertInstanceOf(TipoDocumentoRepository::class, $repository);

        return $repository;
    }

    // Tipos de documento.

    public function testFindByAliasAndByInterface(): void
    {
        $repository = $this->tiposDocumento();
        $factura = $repository->findByAlias('factura_afecta');

        $this->assertNotNull($factura);
        $this->assertSame(33, $factura->getCodigo());
        $this->assertNull($repository->findByAlias('alias_que_no_existe'));

        $interface = $factura->getInterface();
        $this->assertNotNull($interface);
        $this->assertSame(33, $repository->findByInterface($interface)?->getCodigo());
        $this->assertNull($repository->findByInterface('Interfaz\\Que\\NoExiste'));
    }

    public function testReferenceableTypesExcludeTheTaxDocumentsThatAreNotElectronic(): void
    {
        $repository = $this->tiposDocumento();

        $referenciables = $repository->getReferenciables();

        $this->assertNotEmpty($referenciables);
        foreach ($referenciables as $tipo) {
            $this->assertFalse(
                $tipo->getCategoria() === CategoriaDocumento::TRIBUTARIO && $tipo->isElectronico() !== true,
                $tipo->getNombre()
            );
        }
        $this->assertLessThanOrEqual(count($repository->findBy([])), count($referenciables));
    }

    // Entidades de los repositorios.

    /**
     * @return array<string, array{class-string}>
     */
    public static function provideEntities(): array
    {
        $classes = [
            AduanaClausulaVenta::class,
            AduanaFormaPago::class,
            AduanaMoneda::class,
            AduanaPuerto::class,
            AduanaTipoBulto::class,
            AduanaTransporte::class,
            AduanaUnidad::class,
            Comuna::class,
            FormaPagoExportacion::class,
            TagXml::class,
        ];

        return array_combine($classes, array_map(static fn (string $class): array => [$class], $classes));
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideEntities')]
    public function testEntitiesOfTheRepositoriesAreShownWithTheirName(string $class): void
    {
        $manager = Application::getInstance()->getService(RepositoryManagerInterface::class);
        $this->assertInstanceOf(RepositoryManagerInterface::class, $manager);

        $entities = $manager->getRepository($class)->findBy([]);

        $this->assertNotEmpty($entities);
        foreach ($entities as $entity) {
            $this->assertInstanceOf($class, $entity);
            $this->assertInstanceOf(Stringable::class, $entity);
            $this->assertNotSame('', (string) $entity);
        }
    }

    public function testReferenceTypeEntityIsShownWithItsDescription(): void
    {
        $this->assertSame('Anula documento', (string) Factory::create(['glosa' => 'Anula documento'], TipoReferencia::class));
    }

    // Sobre de envío.

    public function testShippingEnvelopeReadsItsCaratula(): void
    {
        $xml = new XmlDocument();
        $xml->loadXml(
            '<EnvioDTE><SetDTE ID="LibreDTE_SetDoc"><Caratula version="1.0">'
            . '<RutEmisor>76192083-9</RutEmisor><RutEnvia>12345678-5</RutEnvia>'
            . '<RutReceptor>60803000-K</RutReceptor><FchResol>2014-08-22</FchResol>'
            . '<NroResol>80</NroResol><TmstFirmaEnv>2024-01-15T10:00:00</TmstFirmaEnv>'
            . '<SubTotDTE><TpoDTE>33</TpoDTE><NroDTE>2</NroDTE></SubTotDTE>'
            . '<SubTotDTE><TpoDTE>61</TpoDTE><NroDTE>1</NroDTE></SubTotDTE>'
            . '</Caratula></SetDTE></EnvioDTE>'
        );

        $sobre = new SobreEnvio($xml);

        $this->assertSame($xml, $sobre->getXmlDocument());
        $this->assertSame('LibreDTE_SetDoc', $sobre->getId());
        $this->assertSame('LibreDTE_SetDoc', (string) $sobre);
        $this->assertSame('76192083-9', $sobre->getRutEmisor());
        $this->assertSame('12345678-5', $sobre->getRunMandatario());
        $this->assertSame('60803000-K', $sobre->getRutReceptor());
        $this->assertSame('2024-01-15T10:00:00', $sobre->getFechaFirma());
        $this->assertSame(['fecha_resolucion' => '2014-08-22', 'numero_resolucion' => 80], $sobre->getAutorizacionDte()->toArray());
        $this->assertCount(2, $sobre->getResumen());
        $this->assertStringStartsWith('<?xml', $sobre->saveXml());
    }

    // Bolsa del documento.

    public function testBagWithoutDocumentHasNoDocumentDataExtraOrStamp(): void
    {
        $bag = new DocumentBag(parsedData: $this->exampleData(self::FACTURA));

        $this->assertNull($bag->getDocumentData());
        $this->assertNull($bag->getDocumentExtra());
        $this->assertNull($bag->getDocumentStamp());
        $this->assertNull($bag->getDocumentAuth());
    }

    public function testBagOfABuiltDocumentGivesItsStampAndItsAuthorization(): void
    {
        $bag = $this->buildBag(self::FACTURA);

        $this->assertNotNull($bag->getDocumentData());
        $this->assertNull($bag->getDocumentExtra());
        $this->assertStringContainsString('<TED', (string) $bag->getDocumentStamp());
        $this->assertNull($bag->getDocumentAuth());

        $bag->requireEmisor()->setAutorizacionDte(new AutorizacionDte('2014-08-22', 80));
        $this->assertSame(['fecha_resolucion' => '2014-08-22', 'numero_resolucion' => 80], $bag->getDocumentAuth());
    }

    // Lote.

    public function testBatchWithoutEmisorOrCertificateReportsWhatIsMissing(): void
    {
        $batch = new DocumentBatch(inputData: "a;b\n");

        $this->expectException(BatchProcessorException::class);
        $this->expectExceptionMessage('emisor');
        $batch->requireEmisor();
    }

    public function testBatchSerializesItsDocuments(): void
    {
        $batch = new DocumentBatch(inputData: "a;b\n");
        $bag = new DocumentBag(parsedData: $this->exampleData(self::FACTURA));
        $batch->setDocumentBags([$bag]);

        $this->assertSame([$bag], $batch->toArray()['document_bags']);
        $this->assertSame([$bag->jsonSerialize()], $batch->jsonSerialize()['document_bags']);
    }

    public function testBatchWithoutEmisorIsParsedWithoutCompletingTheData(): void
    {
        $csv = (string) file_get_contents(self::getFixturesPath('emision_masiva/nombre_pdf_largo.csv'));
        $batch = new DocumentBatch(inputData: $csv);

        $parsed = Application::getInstance()->getPackageRegistry()->getBillingPackage()
            ->getDocumentComponent()->getBatchProcessorWorker()->parse($batch);

        $this->assertCount(1, $parsed->getDocumentBags());
    }
}
