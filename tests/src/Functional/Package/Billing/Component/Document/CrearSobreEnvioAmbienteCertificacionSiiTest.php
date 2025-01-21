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

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\SobreEnvio;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DispatcherWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils as NormalizationUtils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaCompraJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExentaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaExportacionJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeGuiaDespachoJob;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeLiquidacionFacturaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoExportacionJob;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaDebitoExportacionJob;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaDebitoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\BoletaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\BoletaExentaNormalizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaCompraNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaExentaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaExportacionNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\GuiaDespachoNormalizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\LiquidacionFacturaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaCreditoExportacionNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaDebitoExportacionNormalizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaCreditoNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaDebitoNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\BoletaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\BoletaExentaSanitizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaCompraSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaExentaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaExportacionSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\GuiaDespachoSanitizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\LiquidacionFacturaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaCreditoExportacionSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaDebitoExportacionSanitizerStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaCreditoSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaDebitoSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\BoletaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\BoletaExentaValidatorStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaCompraValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExentaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\GuiaDespachoValidatorStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\LiquidacionFacturaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoExportacionValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoExportacionValidatorStrategy;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
//use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaDebitoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\PersonaNatural;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;
use Throwable;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(SobreEnvio::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoSobre::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(DocumentEnvelope::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DispatcherWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeBoletaAfectaJob::class)]
#[CoversClass(NormalizeBoletaExentaJob::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
//#[CoversClass(NormalizeFacturaAfectaJob::class)]
//#[CoversClass(NormalizeFacturaCompraJob::class)]
#[CoversClass(NormalizeFacturaExentaJob::class)]
#[CoversClass(NormalizeFacturaExportacionJob::class)]
#[CoversClass(NormalizeGuiaDespachoJob::class)]
//#[CoversClass(NormalizeLiquidacionFacturaJob::class)]
#[CoversClass(NormalizeNotaCreditoExportacionJob::class)]
//#[CoversClass(NormalizeNotaCreditoJob::class)]
#[CoversClass(NormalizeNotaDebitoExportacionJob::class)]
//#[CoversClass(NormalizeNotaDebitoJob::class)]
#[CoversClass(NormalizerWorker::class)]
//#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(FacturaExentaNormalizerStrategy::class)]
#[CoversClass(BoletaAfectaNormalizerStrategy::class)]
#[CoversClass(BoletaExentaNormalizerStrategy::class)]
//#[CoversClass(LiquidacionFacturaNormalizerStrategy::class)]
//#[CoversClass(FacturaCompraNormalizerStrategy::class)]
#[CoversClass(GuiaDespachoNormalizerStrategy::class)]
//#[CoversClass(NotaDebitoNormalizerStrategy::class)]
//#[CoversClass(NotaCreditoNormalizerStrategy::class)]
#[CoversClass(FacturaExportacionNormalizerStrategy::class)]
#[CoversClass(NotaDebitoExportacionNormalizerStrategy::class)]
#[CoversClass(NotaCreditoExportacionNormalizerStrategy::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
//#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(FacturaExentaSanitizerStrategy::class)]
#[CoversClass(BoletaAfectaSanitizerStrategy::class)]
#[CoversClass(BoletaExentaSanitizerStrategy::class)]
//#[CoversClass(LiquidacionFacturaSanitizerStrategy::class)]
//#[CoversClass(FacturaCompraSanitizerStrategy::class)]
#[CoversClass(GuiaDespachoSanitizerStrategy::class)]
//#[CoversClass(NotaDebitoSanitizerStrategy::class)]
//#[CoversClass(NotaCreditoSanitizerStrategy::class)]
#[CoversClass(FacturaExportacionSanitizerStrategy::class)]
#[CoversClass(NotaDebitoExportacionSanitizerStrategy::class)]
#[CoversClass(NotaCreditoExportacionSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
//#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(FacturaExentaValidatorStrategy::class)]
#[CoversClass(BoletaAfectaValidatorStrategy::class)]
#[CoversClass(BoletaExentaValidatorStrategy::class)]
//#[CoversClass(LiquidacionFacturaValidatorStrategy::class)]
//#[CoversClass(FacturaCompraValidatorStrategy::class)]
#[CoversClass(GuiaDespachoValidatorStrategy::class)]
//#[CoversClass(NotaDebitoValidatorStrategy::class)]
//#[CoversClass(NotaCreditoValidatorStrategy::class)]
#[CoversClass(FacturaExportacionValidatorStrategy::class)]
#[CoversClass(NotaDebitoExportacionValidatorStrategy::class)]
#[CoversClass(NotaCreditoExportacionValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafBag::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(AutorizacionDte::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(PersonaNatural::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
class CrearSobreEnvioAmbienteCertificacionSiiTest extends TestCase
{
    public static function provideDocumentosOk(): array
    {
        // Buscar los archivos con los casos para el test.
        $codes = ['034', '039', '041', '052', '110', '111', '112'];
        $files = [];
        foreach ($codes as $code) {
            $filesPath = self::getFixturesPath() . "/yaml/documentos_ok/{$code}_*/{$code}_001_*.yaml";
            $files = array_merge($files, glob($filesPath));
        }

        // Armar datos de prueba.
        $documentosOk = [];
        foreach ($files as $yamlFile) {
            $name = basename($yamlFile);
            $code = substr($name, 0, 3);
            $cafFile = self::getFixturesPath() . '/caf/' . $code . '.xml';
            $documentosOk[$name] = [$yamlFile, $cafFile];
        }

        // Entregar los datos para las pruebas.
        return $documentosOk;
    }

    #[DataProvider('provideDocumentosOk')]
    public function testCrearSobreEnvioAmbienteCertificacionSii(
        string $yamlFile,
        string $cafFile
    ): void {
        // Si el archivo CAF no existe el test no se realiza.
        if (!file_exists($cafFile)) {
            $this->markTestSkipped('No existe el CAF ' . $cafFile);
        }

        // Iniciar aplicación.
        $app = Application::getInstance();

        // Obtener facturador (componente de documentos).
        $biller = $app
            ->getBillingPackage()
            ->getDocumentComponent()
        ;

        // Tratar de cargar el certificado digital. Si no se logra cargar el
        // test se marcará como "saltado".
        try {
            $certificate = $app
                ->getPrimePackage()
                ->getCertificateComponent()
                ->getLoaderWorker()
                ->createFromFile(
                    getenv('LIBREDTE_CERTIFICATE_FILE'),
                    getenv('LIBREDTE_CERTIFICATE_PASS')
                )
            ;
        } catch (Throwable $e) {
            $this->markTestSkipped($e->getMessage());
        }

        // Cargar contenido del archivo CAF.
        $cafLoader = $app
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafLoaderWorker()
        ;
        $cafData = file_get_contents($cafFile);
        $cafBag = $cafLoader->load($cafData);
        $caf = $cafBag->getCaf();


        // Cargar datos del caso de prueba.
        $yamlData = file_get_contents($yamlFile);
        $data = Yaml::parse($yamlData);
        unset($data['Test']);

        // Ajustar folio con el primer valor del CAF y el RUT del emisor que
        // esté configurado como variable de entorno.
        $data['Encabezado']['IdDoc']['Folio'] = $caf->getFolioDesde();
        $data['Encabezado']['Emisor']['RUTEmisor'] = getenv('LIBREDTE_COMPANY');

        // Sanitizar el arreglo quitando todo lo que no sea ASCII.
        // array_walk_recursive($data, function (&$value) {
        //     if (is_string($value)) {
        //         // Reemplazar caracteres no ASCII con "?".
        //         $value = preg_replace('/[^\x20-\x7E]/', '?', $value);
        //     }
        // });

        // Facturar el documento.
        $documentBag = $biller->bill($data, $caf, $certificate);

        // Validar esquema y firma del documento.
        $validator = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getValidatorWorker()
        ;
        $validator->validateSchema($documentBag);
        $validator->validateSignature($documentBag);

        // Agregar al emisor la fecha de resolución y número de resolución.
        $documentBag->getEmisor()->setAutorizacionDte(
            new AutorizacionDte(getenv('LIBREDTE_ENV_TEST_AUTH_DATE'))
        );

        // Crear sobre, agregar documento y asignar carátula.
        $dispatcher = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getDispatcherWorker()
        ;
        $envelope = new DocumentEnvelope();
        $envelope->addDocument($documentBag);
        $envelope->setCertificate($certificate);

        $dispatcher->normalize($envelope);

        //echo $envelope->getXmlDocument()->saveXml() , "\n\n";

        $dispatcher->validateSchema($envelope);
        //$dispatcher->validateSignature($envelope);

        // Guardar el XML.
        $xmlEnvelope = $envelope->getXmlDocument()->saveXml();
        file_put_contents($yamlFile . '-sobre.xml', $xmlEnvelope);

        // Todo OK.
        $this->assertTrue(true);
    }
}
