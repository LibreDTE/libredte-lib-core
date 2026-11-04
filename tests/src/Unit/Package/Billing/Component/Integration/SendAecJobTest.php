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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Integration;

use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtc\SendAecException;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\SendAecJob;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use UnexpectedValueException;

/**
 * Tests of what is sent to the RTC of the SII: only an AEC.
 */
#[CoversClass(SendAecJob::class)]
#[UsesClass(SiiException::class)]
class SendAecJobTest extends TestCase
{
    private function send(string $xml): void
    {
        $document = new XmlDocument();
        $document->loadXml($xml);

        // The company is not a valid RUT: if the XML passes the check of what is
        // sent, the job stops there, before it calls the SII.
        (new SendAecJob($this->createStub(SiiLazyWorkerInterface::class)))->send(
            $this->createStub(SiiRequestInterface::class),
            $document,
            '12345678-0',
            'cedente@example.com'
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function otherXmlProvider(): array
    {
        return [
            'an EnvioDTE' => ['EnvioDTE'],
            'an EnvioBOLETA' => ['EnvioBOLETA'],
            'a book' => ['LibroCompraVenta'],
            'a DTE' => ['DTE'],
        ];
    }

    #[DataProvider('otherXmlProvider')]
    public function testAnXmlThatIsNotAnAecIsRefused(string $root): void
    {
        $this->expectException(SendAecException::class);

        $this->send('<' . $root . '/>');
    }

    public function testAnAecIsNotRefused(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->send('<AEC/>');
    }
}
