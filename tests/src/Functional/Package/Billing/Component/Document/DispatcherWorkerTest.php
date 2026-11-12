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

use DateTimeImmutable;
use Derafu\Array\ArrayReader;
use Derafu\Xml\Exception\XmlQueryException;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DispatcherWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentEnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\SobreEnvio;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DispatcherException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Dispatcher\Job\ValidateEnvelopeJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DispatcherWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ExamplesWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeNotaCreditoJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\NotaCreditoNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDescuentosRecargosTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDetalleTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeImpuestoAdicionalRetencionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeIvaMntTotalTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeTransporteTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\NotaCreditoSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\NotaCreditoValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateArrayJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Mandatario;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\PersonaNatural;
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
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(SobreEnvio::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(TipoSobre::class)]
#[CoversClass(DocumentEnvelope::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(ExamplesWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(Utils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(NormalizeNotaCreditoJob::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(NotaCreditoNormalizerStrategy::class)]
#[CoversTrait(NormalizeDescuentosRecargosTrait::class)]
#[CoversTrait(NormalizeDetalleTrait::class)]
#[CoversTrait(NormalizeImpuestoAdicionalRetencionTrait::class)]
#[CoversTrait(NormalizeIvaMntTotalTrait::class)]
#[CoversTrait(NormalizeTransporteTrait::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(NotaCreditoSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(NotaCreditoValidatorStrategy::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CreateArrayJob::class)]
#[CoversClass(CreateXmlJob::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(PersonaNatural::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DispatcherWorker::class)]
#[CoversClass(ValidateEnvelopeJob::class)]
#[CoversClass(DispatcherException::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(AutorizacionDte::class)]
class DispatcherWorkerTest extends TestCase
{
    use BuildsDocumentsTrait;

    private const FACTURA = '033_factura_afecta/033_001_simple';

    private const NOTA_CREDITO = '061_nota_credito/061_001_anula_factura';

    private DispatcherWorkerInterface $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getDispatcherWorker()
        ;
    }

    /**
     * Bolsa con el documento de un ejemplo y su emisor autorizado por el SII.
     */
    private function authorizedBag(string $exampleId): DocumentBag
    {
        $bag = $this->buildBag($exampleId);
        $bag->requireEmisor()->setAutorizacionDte(new AutorizacionDte('2014-08-22', 80));

        return $bag;
    }

    private function envelope(): DocumentEnvelopeInterface
    {
        return $this->dispatcher->create($this->authorizedBag(self::FACTURA));
    }

    private function envelopeXml(): string
    {
        return $this->envelope()->requireXmlDocument()->saveXml();
    }

    /**
     * Día (`Y-m-d`) de la firma del sobre.
     */
    private function signatureDay(string $xml): string
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        return substr($xmlDocument->getReader()->requireString('//Caratula/TmstFirmaEnv'), 0, 10);
    }

    public function testCreateBuildsTheEnvelopeOfADocument(): void
    {
        $bag = $this->authorizedBag(self::FACTURA);

        $envelope = $this->dispatcher->create($bag);

        $this->assertSame([$bag], $envelope->getDocuments());
        $this->assertSame($bag->getCertificate(), $envelope->getCertificate());
        $caratula = $envelope->requireCaratula();
        $this->assertSame('76192083-9', $caratula['RutEmisor']);
        $this->assertSame('60803000-K', $caratula['RutReceptor']);
        $this->assertSame('2014-08-22', $caratula['FchResol']);
        $this->assertSame(80, $caratula['NroResol']);
        $this->assertSame([['TpoDTE' => 33, 'NroDTE' => 1]], $caratula['SubTotDTE']);
        $this->assertTrue($this->dispatcher->validateSignature($envelope)[0]->isValid());
    }

    public function testCreateManyGroupsTheDocumentsByType(): void
    {
        $envelope = $this->dispatcher->createMany([
            $this->authorizedBag(self::FACTURA),
            $this->authorizedBag(self::NOTA_CREDITO),
            $this->authorizedBag('033_factura_afecta/033_002_factura_guia'),
        ]);

        $this->assertCount(3, $envelope->requireDocuments());
        $this->assertEqualsCanonicalizing(
            [['TpoDTE' => 33, 'NroDTE' => 2], ['TpoDTE' => 61, 'NroDTE' => 1]],
            $envelope->requireCaratula()['SubTotDTE']
        );
        $this->assertTrue($this->dispatcher->validateSignature($envelope)[0]->isValid());
    }

    public function testCreateManyWithoutDocumentsThrows(): void
    {
        $this->expectException(DispatcherException::class);
        $this->dispatcher->createMany([]);
    }

    public function testEnvelopeBuiltByTheDispatcherPassesTheSchemaValidation(): void
    {
        $envelope = $this->envelope();

        $this->assertSame($envelope->requireXmlDocument(), $this->dispatcher->validateSchema($envelope));
    }

    public function testValidateAcceptsAnEnvelopeAnXmlDocumentAndAString(): void
    {
        $envelope = $this->dispatcher->createMany([
            $this->authorizedBag(self::FACTURA),
            $this->authorizedBag(self::NOTA_CREDITO),
        ]);
        $xmlDocument = $envelope->requireXmlDocument();

        $this->assertSame($xmlDocument, $this->dispatcher->validate($envelope));
        $this->assertSame($xmlDocument, $this->dispatcher->validate($xmlDocument));
        $fromString = $this->dispatcher->validate($xmlDocument->saveXml());
        $this->assertSame(
            $xmlDocument->query('//Caratula/RutEmisor'),
            $fromString->query('//Caratula/RutEmisor')
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideRutTags(): array
    {
        return [
            'emisor' => ['RutEmisor'],
            'envia' => ['RutEnvia'],
            'receptor' => ['RutReceptor'],
        ];
    }

    #[DataProvider('provideRutTags')]
    public function testValidateRejectsARutWithAnInvalidVerificationDigit(string $tag): void
    {
        $xml = (string) preg_replace(
            '#<' . $tag . '>(\d+)-[0-9K]</' . $tag . '>#',
            '<' . $tag . '>${1}-0</' . $tag . '>',
            $this->envelopeXml()
        );
        // Los RUT del ejemplo no terminan en 0, así que el cambio es efectivo.
        $this->assertStringContainsString('-0</' . $tag . '>', $xml);

        $this->expectException(DispatcherException::class);
        $this->expectExceptionMessage($tag);
        $this->dispatcher->validate($xml);
    }

    public function testValidateRejectsAnEnvelopeWithoutCaratula(): void
    {
        $this->expectException(XmlQueryException::class);
        $this->dispatcher->validate('<EnvioDTE xmlns="http://www.sii.cl/SiiDte"><SetDTE/></EnvioDTE>');
    }

    public function testValidateAcceptsAResolutionDateOfTheSameDayAsTheSignature(): void
    {
        $xml = $this->envelopeXml();
        $signatureDay = $this->signatureDay($xml);

        $same = (string) preg_replace('#<FchResol>[^<]*</FchResol>#', '<FchResol>' . $signatureDay . '</FchResol>', $xml);

        $validated = $this->dispatcher->validate($same);

        $this->assertSame($signatureDay, $validated->getReader()->requireString('//Caratula/FchResol'));
    }

    public function testValidateRejectsAResolutionDateAfterTheSignature(): void
    {
        $xml = $this->envelopeXml();
        $signatureDay = $this->signatureDay($xml);
        $nextDay = (new DateTimeImmutable($signatureDay))->modify('+1 day')->format('Y-m-d');

        $late = (string) preg_replace('#<FchResol>[^<]*</FchResol>#', '<FchResol>' . $nextDay . '</FchResol>', $xml);

        $this->expectException(DispatcherException::class);
        $this->expectExceptionMessage($nextDay);
        $this->dispatcher->validate($late);
    }

    public function testValidateDoesNotDependOnTheCurrentDate(): void
    {
        // Un sobre firmado en 2020 con resolución de 2019 es válido aunque hoy
        // sea otro día; con resolución posterior a la firma no lo es.
        $xml = (string) preg_replace(
            ['#<TmstFirmaEnv>[^<]*</TmstFirmaEnv>#', '#<FchResol>[^<]*</FchResol>#'],
            ['<TmstFirmaEnv>2020-06-15T10:00:00</TmstFirmaEnv>', '<FchResol>2019-12-31</FchResol>'],
            $this->envelopeXml()
        );
        $validated = $this->dispatcher->validate($xml);
        $this->assertSame('2019-12-31', $validated->getReader()->requireString('//Caratula/FchResol'));

        $late = str_replace('2019-12-31', '2020-06-16', $xml);
        $this->expectException(DispatcherException::class);
        $this->dispatcher->validate($late);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideInvalidDates(): array
    {
        return [
            'resolution day that does not exist' => ['FchResol', '2014-02-31'],
            'resolution not a date' => ['FchResol', 'ayer'],
            'resolution without zeros' => ['FchResol', '2014-8-22'],
            'signature not a timestamp' => ['TmstFirmaEnv', '2026-11-11'],
            'signature with impossible hour' => ['TmstFirmaEnv', '2026-11-11T25:00:00'],
        ];
    }

    #[DataProvider('provideInvalidDates')]
    public function testValidateRejectsDatesThatAreNotValid(string $tag, string $value): void
    {
        $xml = (string) preg_replace(
            '#<' . $tag . '>[^<]*</' . $tag . '>#',
            '<' . $tag . '>' . $value . '</' . $tag . '>',
            $this->envelopeXml()
        );

        $this->expectException(DispatcherException::class);
        $this->expectExceptionMessage($tag);
        $this->dispatcher->validate($xml);
    }

    public function testValidateRejectsMoreDocumentsDeclaredThanTheEnvelopeHas(): void
    {
        $xml = str_replace('<NroDTE>1</NroDTE>', '<NroDTE>2</NroDTE>', $this->envelopeXml());

        $this->expectException(DispatcherException::class);
        $this->expectExceptionMessage('33');
        $this->dispatcher->validate($xml);
    }

    public function testValidateRejectsFewerDocumentsDeclaredThanTheEnvelopeHas(): void
    {
        $envelope = $this->dispatcher->createMany([
            $this->authorizedBag(self::FACTURA),
            $this->authorizedBag('033_factura_afecta/033_002_factura_guia'),
        ]);
        $xml = str_replace('<NroDTE>2</NroDTE>', '<NroDTE>1</NroDTE>', $envelope->requireXmlDocument()->saveXml());

        $this->expectException(DispatcherException::class);
        $this->dispatcher->validate($xml);
    }

    public function testValidateRejectsADocumentTypeThatIsNotDeclared(): void
    {
        $xml = str_replace('<TpoDTE>33</TpoDTE>', '<TpoDTE>34</TpoDTE>', $this->envelopeXml());

        $this->expectException(DispatcherException::class);
        $this->dispatcher->validate($xml);
    }

    public function testValidateRejectsADeclaredTypeWithoutDocuments(): void
    {
        $envelope = $this->dispatcher->createMany([
            $this->authorizedBag(self::FACTURA),
            $this->authorizedBag(self::NOTA_CREDITO),
        ]);
        // Se declara la nota de crédito pero se quita el documento.
        $xml = (string) preg_replace('#<DTE [^>]*>(?:(?!</DTE>).)*<TipoDTE>61</TipoDTE>.*?</DTE>#s', '', $envelope->requireXmlDocument()->saveXml(), 1);
        $this->assertStringNotContainsString('<TipoDTE>61</TipoDTE>', $xml);

        $this->expectException(DispatcherException::class);
        $this->expectExceptionMessage('61');
        $this->dispatcher->validate($xml);
    }

    public function testValidateAddsUpDocumentTypesDeclaredMoreThanOnce(): void
    {
        $envelope = $this->dispatcher->createMany([
            $this->authorizedBag(self::FACTURA),
            $this->authorizedBag('033_factura_afecta/033_002_factura_guia'),
        ]);
        $xml = str_replace(
            '<SubTotDTE><TpoDTE>33</TpoDTE><NroDTE>2</NroDTE></SubTotDTE>',
            '<SubTotDTE><TpoDTE>33</TpoDTE><NroDTE>1</NroDTE></SubTotDTE><SubTotDTE><TpoDTE>33</TpoDTE><NroDTE>1</NroDTE></SubTotDTE>',
            (string) preg_replace('#>\s+<#', '><', $envelope->requireXmlDocument()->saveXml())
        );
        $this->assertSame(2, substr_count($xml, '<SubTotDTE>'));

        $validated = $this->dispatcher->validate($xml);

        $this->assertCount(2, $validated->getReader()->requireReaders('//Caratula/SubTotDTE'));
    }

    public function testLoadXmlFromAStringRebuildsTheEnvelopeData(): void
    {
        $xml = $this->envelopeXml();

        $envelope = $this->dispatcher->loadXml($xml);

        $this->assertSame(TipoSobre::ENVIO_DTE, $envelope->getTipoSobre());
        $this->assertNotNull($envelope->getSobreEnvio());
        $this->assertCount(1, $envelope->requireDocuments());
        $this->assertSame(33, $envelope->requireDocuments()[0]->requireDocument()->getCodigo());
        $this->assertSame('76192083-9', $envelope->requireEmisor()->getRut());
        $this->assertSame('60803000-K', $envelope->requireReceptor()->getRut());
        $this->assertNull($envelope->getMandatario());
        $this->assertNull($envelope->getCertificate());

        $autorizacion = $envelope->requireEmisor()->getAutorizacionDte();
        $this->assertNotNull($autorizacion);
        $this->assertSame('2014-08-22', $autorizacion->getFechaResolucion());
        $this->assertSame(80, $autorizacion->getNumeroResolucion());

        $caratula = ArrayReader::fromArray($envelope->requireCaratula());
        $this->assertSame('76192083-9', $caratula->requireString('RutEmisor'));
        $this->assertSame('60803000-K', $caratula->requireString('RutReceptor'));
        $this->assertSame('2014-08-22', $caratula->requireString('FchResol'));
    }

    public function testLoadXmlFromAnXmlDocumentKeepsThatDocument(): void
    {
        $xmlDocument = $this->envelope()->requireXmlDocument();

        $envelope = $this->dispatcher->loadXml($xmlDocument);

        $this->assertSame($xmlDocument, $envelope->getXmlDocument());
        $this->assertCount(1, $envelope->requireDocuments());
    }

    public function testLoadXmlOfAnEnvelopeWithSeveralDocumentsLoadsAllOfThem(): void
    {
        $envelope = $this->dispatcher->createMany([
            $this->authorizedBag(self::FACTURA),
            $this->authorizedBag(self::NOTA_CREDITO),
        ]);

        $loaded = $this->dispatcher->loadXml($envelope->requireXmlDocument()->saveXml());

        $codes = array_map(
            fn (DocumentBagInterface $bag) => $bag->requireDocument()->getCodigo(),
            $loaded->requireDocuments()
        );
        $this->assertSame([33, 61], $codes);
    }

    public function testLoadXmlGivesTheAuthorizationToTheEmisorOfEveryDocument(): void
    {
        $envelope = $this->dispatcher->createMany([
            $this->authorizedBag(self::FACTURA),
            $this->authorizedBag(self::NOTA_CREDITO),
            $this->authorizedBag('033_factura_afecta/033_002_factura_guia'),
        ]);

        $loaded = $this->dispatcher->loadXml($envelope->requireXmlDocument()->saveXml());

        $this->assertCount(3, $loaded->requireDocuments());
        foreach ($loaded->requireDocuments() as $i => $bag) {
            $this->assertSame(
                ['fecha_resolucion' => '2014-08-22', 'numero_resolucion' => 80],
                $bag->getDocumentAuth(),
                'Document ' . $i . ' of the envelope has no authorization.'
            );
        }
    }

    public function testLoadXmlDoesNotAuthorizeAnIssuerThatIsNotTheOneOfTheCaratula(): void
    {
        $xml = str_replace('<RutEmisor>76192083-9</RutEmisor>', '<RutEmisor>11111111-1</RutEmisor>', $this->envelopeXml());

        $envelope = $this->dispatcher->loadXml($xml);

        $this->assertSame('76192083-9', $envelope->requireEmisor()->getRut());
        $this->assertNull($envelope->requireEmisor()->getAutorizacionDte());
    }

    public function testTheMandatarioOfTheEnvelopeIsTheOwnerOfTheCertificate(): void
    {
        $envelope = $this->envelope();

        $this->assertSame('76192083-9', $envelope->getMandatario()?->getRun());
        $this->assertSame('76192083-9', $envelope->requireCaratula()['RutEnvia']);
    }

    public function testNormalizeKeepsTheMandatarioThatWasAlreadyAssigned(): void
    {
        $bag = $this->authorizedBag(self::FACTURA);
        $envelope = (new DocumentEnvelope())
            ->addDocument($bag)
            ->setCertificate($bag->getCertificate())
            ->setMandatario(new Mandatario('12345678-5'))
        ;

        $this->dispatcher->normalize($envelope);

        $this->assertSame('12345678-5', $envelope->getMandatario()?->getRun());
        $this->assertSame('12345678-5', $envelope->requireCaratula()['RutEnvia']);
    }

    public function testNormalizeKeepsTheEmisorAndReceptorThatWereAlreadyAssigned(): void
    {
        $bag = $this->authorizedBag(self::FACTURA);
        $emisor = new Emisor('11111111-1', autorizacion_dte: new AutorizacionDte('2015-01-01', 5));
        $receptor = new Receptor('22222222-2');
        $envelope = (new DocumentEnvelope())
            ->addDocument($bag)
            ->setCertificate($bag->getCertificate())
            ->setEmisor($emisor)
            ->setReceptor($receptor)
        ;

        $this->dispatcher->normalize($envelope);

        $this->assertSame($emisor, $envelope->getEmisor());
        $this->assertSame($receptor, $envelope->getReceptor());
        $caratula = ArrayReader::fromArray($envelope->requireCaratula());
        $this->assertSame('11111111-1', $caratula->requireString('RutEmisor'));
        $this->assertSame('22222222-2', $caratula->requireString('RutReceptor'));
        $this->assertSame('2015-01-01', $caratula->requireString('FchResol'));
        $this->assertSame(5, $caratula->requireInt('NroResol'));
    }

    public function testNormalizeDoesNotReplaceTheCaratulaThatWasAlreadyAssigned(): void
    {
        $bag = $this->authorizedBag(self::FACTURA);
        $caratula = ['RutEmisor' => '76192083-9', 'TmstFirmaEnv' => date('Y-m-d\TH:i:s')];
        $envelope = (new DocumentEnvelope())
            ->addDocument($bag)
            ->setCertificate($bag->getCertificate())
            ->setCaratula($caratula)
        ;
        $this->dispatcher->normalize($envelope);

        $this->assertSame($caratula, $envelope->getCaratula());
        $this->assertSame(
            '76192083-9',
            $envelope->requireXmlDocument()->getReader()->requireString('//Caratula/RutEmisor')
        );
    }

    public function testSignRejectsACertificateThatIsNotValidAtTheSigningDate(): void
    {
        $bag = $this->authorizedBag(self::FACTURA);
        $envelope = (new DocumentEnvelope())
            ->addDocument($bag)
            ->setCertificate($bag->getCertificate())
            ->setCaratula(['RutEmisor' => '76192083-9', 'TmstFirmaEnv' => '2099-01-01T00:00:00'])
        ;

        $this->expectException(DispatcherException::class);
        $this->expectExceptionMessage('01/01/2099');
        $this->dispatcher->normalize($envelope);
    }

    public function testSignWithoutCaratulaThrows(): void
    {
        // Sin autorización del SII para el emisor no se puede armar la
        // carátula, y con certificado el sobre se debe firmar.
        $bag = $this->buildBag(self::FACTURA);

        $this->expectException(DispatcherException::class);
        $this->dispatcher->create($bag);
    }
}
