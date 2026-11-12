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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Exchange;

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractParty;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractSenderHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyEndpoint;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\ProcessType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use libredte\lib\Core\Package\Billing\Component\Exchange\ExchangeComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeStatus;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Handler\EmailReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Strategy\Email\ImapReceiverStrategy;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\ReceiverWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Handler\EmailSenderHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Strategy\Email\SmtpSenderStrategy;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\SenderWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

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
class EmailWorkersWithoutServerTest extends TestCase
{
    private const SENDER_RUT = '76192083-9';

    private const RECEIVER_RUT = '60803000-K';

    private function exchange(): ExchangeComponentInterface
    {
        return Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getExchangeComponent()
        ;
    }

    /**
     * Puerto en el que no escucha nadie.
     */
    private function closedPort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($socket);
        $name = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr((string) strrchr($name, ':'), 1);
    }

    /**
     * @param list<string> $to
     * @param array<string, mixed> $metadata
     */
    private function envelope(
        ?string $from,
        array $to,
        Document $document,
        array $metadata = [],
        DocumentType $type = DocumentType::B2B,
        ProcessType $process = ProcessType::BILLING,
    ): Envelope {
        $sender = new Sender(new PartyIdentifier(self::SENDER_RUT));
        if ($from !== null) {
            $sender->addEndpoint(new PartyEndpoint($from));
        }
        $receiver = new Receiver(new PartyIdentifier(self::RECEIVER_RUT));
        foreach ($to as $address) {
            $receiver->addEndpoint(new PartyEndpoint($address));
        }

        $envelope = new Envelope(sender: $sender, receiver: $receiver, documentType: $type, process: $process);
        $envelope->addDocument($document);
        foreach ($metadata as $key => $value) {
            $envelope->addMetadata($key, $value);
        }

        return $envelope;
    }

    private function document(): Document
    {
        return new Document(attachments: [new Attachment(filename: 'a.xml', body: '<a/>')]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function bag(array $options, Envelope ...$envelopes): ExchangeBag
    {
        $bag = new ExchangeBag($options);
        foreach ($envelopes as $envelope) {
            $bag->addEnvelope($envelope);
        }

        return $bag;
    }

    /**
     * Transporte de un servidor que no existe.
     *
     * @return array<string, mixed>
     */
    private function transportWithoutServer(): array
    {
        return ['host' => '127.0.0.1', 'port' => $this->closedPort(), 'username' => 'ana', 'password' => 'secreto'];
    }

    // Lo que el sender decide no enviar.

    public function testAnEnvelopeOfADocumentThatIsNotSentByMailIsSkipped(): void
    {
        $envelope = $this->envelope(
            'ana@example.com',
            ['beto@example.com'],
            new Document(type: DocumentType::REPORT),
            type: DocumentType::REPORT,
            process: ProcessType::REPORTING,
        );
        $bag = $this->bag(['transport' => $this->transportWithoutServer()], $envelope);

        $results = $this->exchange()->getSenderWorker()->handle($bag);

        $this->assertSame([], $results);
    }

    public function testWithoutTransportNothingIsSent(): void
    {
        $envelope = $this->envelope('ana@example.com', ['beto@example.com'], $this->document());

        $results = $this->exchange()->getSenderWorker()->handle($this->bag([], $envelope));

        $this->assertSame([], $results);
    }

    public function testWithoutTransportNothingIsReceived(): void
    {
        $results = $this->exchange()->getReceiverWorker()->handle(new ExchangeBag([]));

        $this->assertSame([], $results);
    }

    // Errores del envío.

    public function testAnEnvelopeWhoseReceiverHasNoEmailCannotBeSent(): void
    {
        $envelope = $this->envelope('ana@example.com', [], $this->document());
        $bag = $this->bag(['transport' => $this->transportWithoutServer()], $envelope);

        try {
            $this->exchange()->getSenderWorker()->send($bag);
            $this->fail('An ExchangeException was expected.');
        } catch (ExchangeException $e) {
            $this->assertStringContainsString('Could not send the exchange', $e->getMessage());
            $this->assertStringContainsString('at least one identifier with EMAIL scheme', $e->getMessage());
            $this->assertInstanceOf(ExchangeException::class, $e->getPrevious());
        }
    }

    public function testAnEnvelopeWhoseSenderHasNoEmailCannotBeSent(): void
    {
        $envelope = $this->envelope(null, ['beto@example.com'], $this->document());
        $bag = $this->bag(['transport' => $this->transportWithoutServer()], $envelope);

        $this->expectException(ExchangeException::class);
        $this->expectExceptionMessage('The sender must include an identifier with EMAIL scheme');
        $this->exchange()->getSenderWorker()->send($bag);
    }

    public function testASendWithoutCredentialsIsAnError(): void
    {
        $envelope = $this->envelope('ana@example.com', ['beto@example.com'], $this->document());
        $bag = $this->bag(['transport' => ['host' => '127.0.0.1', 'port' => $this->closedPort()]], $envelope);

        $this->expectException(ExchangeException::class);
        $this->expectExceptionMessage('SMTP username and password must be specified');
        $this->exchange()->getSenderWorker()->send($bag);
    }

    public function testAServerThatIsNotThereLeavesTheErrorInTheStatusAfterBuildingTheWholeMail(): void
    {
        // Un correo con texto, HTML, el contenido del documento y un adjunto.
        $envelope = $this->envelope(
            'ana@example.com',
            ['beto@example.com'],
            new Document('contenido del documento', [new Attachment(filename: 'a.xml', body: '<a/>')]),
            ['subject' => 'Documentos', 'text' => 'Texto', 'html' => '<p>Texto</p>', 'cc' => ['copia@example.com']],
        );
        $bag = $this->bag(['transport' => $this->transportWithoutServer()], $envelope);

        $results = $this->exchange()->getSenderWorker()->handle($bag);

        $this->assertCount(1, $results);
        $status = $results[0]->getStatuses()[0];
        $this->assertSame('email.smtp', $status->getStrategy());
        $this->assertFalse($status->isOk());
        $this->assertStringContainsString('127.0.0.1', $status->getError()?->getMessage() ?? '');
    }
}
