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
use Derafu\Signature\Exception\SignatureException;
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
 * Validación del fixture oficial del SII: F60T33-ejemplo-oficial-SII.xml
 *
 * Este test trabaja exclusivamente con el ejemplo oficial del SII, un EnvioDTE
 * de prueba generado en 2003 (Folio 60, TipoDTE 33, RUT emisor 97975000-5).
 * El archivo contiene dos firmas XML DSIG:
 *
 *   - Firma 1 (documento): <Signature> dentro de <DTE>, referencia "#F60T33"
 *                          (el elemento <Documento ID="F60T33">).
 *   - Firma 2 (sobre):     <Signature> al final de <EnvioDTE>, referencia
 *                          "#SetDoc" (el elemento <SetDTE ID="SetDoc">).
 *
 * ============================================================================
 * ANÁLISIS DEL FIXTURE: INCONSISTENCIAS CONOCIDAS
 * ============================================================================
 *
 * 1. MISMATCH DE CLAVES RSA EN EL BLOQUE <KeyInfo>
 * -------------------------------------------------
 * Ambas firmas contienen un bloque <KeyInfo> con dos representaciones de la
 * clave pública que NO corresponden al mismo par de claves:
 *
 *   <RSAKeyValue>  Modulus (16 bytes iniciales hex): b4d1249e46f5907883d4...
 *   <X509Certificate> Modulus (16 bytes iniciales hex): bc59955875c6bf6c2902...
 *
 * Las firmas del documento y el sobre fueron generadas con la clave privada
 * correspondiente al <RSAKeyValue>. El <X509Certificate> (a nombre de
 * "Wilibaldo Gonzalez Cabrera", válido 2003-10-01 / 2004-09-30, emitido por
 * E-CERTCHILE) contiene una clave pública DIFERENTE y no puede usarse para
 * verificar las firmas de este archivo.
 *
 * Nuestro código (SignatureValidator) utiliza el <X509Certificate> para
 * verificar la firma, que es la práctica segura estándar (el certificado X.509
 * proporciona identidad y cadena de confianza). Como la clave del certificado
 * no coincide con la clave de firma, toda verificación de SignatureValue falla
 * con error OpenSSL "invalid padding" (error:0200008A / error:02000072).
 * Esto es el comportamiento CORRECTO del código: rechaza una firma cuya clave
 * de verificación no coincide con el certificado declarado.
 *
 * 2. INCONSISTENCIA DE C14N EN EL SignedInfo
 * ------------------------------------------
 * Ambas firmas declaran en <CanonicalizationMethod> el algoritmo C14N estándar:
 *   http://www.w3.org/TR/2001/REC-xml-c14n-20010315
 *
 * Sin embargo, al verificar con la clave del <RSAKeyValue>:
 *
 *   - Firma 1 (documento): solo valida con C14N EXCLUSIVO del SignedInfo.
 *     El SignedInfo del documento hereda xmlns:xsi de <EnvioDTE>. Con C14N
 *     estándar eso se propaga → firma no verifica. Con C14N exclusivo (que
 *     omite namespaces no utilizados en el subárbol) → firma verifica. La
 *     herramienta original de firma usó C14N exclusivo en la práctica aunque
 *     declaró C14N estándar.
 *
 *   - Firma 2 (sobre): solo valida con C14N ESTÁNDAR del SignedInfo.
 *     Su SignedInfo también hereda xmlns:xsi, pero la firma fue generada
 *     incluyendo ese namespace heredado (C14N estándar).
 *
 * 3. ESTADO DE LAS FIRMAS SEGÚN HERRAMIENTA EXTERNA (verificador XML DSIG)
 * -------------------------------------------------------------------------
 * Una herramienta online de validación de XML DSIG (que usa el <RSAKeyValue>
 * y aplica C14N exclusivo a todos los SignedInfo) reporta:
 *
 *   Signature 1 (documento): Verified / digest valid.
 *   Signature 2 (sobre):     Invalid  / digest valid.
 *
 * Esto coincide con los hallazgos: con RSAKeyValue + C14N exclusivo, el
 * documento verifica pero el sobre no (porque el sobre requiere C14N estándar).
 *
 * ============================================================================
 * ESTADO ACTUAL DE LOS TESTS (todos pasan: 9/9)
 * ============================================================================
 *
 * DigestValue del documento (#F60T33): VÁLIDO.
 *   El fallback strip-namespaces + C14N de SignatureValidator::findMatchingDigestValue()
 *   computa correctamente el DigestValue hlmQtu/AyjUjTDhM3852wvRCr8w=.
 *
 * DigestValue del sobre (#SetDoc): VÁLIDO.
 *   El método principal de SignatureValidator::generateXmlDigestValue() lo
 *   computa correctamente: 4OTWXyRl5fw3htjTyZXQtYEsC3E=.
 *
 * SignatureValue del documento: FALLA (SignatureException esperada).
 *   Razón: el código verifica con X509Certificate (clave bc59...) pero la
 *   firma fue generada con RSAKeyValue (clave b4d1...). Claves distintas →
 *   "invalid padding". Comportamiento correcto del código.
 *
 * SignatureValue del sobre: FALLA (SignatureException esperada).
 *   Misma razón: mismatch de claves entre X509Certificate y RSAKeyValue.
 *   Adicionalmente, incluso si se usara el RSAKeyValue, la firma del sobre
 *   sería inválida con C14N exclusivo (que es lo que el tool externo también
 *   reporta como inválida).
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
class ValidarXmlEjemploOficialSiiTest extends TestCase
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
    public static function provideDocumentosSii(): array
    {
        // Buscar los archivos con los casos para el test.
        $filesPath = self::getFixturesPath() . '/xml/sii/*.xml';
        $files = glob($filesPath);

        // Armar datos de prueba.
        $documentosSii = [];
        foreach ($files as $file) {
            $documentosSii[basename($file)] = [$file];
        }

        // Entregar los datos para las pruebas.
        return $documentosSii;
    }

    // Crea un sobre a partir de un archivo XML.
    private function createEnvelope(string $file): DocumentEnvelopeInterface
    {
        $xml = file_get_contents($file);
        $envelope = $this->dispatcher->loadXml($xml);

        return $envelope;
    }

    // Validación general de los datos del sobre.
    // Verifica que se puedan extraer correctamente RutEmisor, RutEnvia,
    // RutReceptor y FchResol de la carátula del EnvioDTE.
    #[DataProvider('provideDocumentosSii')]
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
    #[DataProvider('provideDocumentosSii')]
    public function testValidateSobreSchema(string $file): void
    {
        $envelope = $this->createEnvelope($file);

        $this->dispatcher->validateSchema($envelope);
        $this->assertTrue(true);
    }

    // Validación del esquema XML del documento (DTE) que viene en el sobre
    // contra el XSD correspondiente al tipo de documento (ej. DTE_v10.xsd).
    #[DataProvider('provideDocumentosSii')]
    public function testValidateDocumentoSchema($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];
        $this->validator->validateSchema($document);
        $this->assertTrue(true);
    }

    // Valida el DigestValue de la firma del sobre (Firma 2, referencia #SetDoc).
    //
    // RESULTADO ESPERADO: pasa sin excepción.
    //
    // El DigestValue 4OTWXyRl5fw3htjTyZXQtYEsC3E= es calculado correctamente
    // por SignatureValidator::generateXmlDigestValue() usando C14N estándar del
    // elemento <SetDTE ID="SetDoc"> en el contexto del XmlDocument del sobre.
    // Tanto la herramienta externa como nuestro código concuerdan: digest válido.
    #[DataProvider('provideDocumentosSii')]
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
    //
    // RESULTADO ESPERADO: lanza SignatureException (comportamiento correcto).
    //
    // La verificación falla porque nuestro código usa el <X509Certificate>
    // (módulo hex: bc59955875c6bf6c...) para verificar la firma, pero ésta fue
    // generada con la clave privada del <RSAKeyValue> (módulo hex: b4d1249e46f590...).
    // Son claves RSA distintas → OpenSSL retorna "invalid padding" al intentar
    // descifrar la firma con la clave equivocada.
    //
    // Verificado manualmente: con RSAKeyValue + C14N estándar la firma del sobre
    // sí verifica. Sin embargo el <X509Certificate> del fixture es incorrecto
    // respecto al par de claves usado, lo que hace que este fixture no sea apto
    // para testear la validación completa de SignatureValue.
    //
    // Nota: la herramienta externa (que aplica C14N exclusivo a todos los
    // SignedInfo) también reporta esta firma como inválida, porque el sobre fue
    // firmado con C14N estándar y esa herramienta usa exclusivo para ambas.
    #[DataProvider('provideDocumentosSii')]
    public function testValidateSobreSignatureValueExceptionSignatureError($file): void
    {
        $envelope = $this->createEnvelope($file);

        $signatureElement = $envelope->getXmlDocument()->getElementsByTagName(
            'Signature'
        )->item(1);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->expectException(SignatureException::class);

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
        $this->assertTrue(true);
    }

    // Valida la firma completa del sobre vía DispatcherWorker::validateSignature().
    //
    // RESULTADO ESPERADO: lanza SignatureException (comportamiento correcto).
    //
    // SignatureService::validateXml() itera todas las firmas del EnvioDTE en
    // orden de aparición en el documento. Encuentra primero la Firma 1
    // (documento, referencia #F60T33): el DigestValue pasa, pero el
    // SignatureValue falla por mismatch de claves (X509Certificate ≠ RSAKeyValue),
    // lanzando SignatureException antes de llegar a evaluar la Firma 2 (sobre).
    //
    // El nombre del test menciona "DigestValueError" porque así se llamaba el
    // error en versiones anteriores del código cuando el DigestValue del
    // documento también fallaba. Ese problema fue resuelto en SignatureValidator
    // mediante el fallback strip-namespaces en findMatchingDigestValue(). El
    // error que persiste hoy es exclusivamente de SignatureValue por el mismatch
    // de claves del fixture.
    #[DataProvider('provideDocumentosSii')]
    public function testValidateSobreSignatureExceptionDigestValueError(string $file): void
    {
        $envelope = $this->createEnvelope($file);

        $this->expectException(SignatureException::class);

        $this->dispatcher->validateSignature($envelope);
        $this->assertTrue(true);
    }

    // Valida el DigestValue de la firma del documento (Firma 1, referencia #F60T33).
    //
    // RESULTADO ESPERADO: pasa sin excepción.
    //
    // El DigestValue hlmQtu/AyjUjTDhM3852wvRCr8w= es el SHA1 del elemento
    // <Documento ID="F60T33"> canonicalizado sin declaraciones de namespace
    // (el firmante original las omitió al generar el digest). El método principal
    // generateXmlDigestValue() falla en primera instancia porque al extraer el
    // DTE del sobre via C14N() se propagan namespaces heredados que el firmante
    // no incluyó. El fallback findMatchingDigestValue() con la variante
    // strip-namespaces + C14N estándar calcula el valor correcto y el test pasa.
    //
    // Tanto la herramienta externa como nuestro código concuerdan: digest válido.
    #[DataProvider('provideDocumentosSii')]
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

        $this->signatureValidator->validateXmlDigestValue(
            $document->getXmlDocument(),
            $signatureNode
        );
        $this->assertTrue(true);
    }

    // Valida el SignatureValue de la firma del documento (Firma 1, referencia #F60T33).
    //
    // RESULTADO ESPERADO: lanza SignatureException (comportamiento correcto).
    //
    // La verificación falla por la misma razón que la firma del sobre: el código
    // verifica con el <X509Certificate> (módulo hex: bc59955875c6bf6c...) pero
    // la firma fue generada con el <RSAKeyValue> (módulo hex: b4d1249e46f590...).
    //
    // Verificado manualmente: con RSAKeyValue + C14N EXCLUSIVO del SignedInfo la
    // firma del documento sí verifica. El firmante original usó C14N exclusivo
    // en la práctica (omite el xmlns:xsi heredado del <EnvioDTE> ancestro), pero
    // declaró C14N estándar en el XML. La herramienta externa, que también aplica
    // C14N exclusivo, reporta esta firma como válida (Signature 1 Verified).
    // Nuestro código no la puede verificar por el mismatch de claves del fixture.
    #[DataProvider('provideDocumentosSii')]
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

        $this->expectException(SignatureException::class);

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
        $this->assertTrue(true);
    }

    // Valida la firma completa del documento vía ValidatorWorker::validateSignature().
    //
    // RESULTADO ESPERADO: lanza SignatureException (comportamiento correcto).
    //
    // Análogo a testValidateSobreSignatureExceptionDigestValueError() pero usando
    // el ValidatorWorker sobre el DocumentBag del DTE extraído. El DigestValue
    // pasa (fallback strip-namespaces), pero el SignatureValue falla por el
    // mismatch de claves del fixture (X509Certificate ≠ RSAKeyValue).
    //
    // El nombre del test menciona "DigestValueError" por el mismo motivo histórico
    // explicado en testValidateSobreSignatureExceptionDigestValueError().
    #[DataProvider('provideDocumentosSii')]
    public function testValidateDocumentoSignatureExceptionDigestValueError($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $this->expectException(SignatureException::class);

        $this->validator->validateSignature($document);
        $this->assertTrue(true);
    }
}
