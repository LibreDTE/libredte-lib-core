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

use Derafu\Certificate\Contract\CertificateFakerInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractRendererStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
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
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataFormatter;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils as NormalizationUtils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaCompraJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExportacionJob;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeLiquidacionFacturaJob;
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
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\LiquidacionFacturaNormalizerStrategy;
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
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\LiquidacionFacturaSanitizerStrategy;
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
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\LiquidacionFacturaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\GuiaDespachoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;

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
#[CoversClass(CafFaker::class)]
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
#[CoversClass(AduanaPais::class)]
#[CoversClass(AduanaTransporte::class)]
#[CoversClass(FormaPago::class)]
#[CoversClass(Traslado::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(TemplateDataFormatter::class)]
#[CoversClass(CategoriaDocumento::class)]
#[CoversClass(OperacionDocumento::class)]
class EmitirIndividualmenteDocumentosOkTest extends TestCase
{
    private BuilderWorkerInterface $builder;

    private CafFakerWorkerInterface $cafFaker;

    private CertificateFakerInterface $certificateFaker;

    private ValidatorWorkerInterface $validator;

    private RendererWorkerInterface $renderer;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->builder = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getBuilderWorker()
        ;

        $this->cafFaker = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafFakerWorker()
        ;

        $this->certificateFaker = new CertificateFaker(new CertificateLoader());

        $this->validator = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getValidatorWorker()
        ;

        $this->renderer = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getRendererWorker()
        ;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideDocumentosOk(): array
    {
        // Find files with test cases.
        $filesPath = self::getFixturesPath() . '/yaml/documentos_ok/*/*.yaml';
        $files = glob($filesPath);

        // Build test data.
        $documentosOk = [];
        foreach ($files as $file) {
            $documentosOk[basename($file)] = [$file];
        }

        // Return data for tests.
        return $documentosOk;
    }

    #[DataProvider('provideDocumentosOk')]
    public function testEmisionCompletaEjemplosDocumentosOk(string $file): void
    {
        // Load test case data.
        $yaml = file_get_contents($file);
        $data = Yaml::parse($yaml);
        $test = $data['Test'];
        unset($data['Test']);
        $test['caso'] = basename($file);

        // Instantiate the document issuer.
        $emisor = new Emisor(
            $data['Encabezado']['Emisor']['RUTEmisor'],
            $data['Encabezado']['Emisor']['RznSoc']
                ?? $data['Encabezado']['Emisor']['RznSocEmisor']
        );

        // Create test CAF for the case.
        $caf = $this->cafFaker->create(
            $emisor,
            $data['Encabezado']['IdDoc']['TipoDTE'],
            $data['Encabezado']['IdDoc']['Folio']
        );

        // Create test certificate for the case.
        $certificate = $this->certificateFaker->createFake(id: $emisor->getRUT());

        // Build the document from YAML file data, CAF, and
        // digital certificate (full DTE build, stamping, and
        // signing).
        $bag = new DocumentBag(
            parsedData: $data,
            caf: $caf,
            certificate: $certificate
        );
        $this->builder->build($bag);

        // Assert normalized data was created.
        $normalizedData = $bag->getParsedData();
        $this->assertIsArray($normalizedData);
        $this->assertNotEmpty($normalizedData);

        // Assert a DTE was created.
        $document = $bag->getDocument();
        $this->assertInstanceOf(DocumentInterface::class, $document);

        // Assert expected DTE values match actual values.
        // getData() is used instead of $normalizedData specifically to
        // confirm that building the XML did not
        // alter any data (especially cases with tags whose values are 0 or
        // codificados).
        $actualValues = $document->getData();
        $this->validateExpectedValues(
            $test['ExpectedValues'],
            $actualValues,
            $test['caso']
        );

        // Get the DTE XML.
        $xml = $bag->getDocument()->saveXml();
        $needle = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $this->assertStringContainsString($needle, $xml);
        file_put_contents($file . '.xml', $xml);

        // Validate generated XML schema and signature.
        $this->validator->validateSchema($xml);
        $result = $this->validator->validateSignature($xml);
        $this->assertTrue($result->isValid());

        // Render the document to confirm outputs can be built with
        // the standard strategy in HTML.
        $bag->getOptions()->set('renderer.format', 'html');
        $html = (string) $this->renderer->render($bag);
        $this->assertNotEmpty($html);
        file_put_contents($file . '.html', $html);

        // Render the document to confirm outputs can be built with
        // the standard strategy in PDF.
        $bag->getOptions()->set('renderer.format', 'pdf');
        $pdf = (string) $this->renderer->render($bag);
        $this->assertNotEmpty($pdf);
        file_put_contents($file . '.pdf', $pdf);
    }

    /**
     * Recursively validates expected values against the actual data array returned.
     * @param array<array-key, mixed> $expectedValues
     * @param array<array-key, mixed> $actualValues
     */
    private function validateExpectedValues(
        array $expectedValues,
        array $actualValues,
        string $caso,
        string $parentKey = ''
    ): void {
        foreach ($expectedValues as $key => $expectedValue) {
            // Build the full key name for
            // error messages.
            $fullKey = $parentKey ? $parentKey . '.' . $key : $key;

            // Assert the index is present in actual data.
            $this->assertArrayHasKey($key, $actualValues, sprintf(
                'En el caso %s no existe el campo %s.',
                $caso,
                $fullKey
            ));

            // If the expected value is an array, call recursively.
            if (is_array($expectedValue)) {
                $this->validateExpectedValues(
                    $expectedValue,
                    $actualValues[$key],
                    $caso,
                    $fullKey
                );
            }

            // If the expected value is not an array, compare directly.
            else {
                // If the current value is a float, check whether to
                // convert to integer to use assertSame() with the correct data
                // type so the test does not fail comparing int to float.
                if (
                    is_float($actualValues[$key])
                    && floor($actualValues[$key]) == $actualValues[$key]
                ) {
                    $actualValues[$key] = (int) $actualValues[$key];
                }

                // Validate the value.
                $this->assertSame(
                    $expectedValue,
                    $actualValues[$key],
                    sprintf(
                        'En el caso %s el valor %s para %s no cuadra con el valor esperado %s.',
                        $caso,
                        $actualValues[$key],
                        $fullKey,
                        $expectedValue
                    )
                );
            }
        }
    }
}
