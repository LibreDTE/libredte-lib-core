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
use Derafu\Backbone\Exception\StrategyNotFoundException;
use Derafu\Translation\Exception\Runtime\TranslatableUnexpectedValueException;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentBagManagerException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
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
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\YamlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
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
use ReflectionMethod;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CreateArrayJob::class)]
#[CoversClass(CreateXmlJob::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(BuilderWorker::class)]
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
#[CoversClass(ParserWorker::class)]
#[CoversClass(YamlParserStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(DocumentBagManagerException::class)]
#[CoversClass(DocumentException::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(Receptor::class)]
class DocumentBagManagerWorkerTest extends TestCase
{
    use BuildsDocumentsTrait;

    private const EXAMPLE = '033_factura_afecta/033_001_simple';

    private DocumentBagManagerWorkerInterface $manager;

    protected function setUp(): void
    {
        $this->manager = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getDocumentBagManagerWorker()
        ;
    }

    /**
     * Invoca un método protegido del worker real.
     */
    private function callProtected(string $method, DocumentBagInterface $bag): void
    {
        $reflection = new ReflectionMethod(DocumentBagManagerWorker::class, $method);
        $reflection->invoke($this->manager, $bag);
    }

    public function testCreateFromYamlString(): void
    {
        $yaml = Yaml::dump($this->exampleData(self::EXAMPLE), 8);

        $bag = $this->manager->create('yaml:' . $yaml);

        $this->assertSame(33, $bag->requireTipoDocumento()->getCodigo());
        $this->assertNotEmpty($bag->getParsedData());
        $this->assertNotEmpty($bag->getNormalizedData());
        $this->assertSame(37485, $bag->requireDocument()->getMontoTotal());
        $this->assertSame('76192083-9', $bag->requireEmisor()->getRut());
        $this->assertSame('60803000-K', $bag->getReceptor()?->getRut());
    }

    public function testCreateFromJsonStringWithStrategyPrefix(): void
    {
        $json = (string) json_encode($this->exampleData(self::EXAMPLE));

        $bag = $this->manager->create('parser.strategy.json:' . $json);

        $this->assertSame(37485, $bag->requireDocument()->getMontoTotal());
    }

    public function testCreateFromStringKeepsColonsInsideTheData(): void
    {
        $data = $this->modifiedExample(self::EXAMPLE, ['Detalle.0.NmbItem' => 'Conector: tipo RJ45']);

        $bag = $this->manager->create('json:' . json_encode($data));

        $this->assertSame('Conector: tipo RJ45', $bag->requireDocument()->getDetalle(0)['NmbItem']);
    }

    public function testCreateFromStringWithUnknownStrategyThrows(): void
    {
        $this->expectException(StrategyNotFoundException::class);
        $this->manager->create('no_existe:{}');
    }

    public function testCreateWithoutNormalizingAllOnlyParsesAndFindsTheDocumentType(): void
    {
        $yaml = Yaml::dump($this->exampleData(self::EXAMPLE), 8);

        $bag = $this->manager->create('yaml:' . $yaml, normalizeAll: false);

        $this->assertNotEmpty($bag->getParsedData());
        $this->assertSame(33, $bag->requireTipoDocumento()->getCodigo());
        $this->assertNull($bag->getNormalizedData());
        $this->assertNull($bag->getXmlDocument());
        $this->assertNull($bag->getDocument());
        $this->assertNull($bag->getEmisor());
        $this->assertNull($bag->getReceptor());
    }

    public function testNormalizeCompletesABagThatWasCreatedWithoutNormalizingAll(): void
    {
        $bag = $this->manager->create($this->exampleData(self::EXAMPLE), normalizeAll: false);
        $this->assertNull($bag->getDocument());

        $same = $this->manager->normalize($bag, all: true);

        $this->assertSame($bag, $same);
        $this->assertNotNull($bag->getDocument());
        $this->assertNotNull($bag->getXmlDocument());
        $this->assertSame('76192083-9', $bag->requireEmisor()->getRut());
    }

    public function testCreateFromNormalizedDataOfABuiltDocument(): void
    {
        $document = $this->buildDocument(self::EXAMPLE);

        $bag = $this->manager->create($document->getData());

        $this->assertSame(33, $bag->requireTipoDocumento()->getCodigo());
        $this->assertSame($document->getMontoTotal(), $bag->requireDocument()->getMontoTotal());
        $this->assertSame($document->getFolio(), $bag->requireDocument()->getFolio());
        $this->assertSame($document->getRutReceptor(), $bag->getReceptor()?->getRut());
    }

    public function testCreateFromXmlDocumentOfABuiltDocument(): void
    {
        $document = $this->buildDocument(self::EXAMPLE);

        $bag = $this->manager->create($document->getXmlDocument());

        $this->assertSame($document->getXmlDocument(), $bag->getXmlDocument());
        $this->assertNull($bag->getParsedData());
        $this->assertSame(33, $bag->requireTipoDocumento()->getCodigo());
        $this->assertSame($document->getId(), $bag->requireDocument()->getId());
        $this->assertSame($document->getMontoTotal(), $bag->requireNormalizedDataReader()->requireAsInt('Encabezado.Totales.MntTotal'));
        $this->assertSame('76192083-9', $bag->requireEmisor()->getRut());
        $this->assertSame('60803000-K', $bag->getReceptor()?->getRut());
    }

    public function testCreateFromXmlDocumentWithoutNormalizingAllOnlyDeterminesTheType(): void
    {
        $document = $this->buildDocument(self::EXAMPLE);

        $bag = $this->manager->create($document->getXmlDocument(), normalizeAll: false);

        $this->assertSame(33, $bag->requireTipoDocumento()->getCodigo());
        $this->assertNull($bag->getDocument());
        $this->assertNull($bag->getNormalizedData());
    }

    public function testCreateFromDocumentKeepsTheSameDocumentInstance(): void
    {
        $document = $this->buildDocument(self::EXAMPLE);

        $bag = $this->manager->create($document);

        $this->assertSame($document, $bag->getDocument());
        $this->assertSame($document->getXmlDocument(), $bag->getXmlDocument());
        $this->assertSame($document->getRutEmisor(), $bag->requireEmisor()->getRut());
        $this->assertSame($document->getRutReceptor(), $bag->getReceptor()?->getRut());
        $this->assertNotEmpty($bag->getNormalizedData());
    }

    public function testNormalizeDoesNotReplaceDataThatIsAlreadyInTheBag(): void
    {
        $bag = $this->manager->create($this->buildDocument(self::EXAMPLE));
        $document = $bag->getDocument();
        $xmlDocument = $bag->getXmlDocument();
        $emisor = $bag->getEmisor();
        $receptor = $bag->getReceptor();
        $normalized = $bag->getNormalizedData();

        $this->manager->normalize($bag, all: true);

        $this->assertSame($document, $bag->getDocument());
        $this->assertSame($xmlDocument, $bag->getXmlDocument());
        $this->assertSame($emisor, $bag->getEmisor());
        $this->assertSame($receptor, $bag->getReceptor());
        $this->assertSame($normalized, $bag->getNormalizedData());
    }

    public function testDocumentTypeThatDoesNotExistThrows(): void
    {
        $this->expectException(DocumentBagManagerException::class);
        $this->manager->create(['Encabezado' => ['IdDoc' => ['TipoDTE' => 999]]]);
    }

    public function testMissingDocumentTypeThrows(): void
    {
        $this->expectException(DocumentException::class);
        $this->manager->create(['Encabezado' => ['IdDoc' => []]]);
    }

    public function testEmisorOfTheBagCompletesTheParsedDataWhenItHasNone(): void
    {
        $data = $this->modifiedExample(self::EXAMPLE, remove: ['Encabezado.Emisor']);
        $bag = new DocumentBag(parsedData: $data, emisor: new Emisor(
            rut: '76192083-9',
            razon_social: 'SASCO SpA',
            giro: 'Informática',
            direccion: 'Santa Cruz',
        ));

        $this->manager->normalize($bag, all: true);

        $emisor = $bag->requireDocument()->getEmisor();
        $this->assertSame('76192083-9', $emisor['RUTEmisor']);
        $this->assertSame('SASCO SpA', $emisor['RznSoc']);
        $this->assertSame('Informática', $emisor['GiroEmis']);
        $this->assertSame('Santa Cruz', $emisor['DirOrigen']);
    }

    public function testEmisorOfTheBagIsMergedWithTheParsedEmisorWithoutRut(): void
    {
        $data = $this->modifiedExample(self::EXAMPLE, ['Encabezado.Emisor' => ['RznSoc' => 'Razón informada en los datos']]);
        $bag = new DocumentBag(parsedData: $data, emisor: new Emisor(
            rut: '76192083-9',
            razon_social: 'Razón del emisor de la bolsa',
            giro: 'Informática',
        ));

        $this->manager->normalize($bag, all: true);

        $emisor = $bag->requireDocument()->getEmisor();
        // El RUT viene de la bolsa; lo informado en los datos tiene prioridad.
        $this->assertSame('76192083-9', $emisor['RUTEmisor']);
        $this->assertSame('Razón informada en los datos', $emisor['RznSoc']);
        $this->assertSame('Informática', $emisor['GiroEmis']);
    }

    public function testEmisorInTheParsedDataHasPriorityOverTheOneOfTheBag(): void
    {
        $data = $this->exampleData(self::EXAMPLE);
        $bag = new DocumentBag(parsedData: $data, emisor: new Emisor('11111111-1', 'Otro emisor'));

        $this->manager->normalize($bag, all: true);

        $this->assertSame(
            ArrayReader::fromArray($data)->requireString('Encabezado.Emisor.RUTEmisor'),
            $bag->requireDocument()->getRutEmisor()
        );
    }

    public function testReceptorOfTheBagCompletesTheParsedDataWhenItHasNone(): void
    {
        $data = $this->modifiedExample(self::EXAMPLE, remove: ['Encabezado.Receptor']);
        $bag = new DocumentBag(parsedData: $data, receptor: new Receptor(
            rut: '60803000-K',
            razon_social: 'Servicio de Impuestos Internos',
            giro: 'Gobierno',
            direccion: 'Teatinos 120',
        ));

        $this->manager->normalize($bag, all: true);

        $receptor = $bag->requireDocument()->getReceptor();
        $this->assertSame('60803000-K', $receptor['RUTRecep']);
        $this->assertSame('Gobierno', $receptor['GiroRecep']);
        $this->assertSame('Teatinos 120', $receptor['DirRecep']);
    }

    public function testReceptorInTheParsedDataHasPriorityOverTheOneOfTheBag(): void
    {
        $data = $this->exampleData(self::EXAMPLE);
        $bag = new DocumentBag(parsedData: $data, receptor: new Receptor('11111111-1', 'Otro receptor'));

        $this->manager->normalize($bag, all: true);

        $this->assertSame(
            ArrayReader::fromArray($data)->requireString('Encabezado.Receptor.RUTRecep'),
            $bag->requireDocument()->getRutReceptor()
        );
    }

    public function testEmisorAndReceptorAreNotAddedToTheParsedDataWhenThereIsAnXmlDocument(): void
    {
        $document = $this->buildDocument(self::EXAMPLE);
        $bag = new DocumentBag(
            xmlDocument: $document->getXmlDocument(),
            emisor: new Emisor('11111111-1', 'Otro emisor'),
            receptor: new Receptor('22222222-2', 'Otro receptor'),
        );

        $this->manager->normalize($bag, all: true);

        $this->assertNull($bag->getParsedData());
        $this->assertSame('76192083-9', $bag->requireDocument()->getRutEmisor());
        $this->assertSame('60803000-K', $bag->requireDocument()->getRutReceptor());
    }

    public function testNormalizeOptionsCanSkipTheNormalization(): void
    {
        $bag = new DocumentBag(
            parsedData: $this->exampleData(self::EXAMPLE),
            options: ['normalizer' => ['normalize' => false]],
        );

        $this->manager->normalize($bag, all: true);

        $this->assertNull($bag->getNormalizedData());
        $this->assertNull($bag->getXmlDocument());
        $this->assertNull($bag->getDocument());
    }

    public function testValidationCanBeSkippedAndItIsDoneByDefault(): void
    {
        $data = $this->modifiedExample(self::EXAMPLE, ['Encabezado.Receptor.RUTRecep' => '60803000-1']);

        $withoutValidation = new DocumentBag(
            parsedData: $data,
            options: ['validator' => ['validate' => false]],
        );
        $this->manager->normalize($withoutValidation, all: true);
        $this->assertSame('60803000-1', $withoutValidation->requireDocument()->getRutReceptor());

        $this->expectException(TranslatableUnexpectedValueException::class);
        $this->expectExceptionMessage('60.803.000-1');
        $this->manager->normalize(new DocumentBag(parsedData: $data), all: true);
    }

    public function testEnsureTipoDocumentoKeepsTheOneAlreadyInTheBag(): void
    {
        $tipoExenta = $this->manager
            ->create($this->exampleData('034_factura_exenta/034_001_simple'), normalizeAll: false)
            ->requireTipoDocumento()
        ;
        $bag = new DocumentBag(
            parsedData: $this->exampleData(self::EXAMPLE),
            documentType: $tipoExenta,
        );

        $this->callProtected('ensureTipoDocumento', $bag);

        $this->assertSame($tipoExenta, $bag->getTipoDocumento());
        $this->assertSame(34, $bag->requireTipoDocumento()->getCodigo());
    }

    public function testEnsureEmisorAndReceptorFromTheXmlDocument(): void
    {
        $document = $this->buildDocument(self::EXAMPLE);
        $bag = new DocumentBag(xmlDocument: $document->getXmlDocument());

        $this->callProtected('ensureEmisor', $bag);
        $this->callProtected('ensureReceptor', $bag);

        $this->assertSame('76192083-9', $bag->requireEmisor()->getRut());
        $this->assertSame('60803000-K', $bag->getReceptor()?->getRut());
    }

    public function testEnsureEmisorAndReceptorFromTheNormalizedData(): void
    {
        $document = $this->buildDocument(self::EXAMPLE);
        $bag = new DocumentBag(normalizedData: $document->getData());

        $this->callProtected('ensureEmisor', $bag);
        $this->callProtected('ensureReceptor', $bag);

        $this->assertSame('76192083-9', $bag->requireEmisor()->getRut());
        $this->assertSame('60803000-K', $bag->getReceptor()?->getRut());
    }

    public function testEnsureEmisorAndReceptorFromTheParsedData(): void
    {
        $bag = new DocumentBag(parsedData: $this->exampleData(self::EXAMPLE));

        $this->callProtected('ensureEmisor', $bag);
        $this->callProtected('ensureReceptor', $bag);

        $this->assertSame('76192083-9', $bag->requireEmisor()->getRut());
        $this->assertSame('60803000-K', $bag->getReceptor()?->getRut());
    }

    public function testEnsureEmisorAndReceptorKeepTheOnesAlreadyInTheBag(): void
    {
        $emisor = new Emisor('11111111-1');
        $receptor = new Receptor('22222222-2');
        $bag = new DocumentBag(
            parsedData: $this->exampleData(self::EXAMPLE),
            emisor: $emisor,
            receptor: $receptor,
        );

        $this->callProtected('ensureEmisor', $bag);
        $this->callProtected('ensureReceptor', $bag);

        $this->assertSame($emisor, $bag->getEmisor());
        $this->assertSame($receptor, $bag->getReceptor());
    }

    public function testEnsureEmisorAndReceptorWithoutAnySourceDoNothing(): void
    {
        $bag = new DocumentBag();

        $this->callProtected('ensureEmisor', $bag);
        $this->callProtected('ensureReceptor', $bag);

        $this->assertNull($bag->getEmisor());
        $this->assertNull($bag->getReceptor());
    }

    public function testEnsureNormalizedDataFromTheDocument(): void
    {
        $document = $this->buildDocument(self::EXAMPLE);
        $bag = new DocumentBag(document: $document);

        $this->callProtected('ensureNormalizedData', $bag);
        $this->callProtected('ensureXmlDocument', $bag);

        $this->assertSame(
            37485,
            $bag->requireNormalizedDataReader()->requireAsInt('Encabezado.Totales.MntTotal')
        );
        $this->assertSame($document->getXmlDocument(), $bag->getXmlDocument());
    }

    public function testEnsureXmlDocumentFromTheNormalizedData(): void
    {
        $document = $this->buildDocument(self::EXAMPLE);
        $bag = $this->manager->create($document->getData(), normalizeAll: false);

        $this->callProtected('ensureXmlDocument', $bag);

        $xml = $bag->requireXmlDocument();
        $this->assertSame('76192083-9', $xml->query('//Encabezado/Emisor/RUTEmisor'));
        $this->assertSame($bag->getId(), $xml->query('//Documento/@ID'));
    }
}
