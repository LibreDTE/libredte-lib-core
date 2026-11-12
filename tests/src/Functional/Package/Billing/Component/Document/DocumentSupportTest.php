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
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use Derafu\Value\ValueCaster;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Document\FacturaAfecta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\OperacionDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BuilderException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\NormalizerException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ExamplesWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\LoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils as NormalizationUtils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExportacionJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaExentaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaExportacionNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaCreditoNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeExportacionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\XmlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\YamlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaExentaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaExportacionSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaCreditoSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExentaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateArrayJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
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

#[CoversClass(ExamplesWorker::class)]
#[CoversClass(LoaderWorker::class)]
#[CoversClass(NormalizeFacturaExentaJob::class)]
#[CoversClass(NormalizeFacturaExportacionJob::class)]
#[CoversClass(NormalizeNotaCreditoJob::class)]
#[CoversClass(FacturaExentaNormalizerStrategy::class)]
#[CoversClass(FacturaExportacionNormalizerStrategy::class)]
#[CoversClass(NotaCreditoNormalizerStrategy::class)]
#[CoversTrait(NormalizeExportacionTrait::class)]
#[CoversClass(FacturaExentaSanitizerStrategy::class)]
#[CoversClass(FacturaExportacionSanitizerStrategy::class)]
#[CoversClass(NotaCreditoSanitizerStrategy::class)]
#[CoversClass(FacturaExentaValidatorStrategy::class)]
#[CoversClass(FacturaExportacionValidatorStrategy::class)]
#[CoversClass(NotaCreditoValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CreateArrayJob::class)]
#[CoversClass(CreateXmlJob::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(SiiEnvironment::class)]
#[CoversClass(BuilderException::class)]
#[CoversClass(FacturaAfecta::class)]
#[CoversClass(NormalizerException::class)]
#[CoversClass(ParserException::class)]
#[CoversClass(CategoriaDocumento::class)]
#[CoversClass(OperacionDocumento::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentException::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(XmlParserStrategy::class)]
#[CoversClass(YamlParserStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(RepositorySourceProvider::class)]
class DocumentSupportTest extends TestCase
{
    use BuildsDocumentsTrait;

    private const FACTURA = '033_factura_afecta/033_001_simple';

    private function component(): DocumentComponent
    {
        $component = Application::getInstance()->getPackageRegistry()->getBillingPackage()->getDocumentComponent();
        assert($component instanceof DocumentComponent);

        return $component;
    }

    private function tipoDocumento(int $codigo): TipoDocumento
    {
        $manager = ValueCaster::requireObject(
            Application::getInstance()->getService(RepositoryManagerInterface::class),
            RepositoryManagerInterface::class
        );
        $tipo = $manager->getRepository(TipoDocumentoInterface::class)->find($codigo);
        $this->assertInstanceOf(TipoDocumento::class, $tipo);

        return $tipo;
    }

    // Tipos de documento.

    public function testTipoDocumentoCanBeCreatedDirectly(): void
    {
        $tipo = new TipoDocumento(
            codigo: 999,
            nombre: 'Documento de prueba',
            nombre_corto: 'Prueba',
            disponible: true,
            alias: 'prueba',
        );

        $this->assertSame(999, $tipo->getCodigo());
        $this->assertSame('Documento de prueba', $tipo->getNombre());
        $this->assertSame('Prueba', $tipo->getNombreCorto());
        $this->assertTrue($tipo->isDisponible());
        $this->assertSame('prueba', $tipo->getAlias());
    }

    public function testTipoDocumentoIsNotAvailableByDefault(): void
    {
        $this->assertFalse((new TipoDocumento(codigo: 999, nombre: 'Sin disponibilidad'))->isDisponible());
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function provideAvailability(): array
    {
        return [
            'factura' => [33, true],
            'factura exenta' => [34, true],
            'boleta' => [39, true],
            'boleta exenta' => [41, true],
            'factura de compra' => [46, true],
            'guia de despacho' => [52, true],
            'nota de debito' => [56, true],
            'nota de credito' => [61, true],
            'factura de exportacion' => [110, true],
            'nota de debito de exportacion' => [111, true],
            'nota de credito de exportacion' => [112, true],
            // No se soporta en LibreDTE.
            'liquidacion de factura' => [43, false],
        ];
    }

    #[DataProvider('provideAvailability')]
    public function testAvailabilityOfTheDocumentTypes(int $codigo, bool $disponible): void
    {
        $this->assertSame($disponible, $this->tipoDocumento($codigo)->isDisponible());
    }

    /**
     * @return array<string, array{int, float|null}>
     */
    public static function provideDefaultCredEC(): array
    {
        return [
            'factura' => [33, 0.65],
            'guia de despacho' => [52, 0.65],
            'nota de debito' => [56, 0.65],
            'nota de credito' => [61, 0.65],
            'boleta' => [39, null],
            'boleta exenta' => [41, null],
            'factura de compra' => [46, null],
            'factura de exportacion' => [110, null],
            'nota de debito de exportacion' => [111, null],
            'nota de credito de exportacion' => [112, null],
        ];
    }

    #[DataProvider('provideDefaultCredEC')]
    public function testDefaultSpecialCreditOfConstructionCompanies(int $codigo, ?float $credito): void
    {
        $this->assertSame($credito, $this->tipoDocumento($codigo)->getDefaultCredEC());
    }

    public function testTipoDocumentoSerialization(): void
    {
        $tipo = $this->tipoDocumento(33);

        $array = $tipo->toArray();

        $this->assertSame(33, $array['codigo']);
        $this->assertSame('Factura electrónica', $array['nombre']);
        $this->assertTrue($array['es_electronico']);
        $this->assertFalse($array['es_boleta']);
        $this->assertSame($array, $tipo->jsonSerialize());
        $this->assertJson((string) json_encode($tipo));
    }

    // Componente y workers.

    public function testTheComponentExposesItsWorkersByName(): void
    {
        $component = $this->component();

        $workers = $component->getWorkers();

        $this->assertSame([
            'batch_processor', 'builder', 'dispatcher', 'document_bag_manager', 'examples', 'loader',
            'normalizer', 'parser', 'renderer', 'sanitizer', 'validator',
        ], array_keys($workers));
        $this->assertSame($component->getBuilderWorker(), $workers['builder']);
        $this->assertSame($component->getNormalizerWorker(), $workers['normalizer']);
        $this->assertSame($component->getValidatorWorker(), $workers['validator']);
        $this->assertSame($component->getExamplesWorker(), $workers['examples']);
    }

    public function testNormalizeWithoutDocumentTypeIsRejected(): void
    {
        $bag = new DocumentBag(parsedData: $this->exampleData(self::FACTURA));

        $this->expectException(NormalizerException::class);
        $this->expectExceptionMessage('Cannot normalize without a TipoDocumento');
        $this->component()->getNormalizerWorker()->normalize($bag);
    }

    public function testParserErrorIsReportedAsParserException(): void
    {
        $bag = new DocumentBag(
            inputData: '{esto no es json',
            options: ['parser' => ['strategy' => 'default.json']],
        );

        try {
            $this->component()->getParserWorker()->parse($bag);
            $this->fail('A ParserException was expected.');
        } catch (ParserException $e) {
            $this->assertStringContainsString('Could not interpret the document', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
            $this->assertNull($bag->getParsedData());
        }
    }

    // Bolsa.

    public function testBagDataReaderReadsTheDataOfTheDocument(): void
    {
        $bag = $this->buildBag(self::FACTURA);

        $this->assertNotNull($bag->getDataReader());
        $this->assertSame(1, $bag->requireDataReader()->requireAsInt('DTE.Documento.Encabezado.IdDoc.Folio'));
        $this->assertSame('76192083-9', $bag->requireDataReader()->requireString('DTE.Documento.Encabezado.Emisor.RUTEmisor'));
    }

    public function testBagWithoutDataHasNoDataReader(): void
    {
        $bag = new DocumentBag();

        $this->assertNull($bag->getDataReader());
        $this->expectException(DocumentException::class);
        $bag->requireDataReader();
    }

    // Documento.

    public function testDocumentRejectsAnXmlOfAnotherDocumentType(): void
    {
        $xml = $this->buildDocument('034_factura_exenta/034_001_simple')->getXmlDocument();

        $this->expectException(TranslatableLogicException::class);
        $this->expectExceptionMessage('does not match the expected value');
        new FacturaAfecta($xml);
    }

    public function testDocumentIdUsesFolioZeroWhenTheXmlHasNoFolio(): void
    {
        $xml = new XmlDocument();
        $xml->loadXml(
            '<DTE xmlns="http://www.sii.cl/SiiDte" version="1.0"><Documento ID="x"><Encabezado>'
            . '<IdDoc><TipoDTE>33</TipoDTE></IdDoc>'
            . '<Emisor><RUTEmisor>76192083-9</RUTEmisor></Emisor>'
            . '</Encabezado></Documento></DTE>'
        );

        $document = new FacturaAfecta($xml);

        $this->assertSame('76192083-9_T033F000000000', $document->getId());
        $this->assertSame('76192083-9_T033F000000000', (string) $document);
    }
}
