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

use Derafu\Array\ArrayReader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractRendererStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaModalidadVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPais;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTransporte;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Traslado;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\OperacionDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\NormalizerException;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataFormatter;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ExamplesWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils as NormalizationUtils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaCompraJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExentaJob;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeLiquidacionFacturaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExportacionJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeGuiaDespachoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoExportacionJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaDebitoExportacionJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaDebitoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\BoletaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\BoletaExentaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaCompraNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaExentaNormalizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\LiquidacionFacturaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaExportacionNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\GuiaDespachoNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaCreditoExportacionNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaCreditoNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaDebitoExportacionNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaDebitoNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\RendererWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\BoletaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\BoletaExentaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaCompraSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaExentaSanitizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\LiquidacionFacturaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaExportacionSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\GuiaDespachoSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaCreditoExportacionSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaCreditoSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaDebitoExportacionSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaDebitoSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\BoletaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\BoletaExentaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaCompraValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExentaValidatorStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\LiquidacionFacturaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\GuiaDespachoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateArrayJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Core\Package\System\Component\Rendering\Support\RenderedDocument;
use libredte\lib\Core\Package\System\Component\Rendering\Support\RenderResult;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\FormatterHandlerProvider;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Core\Provider\TemplatePathProvider;
use libredte\lib\Core\Translation\LibredteTranslationResourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(NormalizerException::class)]
#[CoversClass(ExamplesWorker::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractRendererStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(ImpuestoAdicionalRetencion::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(ImpuestoAdicionalRetencionRepository::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeBoletaAfectaJob::class)]
#[CoversClass(NormalizeBoletaExentaJob::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(NormalizeFacturaCompraJob::class)]
#[CoversClass(NormalizeFacturaExentaJob::class)]
#[CoversClass(NormalizeFacturaExportacionJob::class)]
#[CoversClass(NormalizeGuiaDespachoJob::class)]
//#[CoversClass(NormalizeLiquidacionFacturaJob::class)]
#[CoversClass(NormalizeNotaCreditoExportacionJob::class)]
#[CoversClass(NormalizeNotaCreditoJob::class)]
#[CoversClass(NormalizeNotaDebitoExportacionJob::class)]
#[CoversClass(NormalizeNotaDebitoJob::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(FacturaExentaNormalizerStrategy::class)]
#[CoversClass(BoletaAfectaNormalizerStrategy::class)]
#[CoversClass(BoletaExentaNormalizerStrategy::class)]
//#[CoversClass(LiquidacionFacturaNormalizerStrategy::class)]
#[CoversClass(FacturaCompraNormalizerStrategy::class)]
#[CoversClass(GuiaDespachoNormalizerStrategy::class)]
#[CoversClass(NotaDebitoNormalizerStrategy::class)]
#[CoversClass(NotaCreditoNormalizerStrategy::class)]
#[CoversClass(FacturaExportacionNormalizerStrategy::class)]
#[CoversClass(NotaDebitoExportacionNormalizerStrategy::class)]
#[CoversClass(NotaCreditoExportacionNormalizerStrategy::class)]
#[CoversClass(RendererWorker::class)]
#[CoversClass(RenderResult::class)]
#[CoversClass(RenderedDocument::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(FacturaExentaSanitizerStrategy::class)]
#[CoversClass(BoletaAfectaSanitizerStrategy::class)]
#[CoversClass(BoletaExentaSanitizerStrategy::class)]
//#[CoversClass(LiquidacionFacturaSanitizerStrategy::class)]
#[CoversClass(FacturaCompraSanitizerStrategy::class)]
#[CoversClass(GuiaDespachoSanitizerStrategy::class)]
#[CoversClass(NotaDebitoSanitizerStrategy::class)]
#[CoversClass(NotaCreditoSanitizerStrategy::class)]
#[CoversClass(FacturaExportacionSanitizerStrategy::class)]
#[CoversClass(NotaDebitoExportacionSanitizerStrategy::class)]
#[CoversClass(NotaCreditoExportacionSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(FacturaExentaValidatorStrategy::class)]
#[CoversClass(BoletaAfectaValidatorStrategy::class)]
#[CoversClass(BoletaExentaValidatorStrategy::class)]
//#[CoversClass(LiquidacionFacturaValidatorStrategy::class)]
#[CoversClass(FacturaCompraValidatorStrategy::class)]
#[CoversClass(GuiaDespachoValidatorStrategy::class)]
#[CoversClass(NotaDebitoValidatorStrategy::class)]
#[CoversClass(NotaCreditoValidatorStrategy::class)]
#[CoversClass(FacturaExportacionValidatorStrategy::class)]
#[CoversClass(NotaDebitoExportacionValidatorStrategy::class)]
#[CoversClass(NotaCreditoExportacionValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CreateArrayJob::class)]
#[CoversClass(CreateXmlJob::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(AduanaModalidadVenta::class)]
#[CoversClass(AduanaPais::class)]
#[CoversClass(AduanaTransporte::class)]
#[CoversClass(FormaPago::class)]
#[CoversClass(Traslado::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(TemplateDataFormatter::class)]
#[CoversClass(CategoriaDocumento::class)]
#[CoversClass(OperacionDocumento::class)]
#[UsesClass(LibredteTranslationResourceProvider::class)]
#[UsesClass(FormatterHandlerProvider::class)]
#[UsesClass(RepositorySourceProvider::class)]
#[UsesClass(TemplatePathProvider::class)]
class NormalizerInputFormatsTest extends TestCase
{
    use BuildsDocumentsTrait;

    private const FACTURA = '033_factura_afecta/033_001_simple';

    private const IMPUESTO_ADICIONAL = '033_factura_afecta/033_008_impuesto_adicional';

    private const EXPORTACION = '110_factura_exportacion/110_002_otra_moneda';

    /**
     * Normaliza los datos como lo hace la construcción de un documento.
     *
     * @param array<string, mixed> $data
     */
    private function normalize(array $data): ArrayReader
    {
        $component = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
        ;
        $bag = new DocumentBag(parsedData: $data);
        $component->getDocumentBagManagerWorker()->normalize($bag);

        return ArrayReader::fromArray($component->getNormalizerWorker()->normalize($bag));
    }

    // Detalle.

    public function testDocumentWithoutDetalleIsRejected(): void
    {
        $data = $this->modifiedExample(self::FACTURA, remove: ['Detalle']);

        $this->expectException(NormalizerException::class);
        $this->expectExceptionMessage('Detalle');
        $this->normalize($data);
    }

    public function testDocumentWithEmptyDetalleIsRejected(): void
    {
        $data = $this->modifiedExample(self::FACTURA, ['Detalle' => []]);

        $this->expectException(NormalizerException::class);
        $this->normalize($data);
    }

    public function testASingleItemGivenAsAMappingIsNormalizedAsAListOfOneItem(): void
    {
        $item = ArrayReader::fromArray($this->exampleData(self::FACTURA))->requireMap('Detalle.0');
        $data = $this->modifiedExample(self::FACTURA, ['Detalle' => $item]);

        $normalized = $this->normalize($data);

        $this->assertCount(1, $normalized->requireList('Detalle'));
        $this->assertSame('Conectores RJ45', $normalized->requireString('Detalle.0.NmbItem'));
        $this->assertSame(31500, $normalized->requireAsInt('Detalle.0.MontoItem'));
        $this->assertSame(31500, $normalized->requireAsInt('Encabezado.Totales.MntNeto'));
    }

    // Fechas.

    public function testDatesGivenAsTextAreKept(): void
    {
        $data = $this->modifiedExample(self::FACTURA, [
            'Encabezado.IdDoc.FchEmis' => '2024-03-01',
            'Encabezado.IdDoc.FchVenc' => '2024-03-31',
        ]);

        $normalized = $this->normalize($data);

        $this->assertSame('2024-03-01', $normalized->requireString('Encabezado.IdDoc.FchEmis'));
        $this->assertSame('2024-03-31', $normalized->requireString('Encabezado.IdDoc.FchVenc'));
    }

    // Otra moneda.

    public function testOtraMonedaWithoutExchangeRateIsLeftAsItIs(): void
    {
        $data = $this->modifiedExample(self::EXPORTACION, remove: ['Encabezado.OtraMoneda.TpoCambio']);

        $normalized = $this->normalize($data);

        $this->assertSame('PESO CL', $normalized->requireString('Encabezado.OtraMoneda.0.TpoMoneda'));
        $this->assertNull($normalized->getString('Encabezado.OtraMoneda.0.TpoCambio'));
        $this->assertNull($normalized->getString('Encabezado.OtraMoneda.0.MntExeOtrMnda'));
        $this->assertNull($normalized->getString('Encabezado.OtraMoneda.0.MntTotOtrMnda'));
    }

    public function testOtraMonedaAmountsAreTheTotalsByTheExchangeRate(): void
    {
        $normalized = $this->normalize($this->exampleData(self::EXPORTACION));

        $this->assertSame(50, $normalized->requireAsInt('Encabezado.Totales.MntExe'));
        $this->assertEqualsWithDelta(50000, $normalized->requireAsNumber('Encabezado.OtraMoneda.0.MntExeOtrMnda'), 0.0001);
        $this->assertEqualsWithDelta(50000, $normalized->requireAsNumber('Encabezado.OtraMoneda.0.MntTotOtrMnda'), 0.0001);
        $this->assertEqualsWithDelta(1000, $normalized->requireAsNumber('Encabezado.OtraMoneda.0.TpoCambio'), 0.0001);
    }

    public function testExchangeRateOfZeroGivesZeroAmountsAndIsRemoved(): void
    {
        $data = $this->modifiedExample(self::EXPORTACION, ['Encabezado.OtraMoneda.TpoCambio' => 0]);

        $normalized = $this->normalize($data);

        $this->assertNull($normalized->getString('Encabezado.OtraMoneda.0.TpoCambio'));
        $this->assertEqualsWithDelta(0, $normalized->requireAsNumber('Encabezado.OtraMoneda.0.MntExeOtrMnda'), 0.0001);
        $this->assertEqualsWithDelta(0, $normalized->requireAsNumber('Encabezado.OtraMoneda.0.MntTotOtrMnda'), 0.0001);
    }

    public function testOnlyTheDocumentCurrencyIsKeptWhenThereIsNoOtraMoneda(): void
    {
        $data = $this->modifiedExample(self::EXPORTACION, remove: ['Encabezado.OtraMoneda']);

        $normalized = $this->normalize($data);

        $this->assertSame('DOLAR USA', $normalized->requireString('Encabezado.Totales.TpoMoneda'));
        $this->assertNull($normalized->getString('Encabezado.OtraMoneda'));
    }

    // Impuestos adicionales y retenciones.

    public function testASingleTaxGivenAsAMappingIsNormalizedAsAList(): void
    {
        $data = $this->modifiedExample(self::IMPUESTO_ADICIONAL, [
            'Encabezado.Totales.ImptoReten' => ['TipoImp' => 24, 'MontoImp' => 315],
        ]);

        $normalized = $this->normalize($data);

        $this->assertCount(1, $normalized->requireList('Encabezado.Totales.ImptoReten'));
        $this->assertSame(24, $normalized->requireAsInt('Encabezado.Totales.ImptoReten.0.TipoImp'));
        $this->assertSame(315, $normalized->requireAsInt('Encabezado.Totales.ImptoReten.0.MontoImp'));
    }

    public function testTaxesOfCodesThatAreNotInTheDetalleAreRemoved(): void
    {
        $data = $this->modifiedExample(self::IMPUESTO_ADICIONAL, [
            'Encabezado.Totales.ImptoReten' => [
                ['TipoImp' => 27, 'MontoImp' => 999],
                ['TipoImp' => 24, 'MontoImp' => 315],
            ],
        ]);

        $normalized = $this->normalize($data);

        $impuestos = $normalized->requireList('Encabezado.Totales.ImptoReten');
        $this->assertCount(1, $impuestos);
        $this->assertSame(24, $normalized->requireAsInt('Encabezado.Totales.ImptoReten.0.TipoImp'));
    }

    public function testTaxOfTheDetalleIsCreatedWithItsRateWhenTheTotalsDoNotHaveIt(): void
    {
        $normalized = $this->normalize($this->exampleData(self::IMPUESTO_ADICIONAL));

        $this->assertSame(24, $normalized->requireAsInt('Encabezado.Totales.ImptoReten.0.TipoImp'));
        $this->assertEqualsWithDelta(31.5, $normalized->requireAsNumber('Encabezado.Totales.ImptoReten.0.TasaImp'), 0.0001);
        $this->assertSame(315, $normalized->requireAsInt('Encabezado.Totales.ImptoReten.0.MontoImp'));
        $this->assertSame(1505, $normalized->requireAsInt('Encabezado.Totales.MntTotal'));
    }

    // Código del ítem, retenedor y recargos.

    public function testItemCodeGivenAsTextIsAnInternalCode(): void
    {
        $data = $this->modifiedExample(self::FACTURA, ['Detalle.0.CdgItem' => 'ABC123']);

        $normalized = $this->normalize($data);

        $this->assertSame('INT1', $normalized->requireString('Detalle.0.CdgItem.TpoCodigo'));
        $this->assertSame('ABC123', $normalized->requireString('Detalle.0.CdgItem.VlrCodigo'));
        $this->assertNull($normalized->getString('Detalle.0.Retenedor'));
    }

    public function testItemCodeGivenAsTextOfARetainerIsACpcsCode(): void
    {
        $data = $this->modifiedExample(self::FACTURA, ['Detalle.0.CdgItem' => 'ABC123', 'Detalle.0.Retenedor' => true]);

        $normalized = $this->normalize($data);

        $this->assertSame('CPCS', $normalized->requireString('Detalle.0.CdgItem.TpoCodigo'));
        $this->assertSame('R', $normalized->requireString('Detalle.0.Retenedor.IndAgente'));
    }

    public function testItemWithACpcsCodeIsARetainer(): void
    {
        $data = $this->modifiedExample(self::FACTURA, ['Detalle.0.CdgItem' => ['TpoCodigo' => 'CPCS', 'VlrCodigo' => '1']]);

        $normalized = $this->normalize($data);

        $this->assertSame('R', $normalized->requireString('Detalle.0.Retenedor.IndAgente'));
    }

    public function testSurchargeAmountOfAnItemIsAddedToTheItemAmount(): void
    {
        $data = $this->modifiedExample(self::FACTURA, ['Detalle.0.RecargoMonto' => 1000]);

        $normalized = $this->normalize($data);

        $this->assertSame(32500, $normalized->requireAsInt('Detalle.0.MontoItem'));
        $this->assertSame(32500, $normalized->requireAsInt('Encabezado.Totales.MntNeto'));
    }

    public function testAmountsInGrossAreSplitIntoNetAndVat(): void
    {
        $data = $this->modifiedExample(self::FACTURA, ['Encabezado.IdDoc.MntBruto' => 1]);

        $normalized = $this->normalize($data);

        $this->assertSame(26471, $normalized->requireAsInt('Encabezado.Totales.MntNeto'));
        $this->assertSame(5029, $normalized->requireAsInt('Encabezado.Totales.IVA'));
        $this->assertSame(31500, $normalized->requireAsInt('Encabezado.Totales.MntTotal'));
    }

    public function testNonBillableItemOfAReceiptIsTotalizedAsMontoNf(): void
    {
        $data = $this->modifiedExample('039_boleta_afecta/039_001_simple', ['Detalle.0.IndExe' => 2]);

        $normalized = $this->normalize($data);

        $this->assertSame(1190, $normalized->requireAsInt('Encabezado.Totales.MontoNF'));
    }

    public function testSingleReferenceOfAReceiptIsAListWithoutItsDate(): void
    {
        $data = $this->modifiedExample('039_boleta_afecta/039_001_simple', [
            'Referencia' => ['TpoDocRef' => 'SET', 'FolioRef' => 1, 'FchRef' => '2024-01-10', 'RazonRef' => 'Caso de prueba'],
        ]);

        $normalized = $this->normalize($data);

        $this->assertSame('SET', $normalized->requireString('Referencia.0.TpoDocRef'));
        $this->assertNull($normalized->getString('Referencia.0.FchRef'));
    }

    public function testGlobalSurchargeIsAddedToTheNetAmount(): void
    {
        $data = $this->modifiedExample(self::FACTURA, [
            'DscRcgGlobal' => [['TpoMov' => 'R', 'TpoValor' => '%', 'ValorDR' => 10]],
        ]);

        $normalized = $this->normalize($data);

        $this->assertSame(34650, $normalized->requireAsInt('Encabezado.Totales.MntNeto'));
    }

    public function testGlobalDiscountOfAReceiptCanBeAppliedToTheNonBillableAmount(): void
    {
        $data = $this->modifiedExample('039_boleta_afecta/039_001_simple', [
            'Detalle.0.IndExe' => 2,
            'DscRcgGlobal' => [['TpoMov' => 'D', 'TpoValor' => '%', 'ValorDR' => 10, 'IndExeDR' => 2]],
        ]);

        $normalized = $this->normalize($data);

        $this->assertSame(1071, $normalized->requireAsInt('Encabezado.Totales.MontoNF'));
    }
}
