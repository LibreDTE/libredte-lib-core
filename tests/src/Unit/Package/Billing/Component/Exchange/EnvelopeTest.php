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

use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractParty;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The documents of an envelope: it may have none, they are added, and setting
 * them replaces the ones that it had.
 */
#[CoversClass(Envelope::class)]
#[UsesClass(AbstractParty::class)]
#[UsesClass(Sender::class)]
#[UsesClass(Receiver::class)]
#[UsesClass(PartyIdentifier::class)]
#[UsesClass(Document::class)]
#[UsesClass(Attachment::class)]
#[UsesClass(DocumentType::class)]
class EnvelopeTest extends TestCase
{
    private function envelope(Document ...$documents): Envelope
    {
        return new Envelope(
            sender: new Sender(new PartyIdentifier('76192083-9')),
            receiver: new Receiver(new PartyIdentifier('60803000-K')),
            documents: array_values($documents),
        );
    }

    private function document(string $filename): Document
    {
        return new Document(attachments: [new Attachment(filename: $filename, body: '<a/>')]);
    }

    public function testAnEnvelopeWithoutDocumentsHasNone(): void
    {
        $envelope = $this->envelope();

        $this->assertSame(0, $envelope->countDocuments());
        $this->assertSame([], $envelope->getDocuments());
    }

    public function testTheDocumentsOfTheConstructorAreTheOnesOfTheEnvelope(): void
    {
        $one = $this->document('one.xml');
        $two = $this->document('two.xml');

        $envelope = $this->envelope($one, $two);

        $this->assertSame(2, $envelope->countDocuments());
        $this->assertSame([$one, $two], $envelope->getDocuments());
    }

    public function testADocumentIsAddedToTheOnesThatTheEnvelopeHas(): void
    {
        $one = $this->document('one.xml');
        $two = $this->document('two.xml');
        $envelope = $this->envelope($one);

        $envelope->addDocument($two);

        $this->assertSame([$one, $two], $envelope->getDocuments());
    }

    public function testSettingTheDocumentsReplacesTheOnesThatTheEnvelopeHad(): void
    {
        $envelope = $this->envelope($this->document('one.xml'), $this->document('two.xml'));
        $three = $this->document('three.xml');

        $envelope->setDocuments([$three]);

        $this->assertSame([$three], $envelope->getDocuments());

        $envelope->setDocuments([]);

        $this->assertSame(0, $envelope->countDocuments());
    }
}
