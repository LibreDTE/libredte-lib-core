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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Document\Support;

use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Test for the data of the document bag that it requires.
 */
#[CoversClass(DocumentBag::class)]
#[CoversClass(DocumentException::class)]
#[UsesClass(Contribuyente::class)]
#[UsesClass(Receptor::class)]
class DocumentBagRequireTest extends TestCase
{
    public function testTheReceptorIsRequired(): void
    {
        $bag = new DocumentBag();
        $receptor = new Receptor('60803000-K');

        $bag->setReceptor($receptor);

        $this->assertSame($receptor, $bag->requireReceptor());
    }

    public function testWithoutReceptorItThrows(): void
    {
        $bag = new DocumentBag();

        $this->assertNull($bag->getReceptor());

        $this->expectException(DocumentException::class);
        $this->expectExceptionMessage('The document bag does not have receptor.');

        $bag->requireReceptor();
    }
}
