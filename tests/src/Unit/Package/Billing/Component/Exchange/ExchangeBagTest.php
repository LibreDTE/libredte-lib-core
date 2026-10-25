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
use Exception;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractParty;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\ProcessType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeStatus;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Unit test for serialization (`toArray()`/`jsonSerialize()`) of `ExchangeBag`
 * and the full tree of entities it holds.
 *
 * This is the only place this serialization is tested directly: currently
 * `ExchangeBag` is not used elsewhere in the library (`exchange.receiver::receive`
 * / `exchange.sender::send` receive an `ExchangeBagInterface` already built by
 * the caller), so there is no end-to-end functional test exercising it.
 */
#[CoversClass(ExchangeBag::class)]
#[UsesClass(Envelope::class)]
#[UsesClass(AbstractParty::class)]
#[UsesClass(Sender::class)]
#[UsesClass(Receiver::class)]
#[UsesClass(PartyIdentifier::class)]
#[UsesClass(Document::class)]
#[UsesClass(Attachment::class)]
#[UsesClass(ExchangeResult::class)]
#[UsesClass(ExchangeStatus::class)]
#[UsesClass(DocumentType::class)]
#[UsesClass(ProcessType::class)]
class ExchangeBagTest extends TestCase
{
    private function buildBag(): ExchangeBag
    {
        $sender = new Sender(new PartyIdentifier('76192083-9', 'CL-RUT'));

        $receiver = new Receiver(new PartyIdentifier('66666666-6', 'CL-RUT'));

        $attachment = new Attachment(
            body: '<DTE>...</DTE>',
            filename: 'factura.xml',
            contentType: 'application/xml',
        );

        $document = new Document(
            content: '<EnvioDTE>...</EnvioDTE>',
            attachments: [$attachment],
            type: DocumentType::INVOICE,
            metadata: ['origin' => 'test'],
        );

        $envelope = new Envelope(
            sender: $sender,
            receiver: $receiver,
            documentType: DocumentType::INVOICE,
            process: ProcessType::BILLING,
            businessMessageID: 'msg-1',
            originalBusinessMessageID: null,
            creationDateAndTime: new DateTimeImmutable('2026-01-15T10:30:00+00:00'),
            documents: [$document],
            metadata: ['channel' => 'test'],
        );

        $bag = new ExchangeBag(['strategy' => 'email.smtp']);
        $bag->addEnvelope($envelope);

        $status = new ExchangeStatus(
            strategy: 'email.smtp',
            error: new Exception('Fallo simulado de envío.'),
        );
        $result = new ExchangeResult($envelope);
        $result->addStatus($status);
        $bag->addResult($result);

        return $bag;
    }

    public function testToArray(): void
    {
        $bag = $this->buildBag();

        $expected = [
            'options' => [
                'transport' => [],
                'strategy' => 'email.smtp',
            ],
            'envelopes' => [
                [
                    'sender' => [
                        'identifier' => [
                            'id' => 'CL-RUT:76192083-9',
                            'value' => '76192083-9',
                            'schemeId' => 'CL-RUT',
                            'schemeName' => 'Rol Único Tributario (RUT) de Chile',
                            'authority' => 'CL-SII',
                        ],
                        'endpoints' => [],
                    ],
                    'receiver' => [
                        'identifier' => [
                            'id' => 'CL-RUT:66666666-6',
                            'value' => '66666666-6',
                            'schemeId' => 'CL-RUT',
                            'schemeName' => 'Rol Único Tributario (RUT) de Chile',
                            'authority' => 'CL-SII',
                        ],
                        'endpoints' => [],
                    ],
                    'documentType' => 'urn:fdc:libredte.cl:2025:doc:invoice',
                    'process' => 'urn:fdc:libredte.cl:2025:poacc:billing',
                    'businessMessageID' => 'msg-1',
                    'originalBusinessMessageID' => null,
                    'creationDateAndTime' => '2026-01-15T10:30:00+00:00',
                    'documents' => [
                        [
                            'id' => 'test-uuid-should-not-be-asserted',
                            'type' => 'urn:fdc:libredte.cl:2025:doc:invoice',
                            'content' => '<EnvioDTE>...</EnvioDTE>',
                            'attachments' => [
                                [
                                    'data' => '<DTE>...</DTE>',
                                    'name' => 'factura.xml',
                                    'type' => 'application/xml',
                                    'size' => strlen('<DTE>...</DTE>'),
                                ],
                            ],
                            'metadata' => ['origin' => 'test', 'id' => 'test-uuid-should-not-be-asserted'],
                        ],
                    ],
                    'metadata' => ['channel' => 'test'],
                ],
            ],
            'results' => [
                [
                    'envelope' => 'msg-1',
                    'statuses' => [
                        [
                            'strategy' => 'email.smtp',
                            'error' => ['message' => 'Fallo simulado de envío.'],
                            'metadata' => [],
                        ],
                    ],
                    'metadata' => [],
                ],
            ],
        ];

        $actual = $bag->toArray();

        // The document ID is an auto-generated UUID (see Document::getID()),
        // non-deterministic — verify it exists and looks like a UUID,
        // then normalize it to compare the rest of the array.
        $documentId = $actual['envelopes'][0]['documents'][0]['id'];
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $documentId,
        );
        $this->assertSame(
            $documentId,
            $actual['envelopes'][0]['documents'][0]['metadata']['id'],
        );
        $actual['envelopes'][0]['documents'][0]['id'] = 'test-uuid-should-not-be-asserted';
        $actual['envelopes'][0]['documents'][0]['metadata']['id'] = 'test-uuid-should-not-be-asserted';

        $this->assertSame($expected, $actual);
    }

    public function testJsonSerialize(): void
    {
        $bag = $this->buildBag();

        $array = $bag->toArray();
        $json = $bag->jsonSerialize();

        // Document content and attachment data must be base64 in jsonSerialize(),
        // unlike toArray() (which leaves them raw) — same pattern as
        // DocumentEnvelope::jsonSerialize().
        $this->assertSame(
            base64_encode($array['envelopes'][0]['documents'][0]['content']),
            $json['envelopes'][0]['documents'][0]['content'],
        );
        $this->assertSame(
            base64_encode($array['envelopes'][0]['documents'][0]['attachments'][0]['data']),
            $json['envelopes'][0]['documents'][0]['attachments'][0]['data'],
        );

        // The rest of the array must be identical between toArray() and
        // jsonSerialize().
        $json['envelopes'][0]['documents'][0]['content'] = $array['envelopes'][0]['documents'][0]['content'];
        $json['envelopes'][0]['documents'][0]['attachments'][0]['data'] = $array['envelopes'][0]['documents'][0]['attachments'][0]['data'];
        $this->assertSame($array, $json);
    }
}
