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

use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Enum\Currency;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Support\Factory;
use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaMoneda;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BuilderException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\SanitizerException;
use libredte\lib\Core\Package\Billing\Component\Document\Factory\TipoDocumentoFactory;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBatch;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ExamplesWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExportacionJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeGuiaDespachoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoExportacionJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaDebitoExportacionJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
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
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;
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
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(ExamplesWorker::class)]
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
#[CoversClass(Application::class)]
#[CoversClass(AduanaMoneda::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(TipoSobre::class)]
#[CoversClass(BatchProcessorException::class)]
#[CoversClass(DocumentException::class)]
#[CoversClass(TipoDocumentoFactory::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(DocumentBatch::class)]
#[CoversClass(NormalizeBoletaAfectaJob::class)]
#[CoversClass(NormalizeBoletaExentaJob::class)]
#[CoversClass(NormalizeFacturaExentaJob::class)]
#[CoversClass(NormalizeFacturaExportacionJob::class)]
#[CoversClass(NormalizeGuiaDespachoJob::class)]
#[CoversClass(NormalizeNotaCreditoExportacionJob::class)]
#[CoversClass(NormalizeNotaCreditoJob::class)]
#[CoversClass(NormalizeNotaDebitoExportacionJob::class)]
#[CoversClass(SendAecResponse::class)]
class DocumentSmallGapsTest extends TestCase
{
    use BuildsDocumentsTrait;

    private const FACTURA = '033_factura_afecta/033_001_simple';

    public function testNormalizerJobsAreBuiltFromTheRepositoryManager(): void
    {
        $manager = Application::getInstance()->getService(RepositoryManagerInterface::class);
        $this->assertInstanceOf(RepositoryManagerInterface::class, $manager);

        $jobs = [
            new NormalizeBoletaAfectaJob($manager),
            new NormalizeBoletaExentaJob($manager),
            new NormalizeFacturaExentaJob($manager),
            new NormalizeFacturaExportacionJob($manager),
            new NormalizeGuiaDespachoJob($manager),
            new NormalizeNotaCreditoExportacionJob($manager),
            new NormalizeNotaCreditoJob($manager),
            new NormalizeNotaDebitoExportacionJob($manager),
        ];

        $this->assertCount(8, $jobs);
    }

    public function testEveryXmlTagHasADescription(): void
    {
        foreach (TagXmlDocumento::cases() as $tag) {
            $this->assertNotSame('', $tag->getDescripcion(), $tag->name);
        }
    }

    public function testOnlyTheReceiptEnvelopeIsAnEnvelopeOfReceipts(): void
    {
        $this->assertTrue(TipoSobre::ENVIO_BOLETA->areBoletas());
        $this->assertFalse(TipoSobre::ENVIO_DTE->areBoletas());
    }

    public function testExceptionsKeepTheBagOrTheBatchTheyWereRaisedFor(): void
    {
        $bag = new DocumentBag(parsedData: $this->exampleData(self::FACTURA));
        $batch = new DocumentBatch(inputData: "a;b\n");

        $this->assertSame($bag, (new DocumentException('falló', documentBag: $bag))->getDocumentBag());
        $this->assertNull((new DocumentException('falló'))->getDocumentBag());
        $this->assertSame($batch, (new BatchProcessorException('falló', documentBatch: $batch))->getDocumentBatch());
        $this->assertNull((new BatchProcessorException('falló'))->getDocumentBatch());
    }

    public function testDocumentTypeFactoryBuildsTheEntityFromItsData(): void
    {
        $tipo = (new TipoDocumentoFactory())->create(['codigo' => 33, 'nombre' => 'Factura electrónica']);

        $this->assertSame(33, $tipo->getCodigo());
        $this->assertSame('Factura electrónica', $tipo->getNombre());
    }

    public function testCustomsCurrencyFallsBackToTheUnknownCurrency(): void
    {
        $known = Factory::create(['codigo_iso' => 'CLP', 'glosa' => 'Peso chileno'], AduanaMoneda::class);
        $unknown = Factory::create(['codigo_iso' => 'ZZZ', 'glosa' => 'Moneda desconocida'], AduanaMoneda::class);

        $this->assertSame(Currency::CLP, $known->getCurrency());
        $this->assertSame(Currency::XXX, $unknown->getCurrency());
    }

    public function testAecUploadResponseWithoutAnInnerElementHasNoTrackId(): void
    {
        $this->assertSame(0, (new SendAecResponse(['RECEPCIONAEC' => 'texto']))->getTrackId());
    }

    public function testSchemaValidationAcceptsTheXmlDocumentOfABuiltDocument(): void
    {
        $bag = $this->buildBag(self::FACTURA);
        $validator = Application::getInstance()->getPackageRegistry()->getBillingPackage()
            ->getDocumentComponent()->getValidatorWorker();

        $xmlDocument = $bag->requireXmlDocument();

        $this->assertSame($xmlDocument, $validator->validateSchema($xmlDocument));
    }

    public function testActivityCodeOfFiveDigitsIsCompletedWithALeadingZero(): void
    {
        $data = $this->modifiedExample(self::FACTURA, ['Encabezado.Emisor.Acteco' => '72600']);

        $bag = $this->buildBag(self::FACTURA, $data);

        $this->assertStringContainsString('<Acteco>072600</Acteco>', $bag->requireXmlDocument()->saveXml());
    }

    public function testStampingWithACafWhoseKeyIsNotValidIsReported(): void
    {
        $registry = Application::getInstance()->getPackageRegistry()->getBillingPackage();
        $data = $this->exampleData(self::FACTURA);
        $caf = $registry->getIdentifierComponent()->getCafFakerWorker()->create(new Emisor('76192083-9', 'SASCO SpA'), 33, 1);
        $brokenXml = preg_replace('#<RSASK>.*?</RSASK>#s', '<RSASK>no es una clave</RSASK>', $caf->getXml());
        $this->assertIsString($brokenXml);
        $this->assertNotSame($caf->getXml(), $brokenXml);

        $bag = new DocumentBag(
            parsedData: $data,
            caf: new Caf($brokenXml),
            certificate: (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9')
        );

        $this->expectException(BuilderException::class);
        $this->expectExceptionMessage('Could not stamp the data.');
        $registry->getDocumentComponent()->getBuilderWorker()->build($bag);
    }

    public function testDocumentNumberCannotBeAssignedAfterTheXmlWasGenerated(): void
    {
        $bag = $this->buildBag(self::FACTURA);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('after the XML document has been generated');
        $bag->setFolio(99);
    }

    public function testDocumentNumberCannotBeAssignedWithoutData(): void
    {
        $bag = new DocumentBag(inputData: $this->exampleData(self::FACTURA));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('no parsed or normalized data');
        $bag->setFolio(99);
    }

    public function testSanitizingWithoutDocumentTypeIsRejected(): void
    {
        $bag = new DocumentBag(inputData: $this->exampleData(self::FACTURA));

        $this->expectException(SanitizerException::class);
        Application::getInstance()->getPackageRegistry()->getBillingPackage()
            ->getDocumentComponent()->getSanitizerWorker()->sanitize($bag);
    }
}
