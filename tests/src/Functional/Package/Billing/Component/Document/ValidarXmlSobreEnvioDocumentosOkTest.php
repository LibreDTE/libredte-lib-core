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

use Derafu\Signature\Contract\SignatureValidatorInterface;
use Derafu\Signature\Service\SignatureGenerator;
use Derafu\Signature\Service\SignatureValidator;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DispatcherWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentEnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\SobreEnvio;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DispatcherWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Validación de documentos XML generados para producción con otro software de
 * facturación o bien con la versión de 2016 de LibreDTE (en producción).
 *
 * Este test busca validar el sobre de documentos que están "OK" (recibidos por
 * el SII) en ambiente de producción.
 */
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(SobreEnvio::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoSobre::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(DocumentEnvelope::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DispatcherWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(AutorizacionDte::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(Emisor::class)]
class ValidarXmlSobreEnvioDocumentosOkTest extends TestCase
{
    // Worker que tiene los servicios para trabajar con XML del proceso de
    // interfaz de DTE (sobres de documentos, aka: DocumentEnvelope).
    private DispatcherWorkerInterface $dispatcher;

    // Worker que tiene los servicios para validar un DTE.
    private ValidatorWorkerInterface $validator;

    // Worker del componente signature para la validación de firmas.
    private SignatureValidatorInterface $signatureValidator;

    // Configuración inicial del test.
    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->dispatcher = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getDispatcherWorker()
        ;

        $this->validator = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getValidatorWorker()
        ;

        $xmlService = new XmlService(
            new XmlEncoder(),
            new XmlDecoder(),
            new XmlValidator()
        );
        $this->signatureValidator = new SignatureValidator(
            new SignatureGenerator($xmlService),
            $xmlService
        );
    }

    // Proveedor de los documentos XML con los casos de prueba que se deben
    // realizar.
    //
    // Si el directorio documentos_ok/ no contiene archivos XML (p.ej. en CI
    // donde los fixtures no se incluyen en el repo), retorna un dataset con un
    // marcador especial. createEnvelope() detecta ese marcador y llama a
    // markTestSkipped(), evitando el exit code 2 que PHPUnit 11 produce cuando
    // un DataProvider retorna un array vacío.
    public static function provideDocumentosOk(): array
    {
        // Buscar los archivos con los casos para el test.
        $filesPath = self::getFixturesPath() . '/xml/documentos_ok/*.xml';
        $files = glob($filesPath) ?: [];

        // Si no hay fixtures, retornar marcador para skip en lugar de vacío.
        if (empty($files)) {
            return ['sin fixtures' => ['']];
        }

        // Armar datos de prueba.
        $documentosOk = [];
        foreach ($files as $file) {
            $documentosOk[basename($file)] = [$file];
        }

        // Entregar los datos para las pruebas.
        return $documentosOk;
    }

    // Crea un sobre a partir de un archivo XML.
    //
    // Si se recibe una ruta vacía (marcador de "sin fixtures" del DataProvider),
    // marca el test como skipped en lugar de intentar cargar un archivo.
    private function createEnvelope(string $file): DocumentEnvelopeInterface
    {
        if ($file === '') {
            $this->markTestSkipped(
                'No hay fixtures en tests/fixtures/xml/documentos_ok/. '
                . 'Agregar archivos XML de sobres EnvioDTE para ejecutar estos tests.'
            );
        }

        $xml = file_get_contents($file);
        $envelope = $this->dispatcher->loadXml($xml);

        return $envelope;
    }

    // Validación general de los datos del sobre.
    // Verifica que se puedan extraer correctamente RutEmisor, RutEnvia,
    // RutReceptor y FchResol de la carátula del EnvioDTE.
    #[DataProvider('provideDocumentosOk')]
    public function testValidateSobreGeneralData(string $file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();

        $this->assertSame(1, count($documents));
        $this->assertNotEmpty($envelope->getSobreEnvio()->getRutEmisor());
        $this->assertNotEmpty($envelope->getSobreEnvio()->getRunMandatario());
        $this->assertNotEmpty($envelope->getSobreEnvio()->getRutReceptor());
        $this->assertNotEmpty($envelope->getSobreEnvio()->getAutorizacionDte()->getFechaResolucion());
    }

    // Validación del esquema XML del sobre contra EnvioDTE_v10.xsd.
    #[DataProvider('provideDocumentosOk')]
    public function testValidateSobreSchema(string $file): void
    {
        $envelope = $this->createEnvelope($file);

        $this->dispatcher->validateSchema($envelope);
        $this->assertTrue(true);
    }

    // Validación del esquema XML del documento (DTE) que viene en el sobre
    // contra el XSD correspondiente al tipo de documento (ej. DTE_v10.xsd).
    #[DataProvider('provideDocumentosOk')]
    public function testValidateDocumentoSchema($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $this->validator->validateSchema($document);
        $this->assertTrue(true);
    }

    // Valida el DigestValue de la firma del sobre (Firma 2, referencia #SetDoc).
    #[DataProvider('provideDocumentosOk')]
    public function testValidateSobreDigestValue($file): void
    {
        $envelope = $this->createEnvelope($file);

        $signatureElement = $envelope->getXmlDocument()->getElementsByTagName(
            'Signature'
        )->item(1);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->signatureValidator->validateXmlDigestValue(
            $envelope->getXmlDocument(),
            $signatureNode
        );
        $this->assertTrue(true);
    }

    // Valida el SignatureValue de la firma del sobre (Firma 2, referencia #SetDoc).
    #[DataProvider('provideDocumentosOk')]
    public function testValidateSobreSignatureValue($file): void
    {
        $envelope = $this->createEnvelope($file);

        $signatureElement = $envelope->getXmlDocument()->getElementsByTagName(
            'Signature'
        )->item(1);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
        $this->assertTrue(true);
    }

    // Valida la firma completa del sobre vía DispatcherWorker::validateSignature().
    #[DataProvider('provideDocumentosOk')]
    public function testValidateSobreSignature(string $file): void
    {
        $envelope = $this->createEnvelope($file);

        $results = $this->dispatcher->validateSignature($envelope);
        foreach ($results as $result) {
            $this->assertTrue($result->isValid());
        }
    }

    // Valida el DigestValue de la firma del documento (Firma 1).
    #[DataProvider('provideDocumentosOk')]
    public function testValidateDocumentoDigestValue($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $signatureElement = $document->getXmlDocument()->getElementsByTagName(
            'Signature'
        )->item(0);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->signatureValidator->validateXmlDigestValue(
            $document->getXmlDocument(),
            $signatureNode
        );
        $this->assertTrue(true);
    }

    // Valida el SignatureValue de la firma del documento (Firma 1).
    #[DataProvider('provideDocumentosOk')]
    public function testValidateDocumentoSignatureValue($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $signatureElement = $document->getXmlDocument()->getElementsByTagName(
            'Signature'
        )->item(0);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
        $this->assertTrue(true);
    }

    // Valida la firma completa del documento vía ValidatorWorker::validateSignature().
    #[DataProvider('provideDocumentosOk')]
    public function testValidateDocumentoSignature($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $result = $this->validator->validateSignature($document);
        $this->assertTrue($result->isValid());
    }
}
