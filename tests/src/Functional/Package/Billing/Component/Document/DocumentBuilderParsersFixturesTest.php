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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Document;

use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\FakerWorkerInterface as CertificateFakerWorkerInterface;
use Derafu\Lib\Core\Support\Store\DataContainer;
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
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Traslado;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Factory\TipoDocumentoFactory;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataHandler;
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
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\XmlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\YamlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Form\EstandarParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
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
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafProviderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Service\FakeCafProvider;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafProviderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Prueba del parser de datos de entrada para un documento tributario.
 */
#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractRendererStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoDocumentoFactory::class)]
#[CoversClass(TemplateDataHandler::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(BoletaAfectaNormalizerStrategy::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(XmlParserStrategy::class)]
#[CoversClass(YamlParserStrategy::class)]
#[CoversClass(EstandarParserStrategy::class)]
#[CoversClass(RendererWorker::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(BoletaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(BoletaAfectaValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafBag::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(NormalizeBoletaAfectaJob::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(FakeCafProvider::class)]
#[CoversClass(CafProviderWorker::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(ImpuestoAdicionalRetencion::class)]
#[CoversClass(ImpuestoAdicionalRetencionRepository::class)]
#[CoversClass(FormaPago::class)]
#[CoversClass(NormalizeFacturaExentaJob::class)]
#[CoversClass(FacturaExentaNormalizerStrategy::class)]
#[CoversClass(FacturaExentaSanitizerStrategy::class)]
#[CoversClass(FacturaExentaValidatorStrategy::class)]
#[CoversClass(NormalizeBoletaExentaJob::class)]
#[CoversClass(BoletaExentaNormalizerStrategy::class)]
#[CoversClass(BoletaExentaSanitizerStrategy::class)]
#[CoversClass(BoletaExentaValidatorStrategy::class)]
#[CoversClass(NormalizeFacturaCompraJob::class)]
#[CoversClass(FacturaCompraNormalizerStrategy::class)]
#[CoversClass(FacturaCompraSanitizerStrategy::class)]
#[CoversClass(FacturaCompraValidatorStrategy::class)]
#[CoversClass(Traslado::class)]
#[CoversClass(NormalizeGuiaDespachoJob::class)]
#[CoversClass(GuiaDespachoNormalizerStrategy::class)]
#[CoversClass(GuiaDespachoSanitizerStrategy::class)]
#[CoversClass(GuiaDespachoValidatorStrategy::class)]
#[CoversClass(NormalizeNotaDebitoJob::class)]
#[CoversClass(NotaDebitoNormalizerStrategy::class)]
#[CoversClass(NotaDebitoSanitizerStrategy::class)]
#[CoversClass(NotaDebitoValidatorStrategy::class)]
#[CoversClass(NormalizeNotaCreditoJob::class)]
#[CoversClass(NotaCreditoNormalizerStrategy::class)]
#[CoversClass(NotaCreditoSanitizerStrategy::class)]
#[CoversClass(NotaCreditoValidatorStrategy::class)]
#[CoversClass(AduanaPais::class)]
#[CoversClass(NormalizeFacturaExportacionJob::class)]
#[CoversClass(FacturaExportacionNormalizerStrategy::class)]
#[CoversClass(FacturaExportacionSanitizerStrategy::class)]
#[CoversClass(FacturaExportacionValidatorStrategy::class)]
#[CoversClass(NormalizeNotaDebitoExportacionJob::class)]
#[CoversClass(NotaDebitoExportacionNormalizerStrategy::class)]
#[CoversClass(NotaDebitoExportacionSanitizerStrategy::class)]
#[CoversClass(NotaDebitoExportacionValidatorStrategy::class)]
#[CoversClass(NormalizeNotaCreditoExportacionJob::class)]
#[CoversClass(NotaCreditoExportacionNormalizerStrategy::class)]
#[CoversClass(NotaCreditoExportacionSanitizerStrategy::class)]
#[CoversClass(NotaCreditoExportacionValidatorStrategy::class)]
class DocumentBuilderParsersFixturesTest extends TestCase
{
    private BuilderWorkerInterface $builder;

    private CafProviderWorkerInterface $cafProvider;

    private CertificateFakerWorkerInterface $certificateFaker;

    private ValidatorWorkerInterface $validator;

    private RendererWorkerInterface $renderer;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->builder = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getBuilderWorker()
        ;

        $this->cafProvider = $app
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafProviderWorker()
        ;

        $this->certificateFaker = $app
            ->getPrimePackage()
            ->getCertificateComponent()
            ->getFakerWorker()
        ;

        $this->validator = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getValidatorWorker()
        ;

        $this->renderer = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getRendererWorker()
        ;
    }

    /**
     * Entrega los archivos con los casos para el test de los parsers.
     *
     * @return array
     */
    public static function provideInputFiles(): array
    {
        $parsersDir = static::getFixturesPath('parsers');
        $inputFiles = [];
        foreach (scandir($parsersDir) as $parser) {
            if ($parser === '.' || $parser === '..') {
                continue;
            }
            $parserDir = $parsersDir . '/' . $parser;
            foreach (scandir($parserDir) as $format) {
                if ($format === '.' || $format === '..') {
                    continue;
                }
                $formatDir = $parserDir . '/' . $format;
                foreach (scandir($formatDir) as $inputFile) {
                    // Revisar si se debe agregar el archivo.
                    $addFile = true;
                    if ($inputFile === '.' || $inputFile === '..') {
                        $addFile = false;
                    }
                    foreach (['.json.xml', '.xml.xml', '.yaml.xml', '.pdf'] as $ext) {
                        if (str_ends_with($inputFile, $ext)) {
                            $addFile = false;
                            break;
                        }
                    }
                    if (!$addFile) {
                        continue;
                    }

                    // Agregar el archivo.
                    $inputFilePath = $formatDir . '/' . $inputFile;
                    $id = sprintf(
                        '%s.%s:%s',
                        $parser,
                        $format,
                        basename($inputFilePath)
                    );
                    $inputFiles[$id] = [
                        $parser . '.' . $format,
                        $inputFilePath,
                    ];
                }
            }
        }

        return $inputFiles;
    }

    #[DataProvider('provideInputFiles')]
    public function testDocumentoParser(string $format, string $file): void
    {
        // Construir el documento con los datos del archivo y la estrategia de
        // parseo según el formato.
        $data = file_get_contents($file);
        $bag = new DocumentBag(
            inputData: $data,
            options: new DataContainer([
                'parser' => [
                    'strategy' => $format,
                ],
                'renderer' => [
                    'format' => 'pdf',
                ],
            ])
        );
        $this->builder->build($bag);

        // Validar que se haya logrado crear un arreglo con los datos parseados.
        $parsedData = $bag->getParsedData();
        $this->assertIsArray($parsedData);
        $this->assertNotEmpty($parsedData);

        // Validar el documento en base a los datos parseados.
        $document = $bag->getDocument();
        $this->assertInstanceOf(DocumentInterface::class, $document);

        // Crear CAF de pruebas y armar una nueva bolsa que incluya el CAF.
        // Al usar build() se timbrará el documento previamente normalizado.
        $cafBag = $this->cafProvider->retrieve(
            $bag->getEmisor(),
            $bag->getTipoDocumento(),
            $bag->getFolio()
        );
        $caf = $cafBag->getCaf();
        $bag = $bag->withCaf($caf); // withCaf() retorna una nueva DocumentBag.
        $bag->setFolio($bag->getFolio() ?? $cafBag->getSiguienteFolio());
        $this->builder->build($bag);

        // Crear certificado de pruebas y armar una nueva bolsa que incluya el
        // certificado.
        // Al usar build() se firmará el documento previamente timbrado.
        $certificate = $this->certificateFaker->create();
        $bag = $bag->withCertificate($certificate); // withCertificate() retorna una nueva DocumentBag.
        $this->builder->build($bag);

        // Guardar el XML.
        $xml = $bag->getDocument()->saveXml();
        file_put_contents($file . '.xml', $xml);

        // Validar esquema del XML generado y la firma.
        $this->validator->validateSchema($xml);
        $this->validator->validateSignature($xml);

        // Renderizar el documento para corroborar que se puedan construir con
        // la estrategia estándar.
        $pdf = $this->renderer->render($bag);
        $this->assertNotEmpty($pdf);
        $this->assertIsString($pdf);
        file_put_contents($file . '.pdf', $pdf);
    }
}
