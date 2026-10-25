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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Validation of the official SII fixture: F60T33-ejemplo-oficial-SII.xml
 *
 * This test works exclusively with the official SII example, a test EnvioDTE
 * generated in 2003 (Folio 60, TipoDTE 33, issuer RUT 97975000-5).
 * The file contains two XML DSIG signatures:
 *
 *   - Signature 1 (document): <Signature> inside <DTE>, reference "#F60T33"
 *                          (the <Documento ID="F60T33"> element).
 *   - Signature 2 (envelope): <Signature> at the end of <EnvioDTE>, reference
 *                          "#SetDoc" (the <SetDTE ID="SetDoc"> element).
 *
 * ============================================================================
 * FIXTURE ANALYSIS: KNOWN INCONSISTENCIES
 * ============================================================================
 *
 * 1. RSA KEY MISMATCH IN THE <KeyInfo> BLOCK
 * -------------------------------------------------
 * Both signatures contain a <KeyInfo> block with two representations of the
 * public key that do NOT belong to the same key pair:
 *
 *   <RSAKeyValue>  Modulus (first 16 bytes hex): b4d1249e46f5907883d4...
 *   <X509Certificate> Modulus (first 16 bytes hex): bc59955875c6bf6c2902...
 *
 * Document and envelope signatures were generated with the private key
 * matching <RSAKeyValue>. The <X509Certificate> (in the name of
 * "Wilibaldo Gonzalez Cabrera", valid 2003-10-01 / 2004-09-30, issued by
 * E-CERTCHILE) contains a DIFFERENT public key and cannot be used to
 * verify signatures in this file.
 *
 * Our code (SignatureValidator) uses <X509Certificate> to
 * verify the signature, which is standard secure practice (the X.509 certificate
 * provides identity and trust chain). Because the certificate key
 * does not match the signing key, every SignatureValue verification fails
 * with OpenSSL error "invalid padding" (error:0200008A / error:02000072).
 * This is CORRECT code behavior: it rejects a signature whose verification
 * key does not match the declared certificate.
 *
 * 2. C14N INCONSISTENCY IN SignedInfo
 * ------------------------------------------
 * Both signatures declare standard C14N in <CanonicalizationMethod>:
 *   http://www.w3.org/TR/2001/REC-xml-c14n-20010315
 *
 * However, when verifying with the <RSAKeyValue> key:
 *
 *   - Signature 1 (document): validates only with EXCLUSIVE C14N of SignedInfo.
 *     Document SignedInfo inherits xmlns:xsi from <EnvioDTE>. With standard C14N
 *     that propagates → signature does not verify. With exclusive C14N (which
 *     omits unused namespaces in the subtree) → signature verifies. The
 *     original signing tool used exclusive C14N in practice although it
 *     declared standard C14N.
 *
 *   - Signature 2 (envelope): validates only with STANDARD C14N of SignedInfo.
 *     Its SignedInfo also inherits xmlns:xsi, but the signature was generated
 *     including that inherited namespace (standard C14N).
 *
 * 3. SIGNATURE STATUS PER EXTERNAL TOOL (XML DSIG verifier)
 * -------------------------------------------------------------------------
 * An online XML DSIG validator (using <RSAKeyValue>
 * and applying exclusive C14N to all SignedInfo) reports:
 *
 *   Signature 1 (document): Verified / digest valid.
 *   Signature 2 (envelope): Invalid  / digest valid.
 *
 * This matches the findings: with RSAKeyValue + exclusive C14N, the
 * document verifies but the envelope does not (because the envelope requires standard C14N).
 *
 * ============================================================================
 * CURRENT TEST STATUS (all pass: 9/9)
 * ============================================================================
 *
 * Document DigestValue (#F60T33): VALID.
 *   The strip-namespaces + C14N fallback in SignatureValidator::findMatchingDigestValue()
 *   correctly computes DigestValue hlmQtu/AyjUjTDhM3852wvRCr8w=.
 *
 * Envelope DigestValue (#SetDoc): VALID.
 *   The main SignatureValidator::generateXmlDigestValue() method
 *   computes it correctly: 4OTWXyRl5fw3htjTyZXQtYEsC3E=.
 *
 * Document SignatureValue: FAILS (expected SignatureException).
 *   Reason: code verifies with X509Certificate (key bc59...) but the
 *   signature was generated with RSAKeyValue (key b4d1...). Different keys →
 *   "invalid padding". Correct code behavior.
 *
 * Envelope SignatureValue: FAILS (expected SignatureException).
 *   Same reason: key mismatch between X509Certificate and RSAKeyValue.
 *   Additionally, even if RSAKeyValue were used, the envelope signature
 *   would be invalid with exclusive C14N (which the external tool also
 *   reports as invalid).
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
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(ReceptorFactory::class)]
class ValidarXmlEjemploOficialSiiTest extends TestCase
{
    // Worker with services for DTE interface XML (document envelopes,
    // aka DocumentEnvelope).
    private DispatcherWorkerInterface $dispatcher;

    // Worker with services to validate a DTE.
    private ValidatorWorkerInterface $validator;

    // Signature component worker for signature validation.
    private SignatureValidatorInterface $signatureValidator;

    // Initial test setup.
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

    // Provider of XML documents with test cases to run.
    /**
     * @return array<string, array{string}>
     */
    public static function provideDocumentosSii(): array
    {
        // Find files with test cases.
        $filesPath = self::getFixturesPath() . '/xml/sii/*.xml';
        $files = glob($filesPath);

        // Build test data.
        $documentosSii = [];
        foreach ($files as $file) {
            $documentosSii[basename($file)] = [$file];
        }

        // Return data for tests.
        return $documentosSii;
    }

    // Creates an envelope from an XML file.
    private function createEnvelope(string $file): DocumentEnvelopeInterface
    {
        $xml = file_get_contents($file);
        $envelope = $this->dispatcher->loadXml($xml);

        return $envelope;
    }

    // General validation of envelope data.
    // Asserts RutEmisor, RutEnvia, RutReceptor, and FchResol can be extracted
    // correctly from the EnvioDTE cover page.
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

    // Validate envelope XML schema against EnvioDTE_v10.xsd.
    #[DataProvider('provideDocumentosSii')]
    public function testValidateSobreSchema(string $file): void
    {
        $envelope = $this->createEnvelope($file);

        $this->dispatcher->validateSchema($envelope);
        $this->addToAssertionCount(1);
    }

    // Validate XML schema of the document (DTE) in the envelope
    // against the XSD for the document type (e.g. DTE_v10.xsd).
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentosSii')]
    public function testValidateDocumentoSchema($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];
        $this->validator->validateSchema($document);
        $this->addToAssertionCount(1);
    }

    // Validates envelope signature DigestValue (Signature 2, reference #SetDoc).
    //
    // EXPECTED RESULT: passes without exception or error.
    //
    // DigestValue 4OTWXyRl5fw3htjTyZXQtYEsC3E= is calculated correctly
    // by SignatureValidator::generateXmlDigestValue() using standard C14N of the
    // <SetDTE ID="SetDoc"> element in the envelope XmlDocument context.
    // External tool and our code agree: valid digest.
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentosSii')]
    public function testValidateSobreDigestValue($file): void
    {
        $envelope = $this->createEnvelope($file);

        $signatureElement = $envelope->getXmlDocument()->getDomDocument()->getElementsByTagName(
            'Signature'
        )->item(1);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->signatureValidator->validateXmlDigestValue(
            $envelope->getXmlDocument(),
            $signatureNode
        );
        $this->addToAssertionCount(1);
    }

    // Validates envelope signature SignatureValue (Signature 2, reference #SetDoc).
    //
    // EXPECTED RESULT: throws SignatureException (correct behavior).
    //
    // Verification fails because our code uses <X509Certificate>
    // (hex modulus: bc59955875c6bf6c...) to verify the signature, but it was
    // generated with the <RSAKeyValue> private key (hex modulus: b4d1249e46f590...).
    // Different RSA keys → OpenSSL returns "invalid padding" when trying to
    // decrypt the signature with the wrong key.
    //
    // Manually verified: with RSAKeyValue + standard C14N the envelope signature
    // does verify. However the fixture <X509Certificate> does not match the key
    // pair in use, so this fixture is not suitable for testing full
    // SignatureValue validation.
    //
    // Note: the external tool (which applies exclusive C14N to all
    // SignedInfo) also reports this signature as invalid, because the envelope was
    // signed with standard C14N and that tool uses exclusive for both.
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentosSii')]
    public function testValidateSobreSignatureValueExceptionSignatureError($file): void
    {
        $envelope = $this->createEnvelope($file);

        $signatureElement = $envelope->getXmlDocument()->getDomDocument()->getElementsByTagName(
            'Signature'
        )->item(1);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->expectException(SignatureException::class);

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
    }

    // Validates full envelope signature via DispatcherWorker::validateSignature().
    //
    // EXPECTED RESULT: returns an invalid signature validation result.
    //
    // SignatureService::validateXml() iterates all EnvioDTE signatures in
    // document appearance order. It finds Signature 1 first
    // (document, reference #F60T33): DigestValue passes, but
    // SignatureValue fails due to key mismatch (X509Certificate ≠ RSAKeyValue),
    // throwing SignatureException before evaluating Signature 2 (envelope).
    //
    // The test name mentions "DigestValueError" because that was the name of the
    // error in earlier code versions when the DigestValue of the
    // document also failed. That issue was fixed in SignatureValidator
    // via the strip-namespaces fallback in findMatchingDigestValue(). The
    // error that remains today is exclusively SignatureValue due to the fixture
    // key mismatch.
    #[DataProvider('provideDocumentosSii')]
    public function testValidateSobreSignatureExceptionDigestValueError(string $file): void
    {
        $envelope = $this->createEnvelope($file);

        $results = $this->dispatcher->validateSignature($envelope);
        foreach ($results as $result) {
            $this->assertFalse($result->isValid());
        }
    }

    // Validates document signature DigestValue (Signature 1, reference #F60T33).
    //
    // EXPECTED RESULT: passes without exception or error.
    //
    // DigestValue hlmQtu/AyjUjTDhM3852wvRCr8w= is the SHA1 of element
    // <Documento ID="F60T33"> canonicalized without namespace declarations
    // (the original signer omitted them when generating the digest). The main
    // generateXmlDigestValue() fails initially because extracting the
    // DTE from the envelope via C14N() propagates inherited namespaces the signer
    // did not include. The findMatchingDigestValue() fallback with variant
    // strip-namespaces + standard C14N computes the correct value and the test passes.
    //
    // External tool and our code agree: valid digest.
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentosSii')]
    public function testValidateDocumentoDigestValueExceptionDigestValueError($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $signatureElement = $document->getXmlDocument()->getDomDocument()->getElementsByTagName(
            'Signature'
        )->item(0);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->signatureValidator->validateXmlDigestValue(
            $document->getXmlDocument(),
            $signatureNode
        );
        $this->addToAssertionCount(1);
    }

    // Validates document signature SignatureValue (Signature 1, reference #F60T33).
    //
    // EXPECTED RESULT: throws SignatureException (correct behavior).
    //
    // Verification fails for the same reason as the envelope signature: code
    // verifies with <X509Certificate> (hex modulus: bc59955875c6bf6c...) but
    // the signature was generated with <RSAKeyValue> (hex modulus: b4d1249e46f590...).
    //
    // Manually verified: with RSAKeyValue + exclusive C14N of SignedInfo the
    // document signature does verify. The original signer used exclusive C14N
    // in practice (omits xmlns:xsi inherited from ancestor <EnvioDTE>), but
    // declared standard C14N in XML. The external tool, which also applies
    // exclusive C14N, reports this signature as valid (Signature 1 Verified).
    // Our code cannot verify it due to the fixture key mismatch.
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentosSii')]
    public function testValidateDocumentoSignatureValueExceptionSignatureError($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $signatureElement = $document->getXmlDocument()->getDomDocument()->getElementsByTagName(
            'Signature'
        )->item(0);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->expectException(SignatureException::class);

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
    }

    // Validates full document signature via ValidatorWorker::validateSignature().
    //
    // EXPECTED RESULT: returns an invalid signature validation result.
    //
    // Analogous to testValidateSobreSignatureExceptionDigestValueError() but using
    // ValidatorWorker on the extracted DTE DocumentBag. DigestValue
    // passes (strip-namespaces fallback), but SignatureValue fails due to the
    // fixture key mismatch (X509Certificate ≠ RSAKeyValue).
    //
    // The test name mentions "DigestValueError" for the same historical reason
    // explained in testValidateSobreSignatureExceptionDigestValueError().
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentosSii')]
    public function testValidateDocumentoSignatureExceptionDigestValueError($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $result = $this->validator->validateSignature($document);
        $this->assertFalse($result->isValid());
    }
}
