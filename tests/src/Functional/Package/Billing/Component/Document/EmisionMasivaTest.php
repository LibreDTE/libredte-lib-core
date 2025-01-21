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

use Derafu\Lib\Core\Common\Exception\Exception;
use Derafu\Lib\Core\Helper\Arr;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Exception\SignatureException;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Exception\XmlException;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractRendererStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaMoneda;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataHandler;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBatch;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessor\Strategy\Spreadsheet\CsvBatchProcessorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessorWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaCompraJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExportacionJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeGuiaDespachoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\BoletaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaCompraNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaExentaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaExportacionNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\GuiaDespachoNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaCreditoNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\RendererWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\BoletaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaCompraSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaExentaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaExportacionSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\GuiaDespachoSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaCreditoSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\BoletaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaCompraValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExentaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\GuiaDespachoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\Service\FakeCafProvider;
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
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(AduanaMoneda::class)]
#[CoversClass(DocumentBatch::class)]
#[CoversClass(BatchProcessorWorker::class)]
#[CoversClass(CsvBatchProcessorStrategy::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(ImpuestoAdicionalRetencion::class)]
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
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(NormalizeFacturaCompraJob::class)]
#[CoversClass(NormalizeFacturaExentaJob::class)]
#[CoversClass(NormalizeFacturaExportacionJob::class)]
#[CoversClass(NormalizeGuiaDespachoJob::class)]
#[CoversClass(NormalizeNotaCreditoJob::class)]
#[CoversClass(BoletaAfectaNormalizerStrategy::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(FacturaCompraNormalizerStrategy::class)]
#[CoversClass(FacturaExentaNormalizerStrategy::class)]
#[CoversClass(FacturaExportacionNormalizerStrategy::class)]
#[CoversClass(GuiaDespachoNormalizerStrategy::class)]
#[CoversClass(NotaCreditoNormalizerStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(BoletaAfectaSanitizerStrategy::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(FacturaCompraSanitizerStrategy::class)]
#[CoversClass(FacturaExentaSanitizerStrategy::class)]
#[CoversClass(FacturaExportacionSanitizerStrategy::class)]
#[CoversClass(GuiaDespachoSanitizerStrategy::class)]
#[CoversClass(NotaCreditoSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(BoletaAfectaValidatorStrategy::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(FacturaCompraValidatorStrategy::class)]
#[CoversClass(FacturaExentaValidatorStrategy::class)]
#[CoversClass(FacturaExportacionValidatorStrategy::class)]
#[CoversClass(GuiaDespachoValidatorStrategy::class)]
#[CoversClass(NotaCreditoValidatorStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(Caf::class)]
#[CoversClass(FakeCafProvider::class)]
#[CoversClass(CafBag::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractRendererStrategy::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(TemplateDataHandler::class)]
#[CoversClass(RendererWorker::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
class EmisionMasivaTest extends TestCase
{
    public function testCargarDocumentosDesdeArchivoCsv(): void
    {
        $app = Application::getInstance();

        $renderer = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getRendererWorker()
        ;

        $validator = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getValidatorWorker()
        ;

        $file = self::getFixturesPath('emision_masiva/emision_masiva.csv');
        $expected = Yaml::parseFile(
            self::getFixturesPath('emision_masiva/emision_masiva.yaml')
        );

        $emisor = new Emisor(
            rut: 76192083,
            razon_social: 'SASCO SpA',
            giro: 'Tecnología, Informática y Telecomunicaciones',
            actividad_economica: 726000,
            direccion: 'DBG',
            comuna: 'Santa Cruz'
        );

        $certificate = $app
            ->getPrimePackage()
            ->getCertificateComponent()
            ->getFakerWorker()
            ->create()
        ;

        $batch = new DocumentBatch($file);
        $batch->setEmisor($emisor);
        $batch->setCertificate($certificate);

        $documentsBag = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getBatchProcessorWorker()
            ->process($batch)
        ;

        $cantidad = count($documentsBag);
        $this->assertSame($expected['cantidad'], $cantidad);

        for ($i = 0; $i < $cantidad; $i++) {
            $documentBag = $documentsBag[$i];
            $dataExpected = $expected['documentos'][$i] ?? null;
            if ($dataExpected === null) {
                throw new LogicException(sprintf(
                    'Falta definir los valores esperados del documento %d del archivo %s',
                    $i + 1,
                    $file
                ));
            }

            // Generar XML del documento.
            $xml = $documentBag->getXmlDocument()->saveXml();
            $this->assertIsString($xml);
            $this->assertNotEmpty($xml);
            file_put_contents(
                $file . '_' . $documentBag->getId() . '.xml',
                $xml
            );

            // Validar esquema del documento.
            try {
                $validator->validateSchema($xml);
            } catch (Exception $e) {
                throw new XmlException(sprintf(
                    'La validación del XML del documento %d (%s) falló: %s',
                    $i + 1,
                    $documentBag->getId(),
                    $e->getMessage()
                ));
            }

            // Validar firma del documento.
            try {
                $validator->validateSignature($xml);
            } catch (Exception $e) {
                throw new SignatureException(sprintf(
                    'La validación de la firma del documento %d (%s) falló: %s',
                    $i + 1,
                    $documentBag->getId(),
                    $e->getMessage()
                ));
            }

            // Generar el PDF del documento.
            $pdf = $renderer->render($documentBag);
            $this->assertIsString($pdf);
            $this->assertNotEmpty($pdf);
            file_put_contents(
                $file . '_' . $documentBag->getId() . '.pdf',
                $pdf
            );

            // Validar campos esperados del documento.
            $dataActual = Arr::dot($documentBag->getDocument()->getDatos());
            foreach ($dataExpected as $expectedKey => $expectedValue) {
                $actualValue = $dataActual[$expectedKey] ?? null;
                $this->assertSame(
                    $expectedValue,
                    $actualValue,
                    sprintf(
                        'Error al validar que el valor de %s (%s) coincida con el esperado %s en el documento %d (%s).',
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
}
