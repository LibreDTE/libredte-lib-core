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

use Derafu\Mail\Model\Mailbox;
use Derafu\Mail\Testing\GreenMailServer;
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use Derafu\Value\ValueCaster;
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
 * The sending of envelopes by email, through a real SMTP server: what is sent
 * is what arrives, to everybody that must receive it, and a failure is told.
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
final class ExchangeEmailSenderTest extends AbstractExchangeMailTestCase
{
    public function testEachEnvelopeIsOneMailWithItsOwnResult(): void
    {
        $results = self::sendAs(
            'ana',
            self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'one.xml', body: '<one/>')]),
            self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'two.xml', body: '<two/>')]),
        );

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertTrue($result->getStatuses()[0]->isOk());
        }
        $this->assertSame(['one.xml', 'two.xml'], self::attachmentNames(self::receiveAs('beto')));
    }

    public function testAnEnvelopeWithTwoReceiversIsDeliveredToBoth(): void
    {
        $results = self::sendAs(
            'ana',
            self::mailEnvelope(self::ANA, [self::BETO, self::ANA], [new Attachment(filename: 'both.xml', body: '<both/>')]),
        );

        $this->assertTrue($results[0]->getStatuses()[0]->isOk());
        $this->assertSame(['both.xml'], self::attachmentNames(self::receiveAs('beto')));
        $this->assertSame(['both.xml'], self::attachmentNames(self::receiveAs('ana')));
    }

    public function testTheAttachmentsArriveByteForByte(): void
    {
        // The XML of the SII are in ISO-8859-1; a signed XML is only valid if
        // the transport does not change a single byte of it.
        $bodies = [
            'latin1.xml' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<A>Env' . "\xED" . 'o, ni' . "\xF1" . 'o</A>' . "\n",
            'crlf.xml' => "<A>\r\n<B/>\r\n</A>\r\n",
            'no-final-newline.xml' => '<A><B/></A>',
        ];
        $attachments = [];
        foreach ($bodies as $name => $body) {
            $attachments[] = new Attachment(filename: $name, body: $body);
        }

        self::sendAs('ana', self::mailEnvelope(self::ANA, [self::BETO], $attachments));
        $received = self::attachments(self::receiveAs('beto'));

        $this->assertCount(3, $received);
        foreach ($received as $attachment) {
            $name = ValueCaster::requireString($attachment->getFilename());
            $this->assertSame($bodies[$name], $attachment->getBody(), $name);
            $this->assertSame('application/xml', $attachment->getContentType());
        }
    }

    public function testTheSubjectAndTheTextOfTheEnvelopeAreTheOnesOfTheMail(): void
    {
        $attachment = new Attachment(filename: 'a.xml', body: '<a/>');
        self::sendAs(
            'ana',
            self::mailEnvelope(self::ANA, [self::BETO], [$attachment], ['subject' => 'Your invoice', 'text' => 'Attached.']),
            self::mailEnvelope(self::ANA, [self::BETO], [$attachment]),
        );

        $mailbox = new Mailbox(self::server()->imapDsn(), 'beto', GreenMailServer::PASSWORD);
        $subjects = [];
        $texts = [];
        foreach ($mailbox->searchMailbox('ALL') as $id) {
            $mail = $mailbox->getMail($id, markAsSeen: false);
            $subjects[] = ValueCaster::requireString((string) $mail->getSubject());
            $texts[] = trim(ValueCaster::requireString($mail->getTextBody()));
        }
        sort($subjects);

        $this->assertCount(2, $subjects);
        $this->assertStringStartsWith('Envío de documentos electrónicos de ', $subjects[0]);
        $this->assertSame('Your invoice', $subjects[1]);
        $this->assertContains('Attached.', $texts);
    }

    public function testWrongCredentialsAreInTheStatusOfTheResult(): void
    {
        $results = self::send(
            ['password' => 'wrong'] + self::server()->smtpTransport('ana'),
            self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')])
        );

        $status = $results[0]->getStatuses()[0];
        $this->assertFalse($status->isOk());
        $this->assertStringContainsString('authenticate', $status->getError()?->getMessage() ?? '');
        $this->assertSame([], self::receiveAs('beto'));
    }

    public function testAServerThatIsNotThereIsInTheStatusOfTheResult(): void
    {
        $results = self::send(
            ['port' => self::closedPort()] + self::server()->smtpTransport('ana'),
            self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')])
        );

        $this->assertFalse($results[0]->getStatuses()[0]->isOk());
    }

    public function testWithoutCredentialsNothingIsSent(): void
    {
        $results = self::send(
            ['host' => '127.0.0.1', 'port' => self::server()->smtpPort],
            self::mailEnvelope(self::ANA, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')])
        );

        $this->assertSame([], $results);
        $this->assertSame([], self::receiveAs('beto'));
    }

    public function testAnEnvelopeWhoseSenderHasNoEmailIsNotSent(): void
    {
        $results = self::send(
            self::server()->smtpTransport('ana'),
            self::mailEnvelope(null, [self::BETO], [new Attachment(filename: 'a.xml', body: '<a/>')])
        );

        $this->assertSame([], $results);
        $this->assertSame([], self::receiveAs('beto'));
    }

    public function testTheCopiesAndTheHiddenCopiesReceiveTheMail(): void
    {
        $attachment = new Attachment(filename: 'copy.xml', body: '<copy/>');

        $results = self::sendAs(
            'ana',
            self::mailEnvelope(self::ANA, [self::BETO], [$attachment], ['cc' => [self::ANA]]),
        );
        $this->assertTrue($results[0]->getStatuses()[0]->isOk());
        $this->assertSame(['copy.xml'], self::attachmentNames(self::receiveAs('beto')));
        $this->assertSame(['copy.xml'], self::attachmentNames(self::receiveAs('ana')));

        self::server()->purge();
        self::sendAs(
            'ana',
            self::mailEnvelope(self::ANA, [self::BETO], [$attachment], ['bcc' => [self::ANA]]),
        );
        $this->assertSame(['copy.xml'], self::attachmentNames(self::receiveAs('beto')));
        $this->assertSame(['copy.xml'], self::attachmentNames(self::receiveAs('ana')));
    }

    public function testAnAddressThatIsInSeveralFieldsReceivesTheMailOnce(): void
    {
        self::sendAs(
            'ana',
            self::mailEnvelope(
                self::ANA,
                [self::BETO],
                [new Attachment(filename: 'once.xml', body: '<once/>')],
                ['to' => [self::BETO], 'cc' => ['Beto <' . self::BETO . '>']]
            ),
        );

        $this->assertCount(1, self::receiveAs('beto'));
    }

    public function testAnEnvelopeWithoutDocumentsIsRejected(): void
    {
        $bag = new ExchangeBag(['transport' => self::server()->smtpTransport('ana')]);
        $bag->addEnvelope(self::mailEnvelope(self::ANA, [self::BETO], null));

        $this->expectException(TranslatableLogicException::class);
        $this->expectExceptionMessage('The envelope to send has no documents.');

        self::exchange()->getSenderWorker()->handle($bag);
    }

    public function testABagWithoutEnvelopesIsRejected(): void
    {
        $this->expectException(TranslatableLogicException::class);
        $this->expectExceptionMessage('The exchange bag to send has no envelopes.');

        self::exchange()->getSenderWorker()->handle(
            new ExchangeBag(['transport' => self::server()->smtpTransport('ana')])
        );
    }
}
