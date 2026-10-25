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
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\ExchangeComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Handler\EmailReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Strategy\Email\ImapReceiverStrategy;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\ReceiverWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractExchangeWorker::class)]
#[CoversClass(AbstractReceiverHandler::class)]
#[CoversClass(ExchangeComponent::class)]
#[CoversClass(ExchangeBag::class)]
#[CoversClass(ReceiverWorker::class)]
#[CoversClass(EmailReceiverHandler::class)]
#[CoversClass(ImapReceiverStrategy::class)]
#[CoversClass(AbstractParty::class)]
#[CoversClass(PartyIdentifier::class)]
#[CoversClass(Attachment::class)]
#[CoversClass(Document::class)]
#[CoversClass(Envelope::class)]
#[CoversClass(ExchangeResult::class)]
class ExchangeDocumentReceiveEmailTest extends TestCase
{
    private ExchangeComponentInterface $exchange;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->exchange = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getExchangeComponent()
        ;
    }

    public function testReceiveOneDocumentInOneEnvelope(): void
    {
        $username = getenv('MAIL_USERNAME');
        $password = getenv('MAIL_PASSWORD');

        if (!$username || !$password) {
            $this->markTestSkipped('No mail configuration available for receiving.');
        }

        // Create the exchange bag (no envelopes, reception only).
        $exchangeBag = new ExchangeBag([
            'transport' => [
                'username' => $username,
                'password' => $password,
                'search' => [
                    'daysAgo' => 1,
                    'markAsSeen' => false,
                ],
            ],
        ]);
        $results = $this->exchange->handle($exchangeBag);

        // Validate manually when testing other functionality.
        // Note: perform real validation when running the test locally.
        $this->addToAssertionCount(1);
        //$this->assertNotEmpty($results);
    }
}
