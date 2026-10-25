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
 * Validation of XML documents generated for production with other billing
 * software or with the 2016 LibreDTE version (in production).
 *
 * This test validates document envelopes that are "OK" (accepted by
 * the SII) in production.
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
class DocumentosOkEnvelopeXmlValidationTest extends TestCase
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
    //
    // If documentos_ok/ has no XML files (e.g. in CI where fixtures are not
    // in the repo), returns a dataset with a special marker. createEnvelope()
    // detects that marker and calls markTestSkipped(), avoiding exit code 2
    // that PHPUnit 11 produces when a DataProvider returns an empty array.
    /**
     * @return array<string, array{string}>
     */
    public static function provideDocumentOkXmlFiles(): array
    {
        // Find files with test cases.
        $filesPath = self::getFixturesPath() . '/xml/documentos_ok/*.xml';
        $files = glob($filesPath) ?: [];

        // If there are no fixtures, return a skip marker instead of empty.
        if (empty($files)) {
            return ['no fixtures' => ['']];
        }

        // Build test data.
        $documentOkXmlFiles = [];
        foreach ($files as $file) {
            $documentOkXmlFiles[basename($file)] = [$file];
        }

        // Return data for tests.
        return $documentOkXmlFiles;
    }

    // Creates an envelope from an XML file.
    //
    // If an empty path is received ("no fixtures" marker from DataProvider),
    // marks the test as skipped instead of trying to load a file.
    private function createEnvelope(string $file): DocumentEnvelopeInterface
    {
        if ($file === '') {
            $this->markTestSkipped(
                'No fixtures in tests/fixtures/xml/documentos_ok/. '
                . 'Add EnvioDTE envelope XML files to run these tests.'
            );
        }

        $xml = file_get_contents($file);
        $envelope = $this->dispatcher->loadXml($xml);

        return $envelope;
    }

    // General validation of envelope data.
    // Asserts RutEmisor, RutEnvia, RutReceptor, and FchResol can be extracted
    // correctly from the EnvioDTE cover page.
    #[DataProvider('provideDocumentOkXmlFiles')]
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
    #[DataProvider('provideDocumentOkXmlFiles')]
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
    #[DataProvider('provideDocumentOkXmlFiles')]
    public function testValidateDocumentSchema($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $this->validator->validateSchema($document);
        $this->addToAssertionCount(1);
    }

    // Validates envelope signature DigestValue (Signature 2, reference #SetDoc).
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentOkXmlFiles')]
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
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentOkXmlFiles')]
    public function testValidateSobreSignatureValue($file): void
    {
        $envelope = $this->createEnvelope($file);

        $signatureElement = $envelope->getXmlDocument()->getDomDocument()->getElementsByTagName(
            'Signature'
        )->item(1);
        $signatureNode = $this->signatureValidator->createSignatureNode(
            $signatureElement->C14N()
        );

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
        $this->addToAssertionCount(1);
    }

    // Validates full envelope signature via DispatcherWorker::validateSignature().
    #[DataProvider('provideDocumentOkXmlFiles')]
    public function testValidateSobreSignature(string $file): void
    {
        $envelope = $this->createEnvelope($file);

        $results = $this->dispatcher->validateSignature($envelope);
        foreach ($results as $result) {
            $this->assertTrue($result->isValid());
        }
    }

    // Validates document signature DigestValue (Signature 1).
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentOkXmlFiles')]
    public function testValidateDocumentDigestValue($file): void
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

    // Validates document signature SignatureValue (Signature 1).
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentOkXmlFiles')]
    public function testValidateDocumentSignatureValue($file): void
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

        $this->signatureValidator->validateXmlSignatureValue($signatureNode);
        $this->addToAssertionCount(1);
    }

    // Validates full document signature via ValidatorWorker::validateSignature().
    /**
     * @param string $file
     */
    #[DataProvider('provideDocumentOkXmlFiles')]
    public function testValidateDocumentSignature($file): void
    {
        $envelope = $this->createEnvelope($file);
        $documents = $envelope->getDocuments();
        $document = $documents[0];

        $result = $this->validator->validateSignature($document);
        $this->assertTrue($result->isValid());
    }
}
