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

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractParty;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractSenderHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyEndpoint;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * The reception of envelopes by email, through a real IMAP server: the XML that
 * come in the mails are the documents, and nothing else is.
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
#[Group('docker')]
final class ExchangeEmailReceiverTest extends AbstractExchangeMailTestCase
{
    public function testTheSenderAndTheReceiverAreTheAddressesOfTheMail(): void
    {
        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')]));

        $results = self::receiveAs('beto');

        $this->assertCount(1, $results);
        $envelope = $results[0]->getEnvelope();
        $this->assertSame('EMAIL', $envelope->getSender()->getIdentifier()->getSchemeId());
        $this->assertSame(self::ANA, $envelope->getSender()->getIdentifier()->getValue());
        $this->assertSame('EMAIL', $envelope->getReceiver()->getIdentifier()->getSchemeId());
        $this->assertSame(self::BETO, $envelope->getReceiver()->getIdentifier()->getValue());
        $this->assertNotSame('', $envelope->getBusinessMessageID());
    }

    public function testAMailWithoutAttachmentsIsNotAnEnvelope(): void
    {
        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], []));

        $this->assertSame([], self::receiveAs('beto'));
    }

    public function testOnlyTheXmlAttachmentsAreDocuments(): void
    {
        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], [
            new Attachment(filename: 'invoice.pdf', body: '%PDF-1.4 not really', contentType: 'application/pdf'),
            new Attachment(filename: 'notes.txt', body: 'notes', contentType: 'text/plain'),
            new Attachment(filename: 'invoice.xml', body: '<invoice/>'),
        ]));

        $results = self::receiveAs('beto');

        $this->assertCount(1, $results);
        $this->assertSame(1, $results[0]->getEnvelope()->countDocuments());
        $this->assertSame(['invoice.xml'], self::attachmentNames($results));
    }

    public function testEachXmlAttachmentIsADocumentOfTheSameEnvelope(): void
    {
        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], [
            new Attachment(filename: 'one.xml', body: '<one/>'),
            new Attachment(filename: 'two.xml', body: '<two/>'),
        ]));

        $results = self::receiveAs('beto');

        $this->assertCount(1, $results);
        $this->assertSame(2, $results[0]->getEnvelope()->countDocuments());
        $this->assertSame(['one.xml', 'two.xml'], self::attachmentNames($results));
    }

    public function testTheMailsAreMarkedAsReadByDefault(): void
    {
        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')]));

        $this->assertCount(1, self::receiveAs('beto'));
        $this->assertSame([], self::receiveAs('beto'));
    }

    public function testTheMailsAreKeptUnreadWhenItIsAsked(): void
    {
        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')]));

        $this->assertCount(1, self::receiveAs('beto', ['markAsSeen' => false]));
        $this->assertCount(1, self::receiveAs('beto', ['markAsSeen' => false]));
        $this->assertCount(1, self::receiveAs('beto'));
        $this->assertSame([], self::receiveAs('beto'));
    }

    public function testTheSearchCanBeLimitedByDate(): void
    {
        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')]));
        $tomorrow = (new \DateTimeImmutable('+2 days'))->format('Y-m-d');
        $yesterday = (new \DateTimeImmutable('-2 days'))->format('Y-m-d');

        $this->assertSame([], self::receiveAs('beto', ['criteria' => 'UNSEEN SINCE ' . $tomorrow, 'markAsSeen' => false]));
        $this->assertCount(1, self::receiveAs('beto', ['criteria' => 'UNSEEN SINCE ' . $yesterday]));
    }

    public function testEachUserReadsOnlyItsOwnMailbox(): void
    {
        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')]));

        $this->assertSame([], self::receiveAs('ana'));
        $this->assertCount(1, self::receiveAs('beto'));
    }

    public function testWrongCredentialsAreAnError(): void
    {
        $this->expectException(ExchangeException::class);
        $this->expectExceptionMessage('Could not receive the exchange');

        self::receive(['password' => 'wrong'] + self::server()->imapTransport('beto'));
    }

    public function testAServerThatIsNotThereIsAnError(): void
    {
        $this->expectException(ExchangeException::class);
        $this->expectExceptionMessage('Could not receive the exchange');

        self::receive(['port' => self::closedPort()] + self::server()->imapTransport('beto'));
    }

    public function testWithoutCredentialsNothingIsReceived(): void
    {
        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')]));

        $this->assertSame([], self::receive(['host' => '127.0.0.1', 'port' => self::server()->imapPort]));
        $this->assertCount(1, self::receiveAs('beto'));
    }
}
