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
use Derafu\Xml\XmlDocument;
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
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
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
class DocumentEntityTest extends TestCase
{
    use BuildsDocumentsTrait;

    private const AFECTA = '033_factura_afecta/033_001_simple';

    private const EXENTA = '034_factura_exenta/034_001_simple';

    private const NOTA_CREDITO = '061_nota_credito/061_001_anula_factura';

    private const EXPORTACION = '110_factura_exportacion/110_002_otra_moneda';

    public function testIdentificationOfAFacturaAfecta(): void
    {
        $document = $this->buildDocument(self::AFECTA);

        $this->assertSame(CodigoDocumento::FACTURA_AFECTA, $document->getTipoDocumento());
        $this->assertSame(33, $document->getCodigo());
        $this->assertSame(1, $document->getFolio());
        $this->assertSame('76192083-9_T033F000000001', $document->getId());
        $this->assertSame($document->getId(), (string) $document);
        $this->assertSame('76192083-9', $document->getRutEmisor());
        $this->assertSame('60803000-K', $document->getRutReceptor());
        $this->assertSame('Servicio de Impuestos Internos', $document->getRazonSocialReceptor());
        $this->assertSame('Servicio de Impuestos Internos', $document->requireRazonSocialReceptor());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $document->getFechaEmision());
        $this->assertSame(123456, $document->getSucursalSii());
        $this->assertSame('76192083-9', $document->getEmisor()['RUTEmisor']);
        $this->assertSame('60803000-K', $document->getReceptor()['RUTRecep']);
    }

    public function testTotalsOfAFacturaAfecta(): void
    {
        $document = $this->buildDocument(self::AFECTA);

        $this->assertSame(31500, $document->getMontoNeto());
        $this->assertSame(5985, $document->getMontoIVA());
        $this->assertSame(37485, $document->getMontoTotal());
        $this->assertNull($document->getMontoExento());
        $this->assertSame(31500, $document->getNeto());
        $this->assertSame(5985, $document->getIVA());
        $this->assertSame(37485, $document->getTotal());
        $this->assertNull($document->getExento());
        $this->assertSame(
            $document->getNeto() + $document->getIVA(),
            $document->getTotal()
        );
        $this->assertSame('PESO CL', $document->getMoneda());
        $this->assertSame('19', ArrayReader::fromArray($document->getTotales())->requireAsString('TasaIVA'));
    }

    public function testTotalsOfAFacturaExenta(): void
    {
        $document = $this->buildDocument(self::EXENTA);

        $this->assertSame(125000, $document->getMontoExento());
        $this->assertSame(125000, $document->getExento());
        $this->assertSame(125000, $document->getTotal());
        $this->assertNull($document->getMontoNeto());
        $this->assertNull($document->getMontoIVA());
        $this->assertNull($document->getNeto());
        $this->assertNull($document->getIVA());
    }

    public function testTotalsOfANotaCreditoWithZeroExento(): void
    {
        $document = $this->buildDocument(self::NOTA_CREDITO);

        // Un monto exento cero es un valor y no la ausencia de monto.
        $this->assertSame(0, $document->getMontoExento());
        $this->assertSame(0, $document->getExento());
        $this->assertSame(37485, $document->getTotal());
    }

    public function testTotalsInForeignCurrentAreConvertedToPesos(): void
    {
        $document = $this->buildDocument(self::EXPORTACION);

        $this->assertSame('DOLAR USA', $document->getMoneda());
        $this->assertSame(50, $document->getMontoTotal());
        $this->assertSame(50, $document->getMontoExento());
        $this->assertSame(50000, $document->getTotal());
        $this->assertSame(50000, $document->getExento());
        $this->assertSame(1000.0, $document->getTipoDeCambio('PESO CL'));
    }

    public function testTipoDeCambioOfACurrencyThatIsNotInTheDocumentIsNull(): void
    {
        $this->assertNull($this->buildDocument(self::EXPORTACION)->getTipoDeCambio('EURO'));
        $this->assertNull($this->buildDocument(self::AFECTA)->getTipoDeCambio());
    }

    public function testConvertirAPesosCL(): void
    {
        $document = $this->buildDocument(self::EXPORTACION);

        // Sin indicar la moneda usa la del documento.
        $this->assertSame(10000.0, $document->convertirAPesosCL(10));
        $this->assertSame(2500.0, $document->convertirAPesosCL(2.5, 'DOLAR USA'));
        // Un valor que ya está en pesos no se convierte.
        $this->assertSame(10, $document->convertirAPesosCL(10, 'PESO CL'));
    }

    public function testConvertirAPesosCLWithoutExchangeRateThrows(): void
    {
        $document = $this->buildDocument(self::AFECTA);

        $this->assertSame(10, $document->convertirAPesosCL(10));

        $this->expectException(DocumentException::class);
        $this->expectExceptionMessage('DOLAR USA');
        $document->convertirAPesosCL(10, 'DOLAR USA');
    }

    public function testDetalle(): void
    {
        $data = $this->exampleData('033_factura_afecta/033_015_factura_varias_guias');
        $document = $this->buildDocument('033_factura_afecta/033_015_factura_varias_guias');
        $expected = count(ArrayReader::fromArray($data)->requireList('Detalle'));

        $this->assertGreaterThan(1, $expected);
        $this->assertCount($expected, $document->getDetalle());
        $this->assertSame('1', ArrayReader::fromArray($document->getDetalle(0))->requireAsString('NroLinDet'));
        $this->assertSame(
            (string) $expected,
            ArrayReader::fromArray($document->getDetalle($expected - 1))->requireAsString('NroLinDet')
        );
        $this->assertSame($document->getDetalle()[1], $document->getDetalle(1));
        $this->assertSame([], $document->getDetalle($expected));
        $this->assertSame([], $document->getDetalle(99));
    }

    public function testReferencias(): void
    {
        $this->assertSame([], $this->buildDocument(self::AFECTA)->getReferencias());

        $referencias = $this->buildDocument(self::NOTA_CREDITO)->getReferencias();

        $this->assertCount(1, $referencias);
        $referencia = ArrayReader::fromArray($referencias);
        $this->assertSame('33', $referencia->requireAsString('0.TpoDocRef'));
        $this->assertSame('Anula factura', $referencia->requireAsString('0.RazonRef'));
    }

    public function testTedAndTemplateTedOfAStampedDocument(): void
    {
        $document = $this->buildDocument(self::AFECTA);

        $ted = $document->getTED();
        $this->assertNotNull($ted);
        $this->assertStringStartsWith('<TED', $ted);
        $this->assertStringContainsString('<RE>76192083-9</RE>', $ted);
        $this->assertStringContainsString('<MNT>37485</MNT>', $ted);

        $dd = ArrayReader::fromArray($document->getTemplateTED())->requireReader('TED.DD');
        $this->assertSame('76192083-9', $dd->requireString('RE'));
        $this->assertSame(33, $dd->requireInt('TD'));
        $this->assertSame(1, $dd->requireInt('F'));
        $this->assertSame($document->getFechaEmision(), $dd->requireString('FE'));
        $this->assertSame('60803000-K', $dd->requireString('RR'));
        $this->assertSame(37485, $dd->requireInt('MNT'));
        $this->assertSame('Conectores RJ45', $dd->requireString('IT1'));
    }

    public function testTemplateTedTruncatesTheLongTextsToFortyCharacters(): void
    {
        $data = $this->modifiedExample(self::AFECTA, [
            'Encabezado.Receptor.RznSocRecep' => str_repeat('Ñandú ', 12),
            'Detalle.0.NmbItem' => str_repeat('Ñoño ', 14),
        ]);

        $dd = ArrayReader::fromArray($this->buildDocument(self::AFECTA, $data)->getTemplateTED())
            ->requireReader('TED.DD')
        ;

        $this->assertSame(40, mb_strlen($dd->requireString('RSR')));
        $this->assertSame(40, mb_strlen($dd->requireString('IT1')));
        $this->assertStringStartsWith('Ñandú Ñandú', $dd->requireString('RSR'));
        $this->assertStringStartsWith('Ñoño Ñoño', $dd->requireString('IT1'));
    }

    public function testFirmaOfASignedDocumentHasNoLineBreaks(): void
    {
        $firma = $this->buildDocument(self::AFECTA)->getFirma();

        $this->assertNotNull($firma);
        $this->assertNotEmpty($firma);
        $this->assertStringNotContainsString("\n", $firma);
        $this->assertNotFalse(base64_decode($firma, true));
    }

    public function testFirmaAndTedOfADocumentThatIsNotSignedNorStamped(): void
    {
        $xml = $this->buildDocument(self::AFECTA)->getXmlDocument()->getXml();
        $xml = (string) preg_replace(['#<Signature.*?</Signature>#s', '#<TED.*?</TED>#s'], '', $xml);
        $loaded = $this->loadDocument($xml);

        $this->assertNull($loaded->getFirma());
        $this->assertNull($loaded->getTED());
        $this->assertNull($loaded->getEnvironment());
        $this->assertNull($loaded->getCertificacion());
    }

    /**
     * @return array<string, array{int, SiiEnvironment|null, int|null}>
     */
    public static function provideCafIdk(): array
    {
        return [
            'fake caf' => [666, null, null],
            'staging' => [100, SiiEnvironment::STAGING, 1],
            'production' => [300, SiiEnvironment::PRODUCTION, 0],
            'unknown' => [999, null, null],
        ];
    }

    #[DataProvider('provideCafIdk')]
    public function testEnvironmentComesFromTheIdkOfTheCafInTheTed(int $idk, ?SiiEnvironment $environment, ?int $certificacion): void
    {
        $xml = $this->buildDocument(self::AFECTA)->getXmlDocument()->getXml();
        $xml = str_replace('<IDK>666</IDK>', '<IDK>' . $idk . '</IDK>', $xml);
        $loaded = $this->loadDocument($xml);

        $this->assertSame($environment, $loaded->getEnvironment());
        $this->assertSame($certificacion, $loaded->getCertificacion());
    }

    public function testGetAndQuery(): void
    {
        $document = $this->buildDocument(self::AFECTA);

        $this->assertSame(1, $document->get('Encabezado.IdDoc.Folio'));
        $this->assertSame('76192083-9', $document->get('Encabezado.Emisor.RUTEmisor'));
        $this->assertNull($document->get('Encabezado.NoExiste'));
        $this->assertSame('1', $document->query('//Encabezado/IdDoc/Folio'));
        $this->assertNull($document->query('//Encabezado/NoExiste'));
    }

    public function testToArrayAndJsonSerialize(): void
    {
        $document = $this->buildDocument(self::AFECTA);

        $array = $document->toArray();
        $this->assertSame(['id', 'datos', 'ted', 'xml'], array_keys($array));
        $reader = ArrayReader::fromArray($array);
        $this->assertSame($document->getId(), $reader->requireString('id'));
        $this->assertSame($document->getData(), $reader->requireMap('datos'));
        $this->assertSame($document->getTED(), $reader->requireString('ted'));
        $this->assertStringStartsWith('<?xml', $reader->requireString('xml'));

        $json = ArrayReader::fromValue($document->jsonSerialize());
        $this->assertSame($reader->requireString('id'), $json->requireString('id'));
        $this->assertSame($reader->requireMap('datos'), $json->requireMap('datos'));
        $this->assertSame($reader->requireString('ted'), base64_decode($json->requireString('ted'), true));
        $this->assertSame($reader->requireString('xml'), base64_decode($json->requireString('xml'), true));
        $this->assertJson((string) json_encode($document));
    }

    public function testGetDataDoesNotIncludeTedNorTimestamp(): void
    {
        $data = $this->buildDocument(self::AFECTA)->getData();

        $this->assertArrayNotHasKey('TED', $data);
        $this->assertArrayNotHasKey('TmstFirma', $data);
        $this->assertArrayHasKey('Encabezado', $data);
        $this->assertArrayHasKey('Detalle', $data);
    }

    public function testGetDataKeepsASingleReferenciaAsAList(): void
    {
        $data = ArrayReader::fromArray($this->buildDocument(self::NOTA_CREDITO)->getData());

        $this->assertCount(1, $data->requireList('Referencia'));
        $this->assertCount(1, $data->requireList('Detalle'));
    }

    /**
     * Carga un documento desde su XML.
     */
    private function loadDocument(string $xml): DocumentInterface
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        return Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getLoaderWorker()
            ->loadXml($xmlDocument)
            ->requireDocument()
        ;
    }
}
