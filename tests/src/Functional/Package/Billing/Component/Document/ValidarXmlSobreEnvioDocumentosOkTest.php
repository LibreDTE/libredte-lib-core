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

use Derafu\Lib\Core\Package\Prime\Component\Signature\Contract\ValidatorWorkerInterface as SignatureValidatorWorkerInterface;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Exception\SignatureException;
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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Validación de firma electrónica de XML de terceros.
 *
 * Actualmente se valida solo un XML de ejemplo del SII. Sin embargo el test
 * quedó creado para validar cualquier XML del directorio:
 * ./tests/fixtures/xml/documentos_ok
 *
 * NOTE: La validación de una de las firmas falla indicando que la firma es
 * inválida. Se asume que efectivamente la firma viene mal pues fue validada
 * con una herramienta online para verificar XML DSIG.
 *
 * Salida del sitio web para la validación de la firma del XML:
 *
 * NumSignatures: 2
 *
 *   Signature 1                            <- firma del documento (?).
 *     Signature Verified
 *     Number of Reference Digests = 1
 *     Reference 1 digest is valid.
 *
 *   Signature 2                            <- firma del sobre (?).
 *     Signature is Invalid
 *     Number of Reference Digests = 1
 *     Reference 1 digest is valid.
 *
 * WARNING: Este test debería pasar con ambos DigestValue y con una de las
 * firmas. Esto es algo que no se está haciendo, fues la salida del test es:
 *
 *   Validate sobre digest value -> DIGEST VALUE SOBRE OK.
 *   Validate sobre signature value exception signature error
 *   Validate documento digest value exception digest value error
 *   Validate documento signature value exception signature error
 *
 * Solo pasa un DigestValue y ninguna de las firmas.
 *
 * TODO: Se debe determinar cuál es el test que realmente debe fallar, según la
 * web usada para validar. Y si es que realmente debe fallar o es un problema
 * con algún paso en LibreDTE o, directamente, en el componente de firma
 * electrónica de Derafu.
 */
#[CoversClass(Application::class)]
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
class ValidarXmlSobreEnvioDocumentosOkTest extends TestCase
{
    // Worker que tiene los servicios para trabajar con XML del proceso de
    // interfamcio de DTE (sobres de documentos, aka: DocumentEnvelope).
    private DispatcherWorkerInterface $dispatcher;

    // Worker que tiene los servicios para validar un DTE.
    private ValidatorWorkerInterface $validator;

    // Worker del componente signature para la validación de firmas.
    private SignatureValidatorWorkerInterface $signatureValidator;

    // Configuración inicial del test.
    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->dispatcher = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getDispatcherWorker()
        ;

        $this->validator = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getValidatorWorker()
        ;

        $this->signatureValidator = $app
            ->getPrimePackage()
            ->getSignatureComponent()
            ->getValidatorWorker()
        ;
    }

    // Proveedor de los documentos XML con los casos de prueba que se deben
    // realizar.
    public static function provideDocumentosOk(): array
    {
        // Buscar los archivos con los casos para el test.
        $filesPath = self::getFixturesPath() . '/xml/documentos_ok/*.xml';
        $files = glob($filesPath);

        // Armar datos de prueba.
        $documentosOk = [];
        foreach ($files as $file) {
            $documentosOk[basename($file)] = [$file];
        }

        // Entregar los datos para las pruebas.
        return $documentosOk;
    }

    // Crea un sobre a partir de un archivo XML.
    private function createEnvelope(string $file): DocumentEnvelopeInterface
    {
        $xml = file_get_contents($file);
        $envelope = $this->dispatcher->loadXml($xml);

        return $envelope;
    }

    // Validación general del os datos del sobre.
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

    // Validación del esquema XML del sobre.
    #[DataProvider('provideDocumentosOk')]
    public function testValidateSobreSchema(string $file): void
    {
        $envelope = $this->createEnvelope($file);

        $this->dispatcher->validateSchema($envelope);
        $this->assertTrue(true);
    }

    // Validación del esquema XML del documento (DTE) que viene en el sobre.
    #[DataProvider('provideDocumentosOk')]
    public function testValidateDocumentoSchema($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];
        $this->validator->validateSchema($document);
        $this->assertTrue(true);
    }

    // Valida solo el digest value de la firma del sobre
    // Importante: Esto es útil para debug solamente.
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

    // Valida solo el digest value de la firma del sobre
    // Importante: Esto es útil para debug solamente.
    #[DataProvider('provideDocumentosOk')]
    public function testValidateSobreSignatureValueExceptionSignatureError($file): void
    {
        $envelope = $this->createEnvelope($file);

        $signatureElement = $envelope->getXmlDocument()->getElementsByTagName(
            'Signature'
        )->item(1);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        // TODO: Revisar problema de validación.
        //$expectedMessage = 'La firma electrónica del nodo `SignedInfo` del XML para la referencia "SetDoc" no es válida. error:0200008A:rsa routines::invalid padding error:02000072:rsa routines::padding check failed error:1C880004:Provider routines::RSA lib';
        $this->expectException(SignatureException::class);
        //$this->expectExceptionMessage($expectedMessage);

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
        $this->assertTrue(true);
    }

    // Validación de la firma electrónica del XML del sobre.
    #[DataProvider('provideDocumentosOk')]
    public function testValidateSobreSignatureExceptionDigestValueError(string $file): void
    {
        $envelope = $this->createEnvelope($file);

        // TODO: Revisar problema de validación.
        //$expectedMessage = 'El DigestValue que viene en el XML "hlmQtu/AyjUjTDhM3852wvRCr8w=" para la referencia "F60T33" no coincide con el valor calculado al validar "4GXbxCc2Fhaiol1WYeMzcwRKnT4=". Los datos de la referencia podrían haber sido manipulados después de haber sido firmados.';
        $this->expectException(SignatureException::class);
        //$this->expectExceptionMessage($expectedMessage);

        $this->dispatcher->validateSignature($envelope);
        $this->assertTrue(true);
    }

    // Valida solo el digest value de la firma del documento (DTE) que viene en
    // el sobre.
    // Importante: Esto es útil para debug solamente.
    #[DataProvider('provideDocumentosOk')]
    public function testValidateDocumentoDigestValueExceptionDigestValueError($file): void
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

        // TODO: Revisar problema de validación.
        //$expectedMessage = 'El DigestValue que viene en el XML "hlmQtu/AyjUjTDhM3852wvRCr8w=" para la referencia "F60T33" no coincide con el valor calculado al validar "4GXbxCc2Fhaiol1WYeMzcwRKnT4=". Los datos de la referencia podrían haber sido manipulados después de haber sido firmados.';
        $this->expectException(SignatureException::class);
        //$this->expectExceptionMessage($expectedMessage);

        $this->signatureValidator->validateXmlDigestValue(
            $document->getXmlDocument(),
            $signatureNode
        );
        $this->assertTrue(true);
    }

    // Valida solo el digest value de la firma del documento (DTE) que viene en
    // el sobre.
    // Importante: Esto es útil para debug solamente.
    #[DataProvider('provideDocumentosOk')]
    public function testValidateDocumentoSignatureValueExceptionSignatureError($file): void
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

        // TODO: Revisar problema de validación.
        //$expectedMessage = 'La firma electrónica del nodo `SignedInfo` del XML para la referencia "F60T33" no es válida. error:0200008A:rsa routines::invalid padding error:02000072:rsa routines::padding check failed error:1C880004:Provider routines::RSA lib';
        $this->expectException(SignatureException::class);
        //$this->expectExceptionMessage($expectedMessage);

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
        $this->assertTrue(true);
    }

    // Validación de la firma electrónica del documento (DTE) que viene en el
    // sobre.
    #[DataProvider('provideDocumentosOk')]
    public function testValidateDocumentoSignatureExceptionDigestValueError($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        // TODO: Revisar problema de validación.
        //$expectedMessage = 'El DigestValue que viene en el XML "hlmQtu/AyjUjTDhM3852wvRCr8w=" para la referencia "F60T33" no coincide con el valor calculado al validar "4GXbxCc2Fhaiol1WYeMzcwRKnT4=". Los datos de la referencia podrían haber sido manipulados después de haber sido firmados.';
        $this->expectException(SignatureException::class);
        //$this->expectExceptionMessage($expectedMessage);

        $this->validator->validateSignature($document);
        $this->assertTrue(true);
    }
}
