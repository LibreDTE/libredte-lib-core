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
use libredte\lib\Core\Package\Billing\Component\Document\Entity\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Factory\TipoDocumentoFactory;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\XmlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\YamlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\RendererWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
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
#[CoversClass(DocumentBag::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(XmlParserStrategy::class)]
#[CoversClass(YamlParserStrategy::class)]
#[CoversClass(RendererWorker::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
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
class DocumentBuilderParsersFixturesTest extends TestCase
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
                    if ($inputFile === '.' || $inputFile === '..') {
                        continue;
                    }
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
            options: new DataContainer(['parser' => ['strategy' => $format]])
        );
        $this->builder->build($bag);

        // Validar que se haya logrado crear un arreglo con los datos parseados.
        $parsedData = $bag->getParsedData();
        $this->assertIsArray($parsedData);
        $this->assertNotEmpty($parsedData);

        // Crear un documento en base a los datos parseados.
        $document = $bag->getDocument();
        $this->assertInstanceOf(DocumentInterface::class, $document);

        // Crear CAF de pruebas y armar una nueva bolsa que incluya el CAF.
        // Al usar build() se timbrará el documento previamente normalizado.
        $cafBag = $this->cafFaker->create(
            $bag->getEmisor(),
            $document->getCodigo(),
            $document->getFolio()
        );
        $caf = $cafBag->getCaf();
        $bag = $bag->withCaf($caf); // withCaf() retorna una nueva DocumentBag.
        $this->builder->build($bag);

        // Crear certificado de pruebas y armar una nueva bolsa que incluya el
        // certificado.
        // Al usar build() se firmará el documento previamente timbrado.
        $certificate = $this->certificateFaker->create();
        $bag = $bag->withCertificate($certificate); // withCertificate() retorna una nueva DocumentBag.
        $this->builder->build($bag);

        // Validar esquema del XML generado y la firma.
        $xml = $bag->getDocument()->saveXml();
        $this->validator->validateSchema($xml);
        $this->validator->validateSignature($xml);

        // Renderizar el documento para corroborar que se puedan construir con
        // la estrategia estándar.
        $rendered = $this->renderer->render($bag);
        $this->assertNotEmpty($rendered);
        $this->assertIsString($rendered);
    }
}
