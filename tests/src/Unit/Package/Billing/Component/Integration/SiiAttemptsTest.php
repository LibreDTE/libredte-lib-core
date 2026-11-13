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

use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiAttempts;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Tests of the attempts of an operation against the SII: how many there are,
 * how long each one can take and the pause between them.
 */
#[CoversClass(SiiAttempts::class)]
#[UsesClass(SiiRequest::class)]
class SiiAttemptsTest extends TestCase
{
    public function testOneAttemptWithTheTimeoutGiven(): void
    {
        $attempts = new SiiAttempts(1, 20, 60);

        $this->assertSame([20.0], iterator_to_array($attempts));
        $this->assertCount(1, $attempts);
    }

    public function testNoAttemptsWhenRetriesIsZero(): void
    {
        $attempts = new SiiAttempts(0, 20, 60);

        $this->assertSame([], iterator_to_array($attempts));
        $this->assertCount(0, $attempts);
    }

    public function testTheTimeoutOfAnAttemptIsLimitedByTheTimeThatIsLeft(): void
    {
        $timeouts = iterator_to_array(new SiiAttempts(1, 100, 3));

        $this->assertCount(1, $timeouts);
        $this->assertLessThanOrEqual(3.0, $timeouts[0]);
        $this->assertGreaterThan(2.5, $timeouts[0]);
    }

    public function testThePauseBetweenAttemptsIsExponentialAndThereIsNoneAfterTheLast(): void
    {
        $times = [];
        foreach (new SiiAttempts(3, 5, 60) as $timeout) {
            $times[] = microtime(true);
        }
        $end = microtime(true);

        $this->assertCount(3, $times);
        $this->assertEqualsWithDelta(0.5, $times[1] - $times[0], 0.2);
        $this->assertEqualsWithDelta(1.0, $times[2] - $times[1], 0.2);
        $this->assertLessThan(0.2, $end - $times[2]);
    }

    public function testItStopsWhenThereIsNotEnoughTimeLeftForAnotherAttempt(): void
    {
        $attempts = new SiiAttempts(10, 1, 2);

        foreach ($attempts as $timeout) {
            // The attempt uses more time than the total allows for a next one.
            usleep(1_100_000);
        }

        $this->assertCount(1, $attempts);
    }

    public function testOnlyTheAttemptsMadeAreCountedWhenTheLoopIsBroken(): void
    {
        $attempts = new SiiAttempts(5, 20, 60);

        foreach ($attempts as $timeout) {
            break;
        }

        $this->assertCount(1, $attempts);
    }

    public function testTheAttemptsAreTakenFromTheRequest(): void
    {
        $request = new SiiRequest(options: [
            'retries' => 2,
            'timeout' => 15,
            'max_duration' => 100,
        ]);

        $timeouts = iterator_to_array(SiiAttempts::fromRequest($request));

        $this->assertSame([15.0, 15.0], $timeouts);
    }

    public function testTheRetriesGivenReplaceThoseOfTheRequest(): void
    {
        $request = new SiiRequest(options: ['retries' => 2]);

        $timeouts = iterator_to_array(SiiAttempts::fromRequest($request, 1));

        $this->assertCount(1, $timeouts);
    }
}
