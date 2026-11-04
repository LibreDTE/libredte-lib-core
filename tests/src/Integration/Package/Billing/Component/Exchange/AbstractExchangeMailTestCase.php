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

use Derafu\Mail\Testing\MailServerTestCase;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\AttachmentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeResultInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyEndpoint;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeBag;
use LogicException;

/**
 * Base of the tests of the exchange by email against a real mail server.
 *
 * The users of the server are the parties: `ana` is the issuer (or the sender)
 * and `beto` the receiver. The mails go through the real SMTP and IMAP
 * strategies of the exchange; nothing is replaced.
 */
abstract class AbstractExchangeMailTestCase extends MailServerTestCase
{
    protected const ANA = 'ana@example.com';

    protected const BETO = 'beto@example.com';

    protected const SENDER_RUT = '76192083-9';

    protected const RECEIVER_RUT = '60803000-K';

    protected static function exchange(): ExchangeComponentInterface
    {
        return Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getExchangeComponent()
        ;
    }

    /**
     * Creates an envelope of the exchange with one document that has the
     * attachments, from an address to other addresses.
     *
     * @param string|null $from Address of the sender, `null` for a sender
     * without an email.
     * @param list<string> $to Addresses of the receiver.
     * @param list<Attachment>|null $attachments Attachments of the document,
     * `null` for an envelope without documents.
     * @param array<string, mixed> $metadata
     * @param string $senderRut RUT of the sender.
     * @param string $receiverRut RUT of the receiver.
     */
    protected static function mailEnvelope(
        ?string $from,
        array $to,
        ?array $attachments,
        array $metadata = [],
        string $senderRut = self::SENDER_RUT,
        string $receiverRut = self::RECEIVER_RUT
    ): Envelope {
        $sender = new Sender(new PartyIdentifier($senderRut));
        if ($from !== null) {
            $sender->addEndpoint(new PartyEndpoint($from));
        }

        $receiver = new Receiver(new PartyIdentifier($receiverRut));
        foreach ($to as $address) {
            $receiver->addEndpoint(new PartyEndpoint($address));
        }

        $envelope = new Envelope(sender: $sender, receiver: $receiver);
        if ($attachments !== null) {
            $envelope->addDocument(new Document(attachments: $attachments));
        }
        foreach ($metadata as $key => $value) {
            $envelope->addMetadata($key, $value);
        }

        return $envelope;
    }

    /**
     * Sends envelopes by email with a transport.
     *
     * @param array<string, mixed> $transport
     * @return ExchangeResultInterface[]
     */
    protected static function send(array $transport, Envelope ...$envelopes): array
    {
        $bag = new ExchangeBag(['transport' => $transport]);
        foreach ($envelopes as $envelope) {
            $bag->addEnvelope($envelope);
        }

        return self::exchange()->getSenderWorker()->handle($bag);
    }

    /**
     * Receives the envelopes by email with a transport.
     *
     * @param array<string, mixed> $transport
     * @return ExchangeResultInterface[]
     */
    protected static function receive(array $transport): array
    {
        return self::exchange()->getReceiverWorker()->handle(
            new ExchangeBag(['transport' => $transport])
        );
    }

    /**
     * Sends envelopes as a user of the server.
     *
     * @return ExchangeResultInterface[]
     */
    protected static function sendAs(string $user, Envelope ...$envelopes): array
    {
        return self::send(self::server()->smtpTransport($user), ...$envelopes);
    }

    /**
     * Receives the envelopes of the mailbox of a user of the server.
     *
     * @param array<string, mixed> $search Options of `search` of the transport.
     * @return ExchangeResultInterface[]
     */
    protected static function receiveAs(string $user, array $search = []): array
    {
        return self::receive(self::server()->imapTransport($user, $search));
    }

    /**
     * Names of the attachments of the documents of the results, sorted.
     *
     * @param ExchangeResultInterface[] $results
     * @return list<string>
     */
    protected static function attachmentNames(array $results): array
    {
        $names = [];
        foreach (self::attachments($results) as $attachment) {
            $names[] = ValueCaster::requireString($attachment->getFilename());
        }
        sort($names);

        return $names;
    }

    /**
     * Attachments of the documents of the envelopes of the results.
     *
     * @param ExchangeResultInterface[] $results
     * @return list<AttachmentInterface>
     */
    protected static function attachments(array $results): array
    {
        $attachments = [];
        foreach ($results as $result) {
            foreach ($result->getEnvelope()->getDocuments() as $document) {
                foreach ($document->getAttachments() as $attachment) {
                    $attachments[] = $attachment;
                }
            }
        }

        return $attachments;
    }

    /**
     * A port that was free a moment ago, so nothing listens on it.
     */
    protected static function closedPort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if ($socket === false) {
            throw new LogicException('There is no free port to use as a closed one.');
        }
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        return $port;
    }
}
