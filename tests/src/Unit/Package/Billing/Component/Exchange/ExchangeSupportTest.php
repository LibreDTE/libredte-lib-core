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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Exchange;

use DateTimeImmutable;
use DateTimeZone;
use Derafu\Array\ArrayReader;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Container\Bag;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use Derafu\Xml\XmlDocument;
use Exception;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractParty;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\EnvioRecibos;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyEndpoint;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\ProcessType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\TipoDocumentoRespuesta;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeStatus;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Value objects of the exchange component: attachments, documents, parties,
 * envelopes, statuses, results and the enums that describe them.
 */
#[CoversClass(EnvioRecibos::class)]
#[CoversClass(Attachment::class)]
#[CoversClass(Document::class)]
#[CoversClass(Envelope::class)]
#[CoversClass(ExchangeResult::class)]
#[CoversClass(ExchangeStatus::class)]
#[CoversClass(ExchangeDocumentBag::class)]
#[CoversClass(AbstractParty::class)]
#[CoversClass(Sender::class)]
#[CoversClass(Receiver::class)]
#[CoversClass(PartyEndpoint::class)]
#[CoversClass(PartyIdentifier::class)]
#[CoversClass(DocumentType::class)]
#[CoversClass(ProcessType::class)]
#[CoversClass(ExchangeException::class)]
class ExchangeSupportTest extends TestCase
{
    private function sender(): Sender
    {
        return (new Sender(new PartyIdentifier('76192083-9')))
            ->addEndpoint(new PartyEndpoint('ventas@example.com'))
        ;
    }

    private function receiver(): Receiver
    {
        return new Receiver(new PartyIdentifier('60803000-K'));
    }

    // Attachment.

    public function testAttachmentGuessesTheContentTypeFromTheExtension(): void
    {
        $attachment = new Attachment('<DTE/>', 'dte.xml');

        $this->assertStringEndsWith('/xml', $attachment->getContentType());
        $this->assertSame('dte.xml', $attachment->getFilename());
    }

    public function testAttachmentWithAnExplicitContentTypeKeepsIt(): void
    {
        $attachment = new Attachment('%PDF', 'factura.bin', 'application/pdf');

        $this->assertSame('application/pdf', $attachment->getContentType());
    }

    public function testAttachmentWithAnUnknownExtensionAndNoContentTypeThrows(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('dte.zzzunknown');
        new Attachment('x', 'dte.zzzunknown');
    }

    public function testAttachmentSizeIsTheLengthOfTheBodyInBytes(): void
    {
        $this->assertSame(strlen('ñandú'), (new Attachment('ñandú', 'a.txt'))->getSize());
        $this->assertSame(0, (new Attachment('', 'a.txt'))->getSize());
    }

    public function testAttachmentSizeCanBeInformed(): void
    {
        $this->assertSame(999, (new Attachment('abc', 'a.txt', size: 999))->getSize());
    }

    public function testAttachmentToArrayAndJsonSerialize(): void
    {
        $attachment = new Attachment('<DTE/>', 'dte.xml');

        $array = $attachment->toArray();
        $this->assertSame('<DTE/>', $array['data']);
        $this->assertSame('dte.xml', $array['name']);
        $this->assertSame($attachment->getContentType(), $array['type']);
        $this->assertSame(6, $array['size']);

        $json = $attachment->jsonSerialize();
        $this->assertSame(base64_encode('<DTE/>'), $json['data']);
        $this->assertSame($array['name'], $json['name']);
        $this->assertSame($array['size'], $json['size']);
    }

    // Document.

    public function testDocumentDefaults(): void
    {
        $document = new Document();

        $this->assertSame(DocumentType::B2B, $document->getType());
        $this->assertSame('', $document->getContent());
        $this->assertSame(0, $document->getContentSize());
        $this->assertSame([], $document->getAttachments());
        $this->assertSame([], $document->getMetadata()->all());
    }

    public function testDocumentIdIsGeneratedOnceAndStoredInTheMetadata(): void
    {
        $document = new Document();

        $id = $document->getID();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id);
        $this->assertSame($id, $document->getID());
        $this->assertSame($id, $document->getMetadata()->all()['id']);
        $this->assertNotSame($id, (new Document())->getID());
    }

    public function testDocumentIdComesFromTheMetadataWhenItIsThere(): void
    {
        $document = new Document(metadata: ['id' => 'mi-id']);

        $this->assertSame('mi-id', $document->getID());
    }

    public function testDocumentContentSizeCountsBytes(): void
    {
        $this->assertSame(strlen('ñandú'), (new Document('ñandú'))->getContentSize());
    }

    public function testDocumentMetadataCanBeAddedAndReplaced(): void
    {
        $document = new Document(metadata: ['a' => 1]);

        $this->assertSame($document, $document->addMetadata('b', 2));
        $this->assertSame(['a' => 1, 'b' => 2], $document->getMetadata()->all());

        $bag = new Bag(['x' => 9]);
        $this->assertSame($document, $document->setMetadata($bag));
        $this->assertSame($bag, $document->getMetadata());
        $this->assertSame(['x' => 9], $document->getMetadata()->all());
    }

    public function testDocumentAttachmentsAreKeptInOrder(): void
    {
        $one = new Attachment('1', 'one.txt');
        $two = new Attachment('2', 'two.txt');
        $document = new Document(attachments: [$one]);

        $this->assertSame($document, $document->addAttachment($two));
        $this->assertSame([$one, $two], $document->getAttachments());
    }

    public function testDocumentToArrayAndJsonSerialize(): void
    {
        $document = new Document('<xml/>', [new Attachment('x', 'a.txt')], DocumentType::INVOICE, ['k' => 'v']);

        $array = $document->toArray();
        $this->assertSame($document->getID(), $array['id']);
        $this->assertSame(DocumentType::INVOICE->value, $array['type']);
        $this->assertSame('<xml/>', $array['content']);
        $this->assertSame('x', $array['attachments'][0]['data']);
        $this->assertSame('v', $array['metadata']['k']);

        $json = $document->jsonSerialize();
        $this->assertSame($array['id'], $json['id']);
        $this->assertSame(base64_encode('<xml/>'), $json['content']);
        $this->assertSame(base64_encode('x'), ArrayReader::fromArray($json)->requireString('attachments.0.data'));
        $this->assertJson((string) json_encode($document));
    }

    // Party.

    public function testPartyEndpointIdentification(): void
    {
        $email = new PartyEndpoint('ventas@example.com');
        $other = new PartyEndpoint('https://example.com/dte', 'URL');

        $this->assertSame('EMAIL:ventas@example.com', $email->getId());
        $this->assertSame('EMAIL', $email->getSchemeId());
        $this->assertSame('Correo electrónico', $email->getSchemeName());
        $this->assertSame('URL', $other->getSchemeName());
        $this->assertSame('URL:https://example.com/dte', $other->getId());
        $this->assertSame([
            'id' => 'EMAIL:ventas@example.com',
            'value' => 'ventas@example.com',
            'scheme_id' => 'EMAIL',
            'scheme_name' => 'Correo electrónico',
        ], $email->toArray());
        $this->assertSame($email->toArray(), $email->jsonSerialize());
    }

    public function testPartyIdentifierIdentification(): void
    {
        $rut = new PartyIdentifier('76192083-9');
        $email = new PartyIdentifier('a@example.com', 'EMAIL');
        $other = new PartyIdentifier('X-1', 'XX');

        $this->assertSame('CL-RUT:76192083-9', $rut->getId());
        $this->assertSame('Rol Único Tributario (RUT) de Chile', $rut->getSchemeName());
        $this->assertSame('CL-SII', $rut->getAuthority());
        $this->assertSame('Correo electrónico', $email->getSchemeName());
        $this->assertSame('INTERNET', $email->getAuthority());
        $this->assertSame('XX', $other->getSchemeName());
        $this->assertSame('XX', $other->getAuthority());
        $this->assertSame([
            'id' => 'CL-RUT:76192083-9',
            'value' => '76192083-9',
            'scheme_id' => 'CL-RUT',
            'scheme_name' => 'Rol Único Tributario (RUT) de Chile',
            'authority' => 'CL-SII',
        ], $rut->toArray());
        $this->assertSame($rut->toArray(), $rut->jsonSerialize());
    }

    public function testPartyEndpointsAndEmails(): void
    {
        $party = new Sender(new PartyIdentifier('76192083-9'), [new PartyEndpoint('a@example.com')]);
        $party->addEndpoint(new PartyEndpoint('https://example.com', 'URL'));
        $party->addEndpoint(new PartyEndpoint('b@example.com:Cliente B'));

        $this->assertCount(3, $party->getEndpoints());
        $this->assertSame('CL-RUT:76192083-9', $party->getIdentifier()->getId());

        $emails = $party->getEmails();
        $this->assertCount(2, $emails);
        $this->assertSame('a@example.com', $emails[0]->getAddress());
        $this->assertSame('', $emails[0]->getName());
        $this->assertSame('b@example.com', $emails[1]->getAddress());
        $this->assertSame('Cliente B', $emails[1]->getName());
    }

    public function testPartyWithoutEmailEndpointsHasNoEmails(): void
    {
        $this->assertSame([], (new Receiver(new PartyIdentifier('60803000-K')))->getEmails());
    }

    public function testPartyToArrayAndJsonSerialize(): void
    {
        $sender = $this->sender();

        $this->assertSame([
            'identifier' => $sender->getIdentifier()->toArray(),
            'endpoints' => [['id' => 'EMAIL:ventas@example.com', 'value' => 'ventas@example.com', 'scheme_id' => 'EMAIL', 'scheme_name' => 'Correo electrónico']],
        ], $sender->toArray());
        $this->assertSame($sender->toArray(), $sender->jsonSerialize());
    }

    // Envelope.

    public function testEnvelopeDefaults(): void
    {
        $before = new DateTimeImmutable('-2 seconds');
        $envelope = new Envelope($this->sender(), $this->receiver());

        $this->assertSame(DocumentType::B2B, $envelope->getDocumentType());
        $this->assertSame(ProcessType::BILLING, $envelope->getProcess());
        $this->assertNull($envelope->getOriginalBusinessMessageID());
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $envelope->getBusinessMessageID());
        $this->assertGreaterThanOrEqual($before, $envelope->getCreationDateAndTime());
        $this->assertLessThanOrEqual(new DateTimeImmutable('+2 seconds'), $envelope->getCreationDateAndTime());
        $this->assertSame([], $envelope->getMetadata()->all());
    }

    public function testEnvelopeKeepsWhatItIsGiven(): void
    {
        $sender = $this->sender();
        $receiver = $this->receiver();
        $date = new DateTimeImmutable('2026-03-04 10:20:30');

        $envelope = new Envelope(
            $sender,
            $receiver,
            DocumentType::INVOICE,
            ProcessType::PAYMENT,
            'msg-1',
            'msg-0',
            $date,
            [new Document(type: DocumentType::INVOICE)],
            ['k' => 'v'],
        );

        $this->assertSame($sender, $envelope->getSender());
        $this->assertSame($receiver, $envelope->getReceiver());
        $this->assertSame(DocumentType::INVOICE, $envelope->getDocumentType());
        $this->assertSame(ProcessType::PAYMENT, $envelope->getProcess());
        $this->assertSame('msg-1', $envelope->getBusinessMessageID());
        $this->assertSame('msg-0', $envelope->getOriginalBusinessMessageID());
        $this->assertSame($date, $envelope->getCreationDateAndTime());
        $this->assertSame(1, $envelope->countDocuments());
        $this->assertSame(['k' => 'v'], $envelope->getMetadata()->all());
        $this->assertSame($envelope, $envelope->addMetadata('z', 1));
        $this->assertSame(['k' => 'v', 'z' => 1], $envelope->getMetadata()->all());
    }

    public function testEnvelopeRejectsADocumentOfAnotherType(): void
    {
        $envelope = new Envelope($this->sender(), $this->receiver(), DocumentType::INVOICE);

        $this->expectException(ExchangeException::class);
        $this->expectExceptionMessage(DocumentType::ORDER->getID());
        $envelope->addDocument(new Document(type: DocumentType::ORDER));
    }

    public function testEnvelopeRejectsADocumentOfAnotherTypeInTheConstructorToo(): void
    {
        $this->expectException(ExchangeException::class);
        new Envelope($this->sender(), $this->receiver(), DocumentType::INVOICE, documents: [new Document()]);
    }

    public function testEnvelopeToArrayAndJsonSerialize(): void
    {
        $date = new DateTimeImmutable('2026-03-04 10:20:30', new DateTimeZone('America/Santiago'));
        $document = new Document('<xml/>', type: DocumentType::INVOICE);
        $envelope = new Envelope(
            $this->sender(),
            $this->receiver(),
            DocumentType::INVOICE,
            ProcessType::BILLING,
            'msg-1',
            null,
            $date,
            [$document],
            ['k' => 'v'],
        );

        $array = $envelope->toArray();
        $this->assertSame($envelope->getSender()->toArray(), $array['sender']);
        $this->assertSame($envelope->getReceiver()->toArray(), $array['receiver']);
        $this->assertSame(DocumentType::INVOICE->value, $array['document_type']);
        $this->assertSame(ProcessType::BILLING->value, $array['process']);
        $this->assertSame('msg-1', $array['business_message_id']);
        $this->assertNull($array['original_business_message_id']);
        $this->assertSame($date->format(DATE_ATOM), $array['creation_date_and_time']);
        $this->assertSame([$document->toArray()], $array['documents']);
        $this->assertSame(['k' => 'v'], $array['metadata']);

        $json = $envelope->jsonSerialize();
        $this->assertSame($array['business_message_id'], $json['business_message_id']);
        $this->assertSame($array['creation_date_and_time'], $json['creation_date_and_time']);
        $this->assertSame([$document->jsonSerialize()], $json['documents']);
        $this->assertJson((string) json_encode($envelope));
    }

    // Status and result.

    public function testStatusWithoutErrorIsOk(): void
    {
        $status = new ExchangeStatus('smtp', metadata: ['to' => 'a@example.com']);

        $this->assertTrue($status->isOk());
        $this->assertFalse($status->hasError());
        $this->assertNull($status->getError());
        $this->assertSame('smtp', $status->getStrategy());
        $this->assertSame(['strategy' => 'smtp', 'error' => null, 'metadata' => ['to' => 'a@example.com']], $status->toArray());
        $this->assertSame($status->toArray(), $status->jsonSerialize());
    }

    public function testStatusWithAnErrorIsNotOkAndSerializesTheMessage(): void
    {
        $status = new ExchangeStatus('smtp', new Exception('no hay conexión'));

        $this->assertFalse($status->isOk());
        $this->assertTrue($status->hasError());
        $this->assertSame('no hay conexión', $status->getError()?->getMessage());
        $this->assertSame(['message' => 'no hay conexión'], $status->toArray()['error']);
    }

    public function testStatusErrorCanBeSetAfterwardsAndMetadataAdded(): void
    {
        $status = new ExchangeStatus('imap');
        $this->assertTrue($status->isOk());

        $this->assertSame($status, $status->setError(new Exception('falló')));
        $this->assertSame($status, $status->addMetadata('intentos', 3));

        $this->assertFalse($status->isOk());
        $this->assertSame(['intentos' => 3], $status->getMetadata()->all());
        $this->assertSame($status, $status->setMetadata(['otro' => 1]));
        $this->assertSame(['otro' => 1], $status->getMetadata()->all());
    }

    public function testResultKeepsOneStatusPerStrategy(): void
    {
        $envelope = new Envelope($this->sender(), $this->receiver());
        $result = new ExchangeResult($envelope, ['k' => 'v']);
        $first = new ExchangeStatus('smtp');
        $second = new ExchangeStatus('imap', new Exception('x'));

        $this->assertSame($result, $result->addStatus($first)->addStatus($second));

        $this->assertSame($envelope, $result->getEnvelope());
        $this->assertSame(['smtp', 'imap'], $result->getStrategies());
        $this->assertSame([$first, $second], $result->getStatuses());
    }

    public function testResultReplacesTheStatusOfTheSameStrategy(): void
    {
        $result = new ExchangeResult(new Envelope($this->sender(), $this->receiver()));
        $failed = new ExchangeStatus('smtp', new Exception('x'));
        $retried = new ExchangeStatus('smtp');

        $result->addStatus($failed)->addStatus($retried);

        $this->assertSame([$retried], $result->getStatuses());
        $this->assertSame(['smtp'], $result->getStrategies());
    }

    public function testResultToArrayAndJsonSerialize(): void
    {
        $envelope = new Envelope($this->sender(), $this->receiver(), businessMessageID: 'msg-1');
        $result = (new ExchangeResult($envelope, ['k' => 'v']))
            ->addStatus(new ExchangeStatus('smtp'))
            ->addMetadata('z', 1)
        ;

        $expected = [
            'envelope' => 'msg-1',
            'statuses' => [['strategy' => 'smtp', 'error' => null, 'metadata' => []]],
            'metadata' => ['k' => 'v', 'z' => 1],
        ];
        $this->assertSame($expected, $result->toArray());
        $this->assertSame($expected, $result->jsonSerialize());
        $this->assertSame($result, $result->setMetadata([]));
        $this->assertSame([], $result->getMetadata()->all());
    }

    // Response document bag.

    public function testExchangeDocumentBagDefaultsAndSetters(): void
    {
        $bag = new ExchangeDocumentBag(TipoDocumentoRespuesta::ENVIO_RECIBOS);

        $this->assertSame(TipoDocumentoRespuesta::ENVIO_RECIBOS, $bag->getTipo());
        $this->assertSame([], $bag->getCaratula());
        $this->assertSame([], $bag->getData());
        $this->assertNull($bag->getCertificate());
        $this->assertNull($bag->getDocument());

        $this->assertSame($bag, $bag->setCaratula(['a' => 1])->setData(['b' => 2]));
        $this->assertSame(['a' => 1], $bag->getCaratula());
        $this->assertSame(['b' => 2], $bag->getData());
    }

    public function testMetadataCanBeSetFromAnExistingBag(): void
    {
        $bag = new Bag(['x' => 9]);

        $envelope = new Envelope($this->sender(), $this->receiver());
        $this->assertSame($envelope, $envelope->setMetadata($bag));
        $this->assertSame($bag, $envelope->getMetadata());

        $result = new ExchangeResult($envelope);
        $this->assertSame($result, $result->setMetadata($bag));
        $this->assertSame($bag, $result->getMetadata());

        $status = new ExchangeStatus('smtp');
        $this->assertSame($status, $status->setMetadata($bag));
        $this->assertSame($bag, $status->getMetadata());
    }

    public function testExchangeDocumentBagKeepsTheCertificateAndTheDocument(): void
    {
        $bag = new ExchangeDocumentBag(TipoDocumentoRespuesta::ENVIO_RECIBOS);
        $certificate = (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9');
        $xml = new XmlDocument();
        $xml->loadXml('<EnvioRecibos/>');
        $document = new EnvioRecibos($xml);

        $this->assertSame($bag, $bag->setCertificate($certificate)->setDocument($document));
        $this->assertSame($certificate, $bag->getCertificate());
        $this->assertSame($document, $bag->getDocument());
    }

    // Enums.

    /**
     * @return array<string, array{int, DocumentType|null}>
     */
    public static function provideDteCodes(): array
    {
        return [
            'factura' => [33, DocumentType::INVOICE],
            'factura exenta' => [34, DocumentType::INVOICE],
            'boleta' => [39, DocumentType::INVOICE],
            'guia' => [52, DocumentType::DISPATCH_ADVICE],
            'nota debito' => [56, DocumentType::DEBIT_NOTE],
            'nota credito' => [61, DocumentType::CREDIT_NOTE],
            'factura exportacion' => [110, DocumentType::INVOICE],
            'nota debito exportacion' => [111, DocumentType::DEBIT_NOTE],
            'nota credito exportacion' => [112, DocumentType::CREDIT_NOTE],
            'unknown' => [999, null],
            'zero' => [0, null],
        ];
    }

    #[DataProvider('provideDteCodes')]
    public function testDocumentTypeFromDteCode(int $code, ?DocumentType $expected): void
    {
        $this->assertSame($expected, DocumentType::tryFromDTE($code));
    }

    public function testDocumentTypeIdIsItsUrn(): void
    {
        foreach (DocumentType::cases() as $type) {
            $this->assertSame($type->value, $type->getID());
            $this->assertStringStartsWith('urn:fdc:libredte.cl:2025:doc:', $type->getID());
        }
    }

    public function testDocumentTypeReferenceCodes(): void
    {
        $this->assertSame('INV', DocumentType::INVOICE->getReferenceCode());
        $this->assertSame('052', DocumentType::DISPATCH_ADVICE->getReferenceCode());
        $this->assertSame('801', DocumentType::ORDER->getReferenceCode());
        $this->assertNull(DocumentType::B2B->getReferenceCode());
        $this->assertNull(DocumentType::BOOK->getReferenceCode());
    }

    public function testDocumentTypeIsValidOnlyForItsProcesses(): void
    {
        $this->assertTrue(DocumentType::INVOICE->isValidForProcess(ProcessType::BILLING));
        $this->assertFalse(DocumentType::INVOICE->isValidForProcess(ProcessType::PAYMENT));
        $this->assertTrue(DocumentType::QUOTATION->isValidForProcess(ProcessType::SOURCING));
        $this->assertFalse(DocumentType::ORDER->isValidForProcess(ProcessType::BILLING));
        $this->assertTrue(DocumentType::B2B->isValidForProcess(ProcessType::FULFILLMENT));
        $this->assertFalse(DocumentType::B2C->isValidForProcess(ProcessType::FULFILLMENT));
    }

    public function testEveryDocumentTypeIsValidForAtLeastOneProcess(): void
    {
        foreach (DocumentType::cases() as $type) {
            $valid = array_filter(ProcessType::cases(), fn (ProcessType $process) => $type->isValidForProcess($process));
            $this->assertNotEmpty($valid, $type->name);
        }
    }

    public function testProcessTypeIdIsItsUrn(): void
    {
        foreach (ProcessType::cases() as $process) {
            $this->assertSame($process->value, $process->getID());
            $this->assertMatchesRegularExpression('/^urn:fdc:libredte\.cl:2025:(prac|poacc|ctc):[a-z]+$/', $process->getID());
        }
    }
}
