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

use Derafu\Array\Exception\ArrayException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\CheckDocumentAssignabilityResponse;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests de la interpretación del código con que el SII responde a la consulta
 * de cesibilidad de un DTE.
 */
#[CoversClass(CheckDocumentAssignabilityResponse::class)]
class CheckDocumentAssignabilityResponseTest extends TestCase
{
    /**
     * Códigos de la tabla del servicio `consultarDocDteCedible` del SII.
     *
     * @return array<string, array{int, bool}>
     */
    public static function codesProvider(): array
    {
        return [
            'rut emisor erróneo' => [1, false],
            'folio erróneo' => [2, false],
            'documento no emitido ni recibido desde el 14 de enero de 2017' => [10, false],
            'documento no recibido' => [18, false],
            'tipo de documento no cedible' => [20, false],
            'referenciado por nota de crédito de anulación' => [21, false],
            'sin registro de reclamo ni de recepción' => [22, false],
            'cedible sin reclamos' => [23, true],
            'reclamado por el receptor' => [24, false],
            'cedible, con acuse de recibo por haber pasado 8 días' => [25, true],
        ];
    }

    #[DataProvider('codesProvider')]
    public function testOnlyTheCodesOfAnAssignableDteAreAssignable(int $code, bool $assignable): void
    {
        $response = new CheckDocumentAssignabilityResponse([
            'return' => ['codResp' => (string) $code, 'descResp' => 'Descripción'],
        ]);

        $this->assertSame($code, $response->getCode());
        $this->assertSame('Descripción', $response->getDescription());
        $this->assertSame($assignable, $response->isAssignable());
    }

    public function testTheCodeIsReadWithoutTheReturnWrapper(): void
    {
        $response = new CheckDocumentAssignabilityResponse(['codResp' => '23', 'descResp' => 'DTE Cedible']);

        $this->assertSame(23, $response->getCode());
        $this->assertTrue($response->isAssignable());
    }

    public function testAResponseWithoutCodeIsNotValid(): void
    {
        $this->expectException(ArrayException::class);

        new CheckDocumentAssignabilityResponse(['return' => ['descResp' => 'Sin código']]);
    }
}
