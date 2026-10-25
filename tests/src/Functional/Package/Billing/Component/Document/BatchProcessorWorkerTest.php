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

use Derafu\Array\ArrayPath;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Xml\Exception\XmlException;
use Exception;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractRendererStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSpreadsheetBatchProcessorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaModalidadVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaMoneda;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\OperacionDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\NormalizerException;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataFormatter;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBatch;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessor\Strategy\Spreadsheet\CsvBatchProcessorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessorWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaCompraJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExentaJob;
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
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\GuiaDespachoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\Service\CafManager;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
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
use libredte\lib\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(AduanaMoneda::class)]
#[CoversClass(DocumentBatch::class)]
#[CoversClass(BatchProcessorException::class)]
#[CoversClass(NormalizerException::class)]
#[CoversClass(BatchProcessorWorker::class)]
#[CoversClass(AbstractSpreadsheetBatchProcessorStrategy::class)]
#[CoversClass(CsvBatchProcessorStrategy::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(ImpuestoAdicionalRetencion::class)]
#[CoversClass(FormaPago::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(ImpuestoAdicionalRetencionRepository::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(Utils::class)]
#[CoversClass(NormalizeBoletaAfectaJob::class)]
#[CoversClass(NormalizeBoletaExentaJob::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(NormalizeFacturaCompraJob::class)]
#[CoversClass(NormalizeFacturaExentaJob::class)]
#[CoversClass(NormalizeFacturaExportacionJob::class)]
#[CoversClass(NormalizeGuiaDespachoJob::class)]
#[CoversClass(NormalizeNotaCreditoJob::class)]
#[CoversClass(NormalizeNotaCreditoExportacionJob::class)]
#[CoversClass(NormalizeNotaDebitoJob::class)]
#[CoversClass(NormalizeNotaDebitoExportacionJob::class)]
#[CoversClass(BoletaAfectaNormalizerStrategy::class)]
#[CoversClass(BoletaExentaNormalizerStrategy::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(FacturaCompraNormalizerStrategy::class)]
#[CoversClass(FacturaExentaNormalizerStrategy::class)]
#[CoversClass(FacturaExportacionNormalizerStrategy::class)]
#[CoversClass(GuiaDespachoNormalizerStrategy::class)]
#[CoversClass(NotaCreditoNormalizerStrategy::class)]
#[CoversClass(NotaCreditoExportacionNormalizerStrategy::class)]
#[CoversClass(NotaDebitoNormalizerStrategy::class)]
#[CoversClass(NotaDebitoExportacionNormalizerStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(BoletaAfectaSanitizerStrategy::class)]
#[CoversClass(BoletaExentaSanitizerStrategy::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(FacturaCompraSanitizerStrategy::class)]
#[CoversClass(FacturaExentaSanitizerStrategy::class)]
#[CoversClass(FacturaExportacionSanitizerStrategy::class)]
#[CoversClass(GuiaDespachoSanitizerStrategy::class)]
#[CoversClass(NotaCreditoSanitizerStrategy::class)]
#[CoversClass(NotaCreditoExportacionSanitizerStrategy::class)]
#[CoversClass(NotaDebitoSanitizerStrategy::class)]
#[CoversClass(NotaDebitoExportacionSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(BoletaAfectaValidatorStrategy::class)]
#[CoversClass(BoletaExentaValidatorStrategy::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(FacturaCompraValidatorStrategy::class)]
#[CoversClass(FacturaExentaValidatorStrategy::class)]
#[CoversClass(FacturaExportacionValidatorStrategy::class)]
#[CoversClass(GuiaDespachoValidatorStrategy::class)]
#[CoversClass(NotaCreditoValidatorStrategy::class)]
#[CoversClass(NotaCreditoExportacionValidatorStrategy::class)]
#[CoversClass(NotaDebitoValidatorStrategy::class)]
#[CoversClass(NotaDebitoExportacionValidatorStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(Caf::class)]
#[CoversClass(CafManager::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractRendererStrategy::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(RendererWorker::class)]
#[CoversClass(RenderResult::class)]
#[CoversClass(RenderedDocument::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(TemplateDataFormatter::class)]
#[CoversClass(CategoriaDocumento::class)]
#[CoversClass(OperacionDocumento::class)]
#[CoversClass(DocumentException::class)]
#[CoversClass(AduanaModalidadVenta::class)]
class BatchProcessorWorkerTest extends TestCase
{
    public function testLoadDocumentsFromCsvFile(): void
    {
        $app = Application::getInstance();

        $renderer = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getRendererWorker()
        ;

        $validator = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getValidatorWorker()
        ;

        $file = self::getFixturesPath('emision_masiva/emision_masiva.csv');
        $expected = Yaml::parseFile(
            self::getFixturesPath('emision_masiva/emision_masiva.yaml')
        );

        $documentsBag = $this->processFile($file);

        $documentCount = count($documentsBag);
        $this->assertSame($expected['cantidad'], $documentCount);

        for ($i = 0; $i < $documentCount; $i++) {
            $documentBag = $documentsBag[$i];
            $expectedData = $expected['documentos'][$i] ?? null;
            if ($expectedData === null) {
                throw new LogicException(sprintf(
                    'Expected values for document %d in file %s are not defined',
                    $i + 1,
                    $file
                ));
            }

            // Generate document XML.
            $xml = $documentBag->getXmlDocument()->setEncoding('ISO-8859-1')->saveXml();
            $this->assertNotEmpty($xml);
            file_put_contents(
                $file . '_' . $documentBag->getId() . '.xml',
                $xml
            );

            // Validate document schema.
            try {
                $validator->validateSchema($xml);
            } catch (Exception $e) {
                throw new XmlException(sprintf(
                    'XML validation for document %d (%s) failed: %s',
                    $i + 1,
                    $documentBag->getId(),
                    $e->getMessage()
                ));
            }

            // Validate document signature.
            try {
                $result = $validator->validateSignature($xml);
                if (!$result->isValid()) {
                    throw $result->getError();
                }
            } catch (Exception $e) {
                throw new SignatureException(sprintf(
                    'Signature validation for document %d (%s) failed: %s',
                    $i + 1,
                    $documentBag->getId(),
                    $e->getMessage()
                ));
            }

            // Generate the document PDF.
            $pdf = (string) $renderer->render($documentBag);
            $this->assertNotEmpty($pdf);
            file_put_contents(
                $file . '_' . $documentBag->getId() . '.pdf',
                $pdf
            );

            // Validate expected document fields.
            $actualData = ArrayPath::flatten(
                $documentBag->getDocument()->getData()
            );
            foreach ($expectedData as $expectedKey => $expectedValue) {
                $actualValue = $actualData[$expectedKey] ?? null;
                $this->assertSame(
                    $expectedValue,
                    $actualValue,
                    sprintf(
                        'Field %s (%s) does not match expected %s in document %d (%s).',
                        $expectedKey,
                        $actualValue,
                        $expectedValue,
                        $i + 1,
                        $documentBag->getId()
                    )
                );
            }
        }
    }

    /**
     * @return array<string, array{string, mixed, mixed}>
     */
    public static function provideErrorFiles(): array
    {
        $dir = self::getFixturesPath('emision_masiva/errores');
        $cases = Yaml::parseFile($dir . '/errores.yaml');

        $files = [];
        foreach ($cases as $fileName => $caseDefinition) {
            $files[$fileName] = [
                $dir . '/' . $fileName,
                $caseDefinition['exception'],
                $caseDefinition['message'],
            ];
        }

        return $files;
    }

    #[DataProvider('provideErrorFiles')]
    public function testFileWithErrorsThrowsException(
        string $file,
        string $exception,
        string $message
    ): void {
        $this->expectException(
            'libredte\lib\Core\Package\Billing\Component\Document\Exception\\'
            . $exception
        );
        $this->expectExceptionMessage($message);

        $this->processFile($file);
    }

    public function testFileWithNoDocumentsReturnsEmptyArray(): void
    {
        $file = self::getFixturesPath('emision_masiva/sin_documentos.csv');

        $this->assertSame([], $this->processFile($file));
    }

    public function testPdfNameIsTruncatedTo100Characters(): void
    {
        $file = self::getFixturesPath('emision_masiva/nombre_pdf_largo.csv');

        $documentsBag = $this->processFile($file);

        $this->assertCount(1, $documentsBag);
        $pdfFileName = $documentsBag[0]->getParsedData()['LibreDTE']['pdf']['nombre'];
        $this->assertSame(100, mb_strlen($pdfFileName));
    }

    public function testIso88591FileIsConvertedToUtf8(): void
    {
        $file = self::getFixturesPath('emision_masiva/iso_8859_1.csv');

        $documentsBag = $this->processFile($file);

        $this->assertCount(1, $documentsBag);
        $parsed = $documentsBag[0]->getParsedData();
        $this->assertSame(
            'Peña Ñandú Ltda',
            $parsed['Encabezado']['Receptor']['RznSocRecep']
        );
        $this->assertSame(
            'Asesoría y Diseño',
            $parsed['Encabezado']['Receptor']['GiroRecep']
        );
        $this->assertSame('Café', $parsed['Detalle'][0]['NmbItem']);
        $this->assertSame(
            'Descripción: á é í ó ú ñ',
            $parsed['Detalle'][0]['DscItem']
        );
    }

    public function testBatchFromContentReturnsSameDocuments(): void
    {
        $file = self::getFixturesPath('emision_masiva/nombre_pdf_largo.csv');
        $batch = new DocumentBatch(inputData: file_get_contents($file));

        $documentsBag = $this->processBatch($batch);

        $this->assertCount(1, $documentsBag);
        $this->assertNull($batch->getInputFile());
    }

    /**
     * Processes a bulk issuance file with an issuer and test
     * certificate.
     *
     * @return array<array-key, DocumentBagInterface>
     */
    private function processFile(string $file): array
    {
        return $this->processBatch(new DocumentBatch(inputFile: $file));
    }

    /**
     * Processes a bulk issuance batch with an issuer and test
     * certificate.
     *
     * @return array<array-key, DocumentBagInterface>
     */
    private function processBatch(DocumentBatch $batch): array
    {
        $emisor = new Emisor(
            rut: 76192083,
            razon_social: 'SASCO SpA',
            giro: 'Tecnología, Informática y Telecomunicaciones',
            actividad_economica: 726000,
            direccion: 'DBG',
            comuna: 'Santa Cruz'
        );

        // Generate a fake certificate.
        $certificateFaker = new CertificateFaker(new CertificateLoader());
        $certificate = $certificateFaker->createFake(id: $emisor->getRUT());

        $batch->setEmisor($emisor);
        $batch->setCertificate($certificate);

        return Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getBatchProcessorWorker()
            ->process($batch)
            ->getDocumentBags()
        ;
    }
}
