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

namespace libredte\lib\Tests\Integration\Package\Billing\Component\Exchange;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
use Derafu\Xml\XmlDocument;
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
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDescuentosRecargosTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDetalleTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeImpuestoAdicionalRetencionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeIvaMntTotalTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeTransporteTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractParty;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractSenderHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyEndpoint;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\TipoDocumentoRespuesta;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use libredte\lib\Core\Package\Billing\Component\Exchange\ExchangeComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeStatus;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildEnvioRecibosJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildRespuestaEnvioJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponseWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Handler\EmailReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Strategy\Email\ImapReceiverStrategy;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\ReceiverWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Handler\EmailSenderHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Strategy\Email\SmtpSenderStrategy;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\SenderWorker;
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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\PersonaNatural;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * The exchange of an EnvioDTE between two contributors, as it happens in Chile:
 *
 *   1. The issuer sends the EnvioDTE by email to the exchange address of the
 *      receiver.
 *   2. The receiver reads it from its mailbox (IMAP), checks it, and builds the
 *      three answers of the process: the acknowledgement of receipt of the
 *      envelope, the commercial result of each DTE and the receipt of goods or
 *      services.
 *   3. The receiver sends the answers by email to the issuer.
 *   4. The issuer reads them from its mailbox and can check them.
 *
 * Every step is real: the documents are stamped and signed, the mails go
 * through an SMTP server and come out of an IMAP server, and the XML that
 * arrives is validated again, so a transport that changes the XML shows up as an
 * invalid signature.
 */
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(ExchangeComponent::class)]
#[CoversClass(AbstractExchangeWorker::class)]
#[CoversClass(AbstractSenderHandler::class)]
#[CoversClass(AbstractReceiverHandler::class)]
#[CoversClass(AbstractParty::class)]
#[CoversClass(SenderWorker::class)]
#[CoversClass(ReceiverWorker::class)]
#[CoversClass(EmailSenderHandler::class)]
#[CoversClass(SmtpSenderStrategy::class)]
#[CoversClass(EmailReceiverHandler::class)]
#[CoversClass(ImapReceiverStrategy::class)]
#[CoversClass(ExchangeBag::class)]
#[CoversClass(ExchangeResult::class)]
#[CoversClass(ExchangeStatus::class)]
#[CoversClass(Envelope::class)]
#[CoversClass(Document::class)]
#[CoversClass(Attachment::class)]
#[CoversClass(Sender::class)]
#[CoversClass(Receiver::class)]
#[CoversClass(PartyEndpoint::class)]
#[CoversClass(PartyIdentifier::class)]
#[CoversClass(DocumentType::class)]
#[CoversClass(ExchangeException::class)]
#[UsesClass(AbstractBuilderStrategy::class)]
#[UsesClass(AbstractDocument::class)]
#[UsesClass(AbstractNormalizerStrategy::class)]
#[UsesClass(AbstractSanitizerStrategy::class)]
#[UsesClass(AbstractValidatorStrategy::class)]
#[UsesClass(DocumentComponent::class)]
#[UsesClass(SobreEnvio::class)]
#[UsesClass(TipoDocumento::class)]
#[UsesClass(CodigoDocumento::class)]
#[UsesClass(TagXmlDocumento::class)]
#[UsesClass(TipoSobre::class)]
#[UsesClass(DocumentBag::class)]
#[UsesClass(DocumentEnvelope::class)]
#[UsesClass(BuilderWorker::class)]
#[UsesClass(DispatcherWorker::class)]
#[UsesClass(DocumentBagManagerWorker::class)]
#[UsesClass(NormalizerWorker::class)]
#[UsesClass(Utils::class)]
#[UsesClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[UsesClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[UsesClass(NormalizeFacturaAfectaJob::class)]
#[UsesClass(FacturaAfectaNormalizerStrategy::class)]
#[UsesTrait(NormalizeDescuentosRecargosTrait::class)]
#[UsesTrait(NormalizeDetalleTrait::class)]
#[UsesTrait(NormalizeImpuestoAdicionalRetencionTrait::class)]
#[UsesTrait(NormalizeIvaMntTotalTrait::class)]
#[UsesTrait(NormalizeTransporteTrait::class)]
#[UsesClass(ParserWorker::class)]
#[UsesClass(JsonParserStrategy::class)]
#[UsesClass(SanitizerWorker::class)]
#[UsesClass(FacturaAfectaSanitizerStrategy::class)]
#[UsesClass(ValidatorWorker::class)]
#[UsesClass(FacturaAfectaValidatorStrategy::class)]
#[UsesClass(AbstractExchangeDocument::class)]
#[UsesClass(ExchangeDocumentBag::class)]
#[UsesClass(DocumentResponseWorker::class)]
#[UsesClass(BuildEnvioRecibosJob::class)]
#[UsesClass(BuildRespuestaEnvioJob::class)]
#[UsesClass(Caf::class)]
#[UsesClass(IdentifierComponent::class)]
#[UsesClass(CreateArrayJob::class)]
#[UsesClass(CreateXmlJob::class)]
#[UsesClass(CafFakerWorker::class)]
#[UsesClass(CafLoaderWorker::class)]
#[UsesClass(AbstractContribuyenteFactory::class)]
#[UsesClass(AutorizacionDte::class)]
#[UsesClass(Contribuyente::class)]
#[UsesClass(Emisor::class)]
#[UsesClass(PersonaNatural::class)]
#[UsesClass(Receptor::class)]
#[UsesClass(EmisorFactory::class)]
#[UsesClass(ReceptorFactory::class)]
#[UsesClass(FakeEmisorProvider::class)]
#[UsesClass(FakeReceptorProvider::class)]
#[UsesClass(RepositorySourceProvider::class)]
#[Group('docker')]
final class ExchangeDteTest extends AbstractExchangeMailTestCase
{
    public function testTheReceiverAnswersTheEnvioDteAndTheIssuerReadsTheAnswers(): void
    {
        $billing = Application::getInstance()->getPackageRegistry()->getBillingPackage();
        $dispatcher = $billing->getDocumentComponent()->getDispatcherWorker();
        $responses = self::exchange()->getDocumentResponseWorker();

        $faker = new CertificateFaker(new CertificateLoader());
        $issuerCertificate = $faker->createFake(self::SENDER_RUT, 'Issuer', 'issuer@example.com');
        $receiverCertificate = $faker->createFake(self::RECEIVER_RUT, 'Receiver', 'receiver@example.com');

        // 1. The issuer builds the EnvioDTE and sends it, with the PDF.
        $dte = $this->createEnvioDte($issuerCertificate);
        $filename = sprintf('EnvioDTE_T%dF%d.xml', $dte['tipo'], $dte['folio']);
        $results = self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], [
            new Attachment(filename: $filename, body: $dte['xml']),
            new Attachment(filename: 'T33F1.pdf', body: '%PDF-1.4 not really', contentType: 'application/pdf'),
        ]));
        $this->assertTrue($results[0]->getStatuses()[0]->isOk());

        // 2. The receiver reads the mail: the EnvioDTE, as it was sent, and
        // nothing else (not the PDF).
        $received = self::receiveAs('beto');
        $this->assertCount(1, $received);
        $this->assertSame([$filename], self::attachmentNames($received));
        $envioDte = self::attachments($received)[0]->getBody();
        $this->assertSame($dte['xml'], $envioDte);
        $dispatcher->validateSchema($envioDte);
        foreach ($dispatcher->validateSignature($envioDte) as $signature) {
            $this->assertTrue($signature->isValid());
        }

        // 3. The receiver builds its answers and sends them to the issuer, one
        // mail for each one.
        $answers = $this->answer($envioDte, $receiverCertificate);
        $envelopes = [];
        foreach ($answers as $name => $xml) {
            $envelopes[] = self::mailEnvelope(
                self::BETO,
                [self::ANA],
                [new Attachment(filename: $name . '.xml', body: $xml)],
                [],
                self::RECEIVER_RUT,
                self::SENDER_RUT
            );
        }
        foreach (self::sendAs('beto', ...$envelopes) as $result) {
            $this->assertTrue($result->getStatuses()[0]->isOk());
        }

        // 4. The issuer reads the three answers, from the receiver.
        $answered = self::receiveAs('ana');
        $this->assertCount(3, $answered);
        foreach ($answered as $result) {
            $this->assertSame(self::BETO, $result->getEnvelope()->getSender()->getIdentifier()->getValue());
            $this->assertSame(self::ANA, $result->getEnvelope()->getReceiver()->getIdentifier()->getValue());
        }
        $this->assertSame(
            ['EnvioRecibos.xml', 'RespuestaRecepcionEnvio.xml', 'RespuestaResultadoDte.xml'],
            self::attachmentNames($answered)
        );

        $bodies = [];
        foreach (self::attachments($answered) as $attachment) {
            $bodies[ValueCaster::requireString($attachment->getFilename())] = $attachment->getBody();
        }

        // Every answer arrives as it was sent, valid, and says what the issuer
        // needs to know: the DTE that it sent was received and accepted.
        foreach ($answers as $name => $xml) {
            $this->assertSame($xml, $bodies[$name . '.xml'], $name);
            $responses->validateSchema($xml);
            $signatures = $responses->validateSignature($bodies[$name . '.xml']);
            $this->assertNotEmpty($signatures);
            foreach ($signatures as $signature) {
                $this->assertTrue($signature->isValid(), $name);
            }
        }

        $recepcion = $this->xml($bodies['RespuestaRecepcionEnvio.xml']);
        $this->assertSame('RespuestaDTE', $recepcion->getName());
        $reader = $recepcion->getReader();
        $this->assertSame(0, $reader->requireAsInt('//RecepcionEnvio/EstadoRecepEnv'));
        $this->assertSame($dte['tipo'], $reader->requireAsInt('//RecepcionDTE/TipoDTE'));
        $this->assertSame($dte['folio'], $reader->requireAsInt('//RecepcionDTE/Folio'));
        $this->assertSame($dte['total'], $reader->requireAsInt('//RecepcionDTE/MntTotal'));
        $this->assertSame($dte['digest'], $reader->requireString('//RecepcionEnvio/Digest'));

        $resultado = $this->xml($bodies['RespuestaResultadoDte.xml']);
        $this->assertSame('RespuestaDTE', $resultado->getName());
        $reader = $resultado->getReader();
        $this->assertSame(0, $reader->requireAsInt('//ResultadoDTE/EstadoDTE'));
        $this->assertSame($dte['folio'], $reader->requireAsInt('//ResultadoDTE/Folio'));

        $recibos = $this->xml($bodies['EnvioRecibos.xml']);
        $this->assertSame('EnvioRecibos', $recibos->getName());
        $reader = $recibos->getReader();
        $this->assertSame($dte['tipo'], $reader->requireAsInt('//DocumentoRecibo/TipoDoc'));
        $this->assertSame($dte['folio'], $reader->requireAsInt('//DocumentoRecibo/Folio'));
        $this->assertSame(self::RECEIVER_RUT, $reader->requireString('//Recibo/DocumentoRecibo/RUTRecep'));

        // The mailboxes are empty: everything was read.
        $this->assertSame([], self::receiveAs('beto'));
        $this->assertSame([], self::receiveAs('ana'));
    }

    /**
     * Builds, stamps and signs a DTE, and wraps it in an EnvioDTE signed by
     * the issuer.
     *
     * @return array{xml: string, tipo: int, folio: int, total: int, digest: string}
     */
    private function createEnvioDte(CertificateInterface $certificate): array
    {
        $billing = Application::getInstance()->getPackageRegistry()->getBillingPackage();
        $builder = $billing->getDocumentComponent()->getBuilderWorker();
        $dispatcher = $billing->getDocumentComponent()->getDispatcherWorker();
        $cafFaker = $billing->getIdentifierComponent()->getCafFakerWorker();

        $file = dirname(__DIR__, 6) . '/fixtures/yaml/documentos_ok/033_factura_afecta/033_001_simple.yaml';
        $data = ValueType::map()->parse(
            Yaml::parse(ValueCaster::requireString(file_get_contents($file))),
            '',
            basename($file)
        );
        unset($data['Test']);

        // Build, then stamp with a CAF and sign.
        $bag = new DocumentBag(inputData: $data);
        $builder->build($bag);
        $tipo = ValueCaster::requireInt($bag->requireTipoDocumento()->getCodigo());
        $caf = $cafFaker->create(
            $bag->requireEmisor(),
            $tipo,
            $bag->requireDocument()->getFolio()
        );
        $bag = $bag->withCaf($caf);
        $builder->build($bag);
        $bag = $bag->withCertificate($certificate);
        $builder->build($bag);

        // Wrap in the EnvioDTE.
        $bag->requireEmisor()->setAutorizacionDte(new AutorizacionDte('2019-12-23'));
        $envelope = new DocumentEnvelope();
        $envelope->addDocument($bag);
        $envelope->setCertificate($certificate);
        $dispatcher->normalize($envelope);
        $xml = $envelope->requireXmlDocument()->saveXml();

        $document = $bag->requireDocument();

        return [
            'xml' => $xml,
            'tipo' => $tipo,
            'folio' => $document->getFolio(),
            'total' => ValueCaster::requireInt($document->getMontoTotal()),
            'digest' => $envelope->requireXmlDocument()->getReader()->requireString(
                '/EnvioDTE/Signature/SignedInfo/Reference/DigestValue'
            ),
        ];
    }

    /**
     * The answers of the receiver to an EnvioDTE that it accepts, built from
     * the XML that it received: the acknowledgement of receipt, the receipt of
     * goods or services and the commercial result.
     *
     * @return array<string, string> XML of each answer, by name.
     */
    private function answer(string $envioDte, CertificateInterface $certificate): array
    {
        $billing = Application::getInstance()->getPackageRegistry()->getBillingPackage();
        $dispatcher = $billing->getDocumentComponent()->getDispatcherWorker();
        $responses = self::exchange()->getDocumentResponseWorker();

        $envelope = $dispatcher->loadXml($envioDte);
        $sobre = $envelope->requireSobreEnvio();
        $caratula = [
            'RutResponde' => self::RECEIVER_RUT,
            'RutRecibe' => $sobre->getRutEmisor(),
            'NmbContacto' => 'Receiver',
            'MailContacto' => $certificate->getEmail(),
        ];

        $recepcionDte = [];
        $recibos = [];
        $resultados = [];
        foreach ($envelope->requireDocuments() as $number => $bag) {
            $document = $bag->requireDocument();
            $common = [
                'Folio' => $document->getFolio(),
                'FchEmis' => $document->getFechaEmision(),
                'RUTEmisor' => $document->getRutEmisor(),
                'RUTRecep' => $document->getRutReceptor(),
                'MntTotal' => $document->getMontoTotal(),
            ];
            $recepcionDte[] = ['TipoDTE' => $document->getCodigo()] + $common + [
                'EstadoRecepDTE' => 0,
                'RecepDTEGlosa' => 'DTE Recibido OK',
            ];
            $recibos[] = ['TipoDoc' => $document->getCodigo()] + $common + [
                'Recinto' => 'Oficina central',
                'RutFirma' => $certificate->getId(),
            ];
            $resultados[] = ['TipoDTE' => $document->getCodigo()] + $common + [
                'CodEnvio' => $number + 1,
                'EstadoDTE' => 0,
                'EstadoDTEGlosa' => 'ACEPTADO OK',
            ];
        }

        $recepcion = $responses->buildRespuestaEnvio(new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::RESPUESTA_ENVIO,
            caratula: $caratula + ['IdRespuesta' => 1],
            data: ['recepcion_envio' => [[
                'NmbEnvio' => 'EnvioDTE.xml',
                'CodEnvio' => 1,
                'EnvioDTEID' => $sobre->getId(),
                'Digest' => $sobre->getXmlDocument()->getReader()->requireString(
                    '/EnvioDTE/Signature/SignedInfo/Reference/DigestValue'
                ),
                'RutEmisor' => $sobre->getRutEmisor(),
                'RutReceptor' => self::RECEIVER_RUT,
                'EstadoRecepEnv' => 0,
                'RecepEnvGlosa' => 'Envío Recibido Conforme',
                'NroDTE' => count($recepcionDte),
                'RecepcionDTE' => $recepcionDte,
            ]]],
            certificate: $certificate,
        ));
        $envioRecibos = $responses->buildEnvioRecibos(new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::ENVIO_RECIBOS,
            caratula: $caratula,
            data: $recibos,
            certificate: $certificate,
        ));
        $resultado = $responses->buildRespuestaEnvio(new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::RESPUESTA_ENVIO,
            caratula: $caratula + ['IdRespuesta' => 2],
            data: ['resultado_dte' => $resultados],
            certificate: $certificate,
        ));

        return [
            'RespuestaRecepcionEnvio' => $recepcion->saveXml(),
            'EnvioRecibos' => $envioRecibos->saveXml(),
            'RespuestaResultadoDte' => $resultado->saveXml(),
        ];
    }

    private function xml(string $content): XmlDocument
    {
        $document = new XmlDocument();
        $document->loadXml($content);

        return $document;
    }
}
