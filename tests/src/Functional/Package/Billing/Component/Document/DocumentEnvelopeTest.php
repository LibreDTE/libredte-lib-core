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

use Derafu\Array\ArrayReader;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\OperacionDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DispatcherException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ExamplesWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\BoletaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeBoletasTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDescuentosRecargosTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDetalleTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeImpuestoAdicionalRetencionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeIvaMntTotalTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeTransporteTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\BoletaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\BoletaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateArrayJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(ExamplesWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(Utils::class)]
#[CoversClass(NormalizeBoletaAfectaJob::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(BoletaAfectaNormalizerStrategy::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversTrait(NormalizeBoletasTrait::class)]
#[CoversTrait(NormalizeDescuentosRecargosTrait::class)]
#[CoversTrait(NormalizeDetalleTrait::class)]
#[CoversTrait(NormalizeImpuestoAdicionalRetencionTrait::class)]
#[CoversTrait(NormalizeIvaMntTotalTrait::class)]
#[CoversTrait(NormalizeTransporteTrait::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(BoletaAfectaSanitizerStrategy::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(BoletaAfectaValidatorStrategy::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CreateArrayJob::class)]
#[CoversClass(CreateXmlJob::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentEnvelope::class)]
#[CoversClass(DispatcherException::class)]
#[CoversClass(TipoSobre::class)]
#[CoversClass(CategoriaDocumento::class)]
#[CoversClass(OperacionDocumento::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(Receptor::class)]
class DocumentEnvelopeTest extends TestCase
{
    use BuildsDocumentsTrait;

    private const FACTURA = '033_factura_afecta/033_001_simple';

    private const BOLETA = '039_boleta_afecta/039_001_simple';

    public function testFirstDocumentDefinesTheTypeOfTheEnvelope(): void
    {
        $envelope = new DocumentEnvelope();

        $envelope->addDocument($this->buildBag(self::FACTURA));

        $this->assertSame(TipoSobre::ENVIO_DTE, $envelope->getTipoSobre());
        $this->assertCount(1, $envelope->requireDocuments());
    }

    public function testBoletaDefinesAnEnvelopeOfBoletas(): void
    {
        $envelope = new DocumentEnvelope();

        $envelope->addDocument($this->buildBag(self::BOLETA));

        $this->assertSame(TipoSobre::ENVIO_BOLETA, $envelope->getTipoSobre());
    }

    public function testSetDocumentsAddsEveryDocumentInOrder(): void
    {
        $factura1 = $this->buildBag(self::FACTURA);
        $factura2 = $this->buildBag('033_factura_afecta/033_002_factura_guia');
        $envelope = new DocumentEnvelope();

        $same = $envelope->setDocuments([$factura1, $factura2]);

        $this->assertSame($envelope, $same);
        $this->assertSame([$factura1, $factura2], $envelope->getDocuments());
    }

    public function testDocumentsOfDifferentEnvelopeTypesCannotBeMixed(): void
    {
        $envelope = new DocumentEnvelope();
        $envelope->addDocument($this->buildBag(self::FACTURA));

        $this->expectException(DispatcherException::class);
        $envelope->addDocument($this->buildBag(self::BOLETA));
    }

    public function testEnvelopeWithoutDocumentsHasNoDocumentsNorType(): void
    {
        $envelope = new DocumentEnvelope();

        $this->assertNull($envelope->getDocuments());
        $this->expectException(DispatcherException::class);
        $envelope->requireDocuments();
    }

    public function testTheEnvelopeAcceptsExactlyTheMaximumOfDocumentsAndRejectsTheNext(): void
    {
        $bag = $this->buildBag(self::BOLETA);
        $envelope = new DocumentEnvelope();
        $max = TipoSobre::ENVIO_BOLETA->getMaxDocumentos();

        for ($i = 0; $i < $max; $i++) {
            $envelope->addDocument($bag);
        }
        $this->assertCount($max, $envelope->requireDocuments());

        $this->expectException(DispatcherException::class);
        $envelope->addDocument($bag);
    }

    public function testDocumentsCannotBeAddedAfterTheCaratulaWasGenerated(): void
    {
        $envelope = new DocumentEnvelope();
        $envelope->addDocument($this->buildBag(self::FACTURA));
        $envelope->setCaratula(['RutEmisor' => '76192083-9']);

        $this->expectException(DispatcherException::class);
        $envelope->addDocument($this->buildBag(self::FACTURA));
    }

    public function testCaratula(): void
    {
        $envelope = new DocumentEnvelope();
        $this->assertNull($envelope->getCaratula());

        $envelope->setCaratula(['RutEmisor' => '76192083-9']);

        $this->assertSame(['RutEmisor' => '76192083-9'], $envelope->requireCaratula());
    }

    public function testWithCertificateCreatesANewEnvelopeWithTheGivenCertificate(): void
    {
        $emisor = new Emisor('76192083-9');
        $mandatario = null;
        $receptor = new Receptor('60803000-K');
        $xmlDocument = new XmlDocument();
        $factura = $this->buildBag(self::FACTURA);
        $original = (new DocumentEnvelope())
            ->setXmlDocument($xmlDocument)
            ->addDocument($factura)
            ->setEmisor($emisor)
            ->setReceptor($receptor)
        ;
        $certificate = (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9');

        $copy = $original->withCertificate($certificate);

        $this->assertNotSame($original, $copy);
        $this->assertSame($certificate, $copy->getCertificate());
        $this->assertNull($original->getCertificate());
        $this->assertSame($xmlDocument, $copy->getXmlDocument());
        $this->assertSame([$factura], $copy->getDocuments());
        $this->assertSame($emisor, $copy->getEmisor());
        $this->assertSame($mandatario, $copy->getMandatario());
        $this->assertSame($receptor, $copy->getReceptor());
        $this->assertSame($original->getTipoSobre(), $copy->getTipoSobre());
    }

    public function testWithCertificateReplacesTheCertificateOfTheOriginalEnvelope(): void
    {
        $factory = new CertificateFaker(new CertificateLoader());
        $old = $factory->createFake(id: '76192083-9');
        $new = $factory->createFake(id: '12345678-5');
        $original = (new DocumentEnvelope())
            ->addDocument($this->buildBag(self::FACTURA))
            ->setCertificate($old)
        ;

        $copy = $original->withCertificate($new);

        $this->assertSame($new, $copy->getCertificate());
        $this->assertSame($old, $original->getCertificate());
    }

    public function testWithCertificateKeepsTheOptionsOfTheEnvelope(): void
    {
        $original = (new DocumentEnvelope())
            ->addDocument($this->buildBag(self::FACTURA))
            ->setOptions(['dispatcher' => ['xml' => ['pretty' => true]]])
        ;

        $copy = $original->withCertificate(
            (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9')
        );

        $this->assertSame(['xml' => ['pretty' => true]], $copy->getDispatcherOptions());
    }

    public function testWithCertificateWithoutDocumentsThrows(): void
    {
        $this->expectException(DispatcherException::class);
        (new DocumentEnvelope())->withCertificate(
            (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9')
        );
    }

    public function testToArrayAndJsonSerialize(): void
    {
        $factura = $this->buildBag(self::FACTURA);
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml('<EnvioDTE><SetDTE ID="LibreDTE_SetDoc"/></EnvioDTE>');
        $envelope = (new DocumentEnvelope())
            ->setXmlDocument($xmlDocument)
            ->addDocument($factura)
            ->setCaratula(['RutEmisor' => '76192083-9'])
        ;

        $array = ArrayReader::fromArray($envelope->toArray());

        $this->assertSame('EnvioDTE', $array->requireString('tag'));
        $this->assertStringContainsString('<SetDTE ID="LibreDTE_SetDoc"/>', $array->requireString('xml'));
        $this->assertCount(1, $array->requireList('documents'));
        $this->assertSame($factura->toArray(), $array->requireMap('documents.0'));
        $this->assertSame(['RutEmisor' => '76192083-9'], $array->requireMap('caratula'));

        $json = ArrayReader::fromValue($envelope->jsonSerialize());
        $this->assertSame($array->requireString('xml'), base64_decode($json->requireString('xml'), true));
        $this->assertSame($factura->jsonSerialize(), $json->requireMap('documents.0'));
        $this->assertJson((string) json_encode($envelope));
    }

    public function testToArrayWithoutXmlDocumentThrows(): void
    {
        $envelope = (new DocumentEnvelope())->addDocument($this->buildBag(self::FACTURA));

        $this->expectException(DispatcherException::class);
        $envelope->toArray();
    }
}
