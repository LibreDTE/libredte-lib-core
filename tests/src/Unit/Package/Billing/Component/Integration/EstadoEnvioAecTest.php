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

use Derafu\Enum\Status;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\EstadoEnvioAec;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests de los estados de un envío de AEC al RTC del SII.
 */
#[CoversClass(EstadoEnvioAec::class)]
class EstadoEnvioAecTest extends TestCase
{
    public function testEveryStateHasADescription(): void
    {
        foreach (EstadoEnvioAec::cases() as $estado) {
            $this->assertNotSame('', $estado->getDescription(), $estado->name);
        }
    }

    public function testTheRejectedStatesAreErrorsAndFinal(): void
    {
        foreach ([EstadoEnvioAec::RSC, EstadoEnvioAec::RFS, EstadoEnvioAec::RCR, EstadoEnvioAec::RDC, EstadoEnvioAec::RCS] as $estado) {
            $this->assertTrue($estado->isError(), $estado->name);
            $this->assertTrue($estado->isFinal(), $estado->name);
            $this->assertSame(Status::Danger, $estado->getStatusType(), $estado->name);
        }
    }

    public function testTheAcceptedAndCancelledEnvelopesAreFinalWithoutBeingErrors(): void
    {
        $this->assertFalse(EstadoEnvioAec::EOK->isError());
        $this->assertTrue(EstadoEnvioAec::EOK->isFinal());
        $this->assertSame(Status::Success, EstadoEnvioAec::EOK->getStatusType());

        $this->assertFalse(EstadoEnvioAec::EAN->isError());
        $this->assertTrue(EstadoEnvioAec::EAN->isFinal());
        $this->assertSame(Status::Warning, EstadoEnvioAec::EAN->getStatusType());
    }

    public function testTheIntermediateStatesAreNotFinal(): void
    {
        foreach ([EstadoEnvioAec::UPL, EstadoEnvioAec::RCP, EstadoEnvioAec::SOK, EstadoEnvioAec::FSO, EstadoEnvioAec::COK, EstadoEnvioAec::VDC, EstadoEnvioAec::VCS] as $estado) {
            $this->assertFalse($estado->isError(), $estado->name);
            $this->assertFalse($estado->isFinal(), $estado->name);
            $this->assertSame(Status::Info, $estado->getStatusType(), $estado->name);
        }
    }
}
