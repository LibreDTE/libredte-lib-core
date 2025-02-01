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
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
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
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeLiquidacionFacturaJob;
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
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\LiquidacionFacturaNormalizerStrategy;
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
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\LiquidacionFacturaSanitizerStrategy;
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
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\LiquidacionFacturaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExentaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\GuiaDespachoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
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
use Symfony\Component\Yaml\Yaml;

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
#[CoversClass(CafBag::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(TemplateDataHandler::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(AduanaPais::class)]
#[CoversClass(AduanaTransporte::class)]
#[CoversClass(FormaPago::class)]
#[CoversClass(Traslado::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
class EmitirIndividualmenteDocumentosOkTest extends TestCase
{
    private BuilderWorkerInterface $builder;

    private CafFakerWorkerInterface $cafFaker;

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

        $this->cafFaker = $app
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafFakerWorker()
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

    public static function provideDocumentosOk(): array
    {
        // Buscar los archivos con los casos para el test.
        $filesPath = self::getFixturesPath() . '/yaml/documentos_ok/*/*.yaml';
        $files = glob($filesPath);

        // Armar datos de prueba.
        $documentosOk = [];
        foreach ($files as $file) {
            $documentosOk[basename($file)] = [$file];
        }

        // Entregar los datos para las pruebas.
        return $documentosOk;
    }

    #[DataProvider('provideDocumentosOk')]
    public function testEmisionCompletaEjemplosDocumentosOk(string $file): void
    {
        // Cargar datos del caso de prueba.
        $yaml = file_get_contents($file);
        $data = Yaml::parse($yaml);
        $test = $data['Test'];
        unset($data['Test']);
        $test['caso'] = basename($file);

        // Instanciar al emisor del documento.
        $emisor = new Emisor(
            $data['Encabezado']['Emisor']['RUTEmisor'],
            $data['Encabezado']['Emisor']['RznSoc']
                ?? $data['Encabezado']['Emisor']['RznSocEmisor']
        );

        // Crear CAF de pruebas para el caso.
        $cafBag = $this->cafFaker->create(
            $emisor,
            $data['Encabezado']['IdDoc']['TipoDTE'],
            $data['Encabezado']['IdDoc']['Folio']
        );
        $caf = $cafBag->getCaf();

        // Crear certificado de pruebas para el caso.
        $certificate = $this->certificateFaker->create();

        // Construir el documento con los datos del archivo YAML, el CAF y el
        // certificado digital (construcción de DTE completa, timbrado y
        // firmado).
        $bag = new DocumentBag(
            parsedData: $data,
            caf: $caf,
            certificate: $certificate
        );
        $this->builder->build($bag);

        // Validar que se haya logrado crear los datos normalizados.
        $normalizedData = $bag->getParsedData();
        $this->assertIsArray($normalizedData);
        $this->assertNotEmpty($normalizedData);

        // Validar que se haya creado un DTE.
        $document = $bag->getDocument();
        $this->assertInstanceOf(DocumentInterface::class, $document);

        // Validar los valores esperados del DTE con los reales obtenidos.
        // Se usa getDatos() en vez de $normalizedData justamente para
        // corroborar que al pasar por la construcción del XML no se haya
        // alterado ningún dato (sobre todo en casos con tags con valores 0 o
        // codificados).
        $actualValues = $document->getDatos();
        $this->validateExpectedValues(
            $test['ExpectedValues'],
            $actualValues,
            $test['caso']
        );

        // Obtener el XML del DTE.
        $xml = $bag->getDocument()->saveXml();
        $needle = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $this->assertStringContainsString($needle, $xml);
        file_put_contents($file . '.xml', $xml);

        // Validar esquema del XML generado y la firma.
        $this->validator->validateSchema($xml);
        $this->validator->validateSignature($xml);

        // Renderizar el documento para corroborar que se puedan construir con
        // la estrategia estándar en HTML.
        $bag->getOptions()->set('renderer.format', 'html');
        $html = $this->renderer->render($bag);
        $this->assertNotEmpty($html);
        $this->assertIsString($html);
        file_put_contents($file . '.html', $html);

        // Renderizar el documento para corroborar que se puedan construir con
        // la estrategia estándar en PDF.
        $bag->getOptions()->set('renderer.format', 'pdf');
        $pdf = $this->renderer->render($bag);
        $this->assertNotEmpty($pdf);
        $this->assertIsString($pdf);
        file_put_contents($file . '.pdf', $pdf);
    }

    /**
     * Valida recursivamente los valores esperados en el arreglo de los datos
     * reales entregados.
     */
    private function validateExpectedValues(
        array $expectedValues,
        array $actualValues,
        string $caso,
        string $parentKey = ''
    ): void {
        foreach ($expectedValues as $key => $expectedValue) {
            // Construir el nombre completo de la clave para los mensajes de
            // error.
            $fullKey = $parentKey ? $parentKey . '.' . $key : $key;

            // Corroborar que el índice esté presente en los datos reales.
            $this->assertArrayHasKey($key, $actualValues, sprintf(
                'En el caso %s no existe el campo %s.',
                $caso,
                $fullKey
            ));

            // Si el valor esperado es un arreglo, llamar recursivamente.
            if (is_array($expectedValue)) {
                $this->validateExpectedValues(
                    $expectedValue,
                    $actualValues[$key],
                    $caso,
                    $fullKey
                );
            }

            // Si el valor esperado no es un arreglo, se compara directamente.
            else {
                // Si el valor actual es un flotante, se revisa si se debe
                // convertir a un entero para usar assertSame() con el tipo de
                // datos correcto y que no falle porque se compara entero con
                // float.
                if (
                    is_float($actualValues[$key])
                    && floor($actualValues[$key]) == $actualValues[$key]
                ) {
                    $actualValues[$key] = (int) $actualValues[$key];
                }

                // Realizar validación del valor.
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
