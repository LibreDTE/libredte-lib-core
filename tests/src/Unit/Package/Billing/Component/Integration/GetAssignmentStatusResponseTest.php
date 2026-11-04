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
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiRtcQueryResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\GetAssignmentStatusResponse;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests de la respuesta de la consulta del estado de una cesión.
 */
#[CoversClass(AbstractSiiRtcQueryResponse::class)]
#[CoversClass(GetAssignmentStatusResponse::class)]
class GetAssignmentStatusResponseTest extends TestCase
{
    /**
     * Decodes an SII response as when it is received.
     *
     * @return array<int|string, mixed>
     */
    private function decode(string $xml): array
    {
        $document = new XmlDocument();
        $document->loadXml($xml);

        return (new XmlService(new XmlEncoder(), new XmlDecoder(), new XmlValidator()))
            ->decode($document)
        ;
    }

    private function respuesta(string $header, string $body = ''): string
    {
        return '<SII:RESPUESTA xmlns:SII="http://www.sii.cl/XMLSchema">'
            . '<SII:RESP_HDR>' . $header . '</SII:RESP_HDR>'
            . ($body !== '' ? '<SII:RESP_BODY>' . $body . '</SII:RESP_BODY>' : '<SII:RESP_BODY/>')
            . '</SII:RESPUESTA>'
        ;
    }

    public function testAnAssignedDocumentWithoutDetail(): void
    {
        $response = new GetAssignmentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>0</SII:ESTADO><SII:GLOSA>Documento Cedido</SII:GLOSA>'
        )));

        $this->assertTrue($response->isAssigned());
        $this->assertSame('0', $response->getStatus());
        $this->assertSame('Documento Cedido', $response->getDescription());
        $this->assertNull($response->getHolder());
        $this->assertSame(Status::Success, $response->getStatusType());
    }

    public function testTheDetailOfTheAssignment(): void
    {
        $response = new GetAssignmentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>0</SII:ESTADO>',
            '<RUT_TENEDOR>2-7</RUT_TENEDOR><FECHA_ULT_ANOT>2005-05-04 18:13:06</FECHA_ULT_ANOT><DEC_JURADA>EXISTE</DEC_JURADA>'
        )));

        $this->assertTrue($response->isAssigned());
        $this->assertSame('2-7', $response->getHolder());
        $this->assertSame([
            'assigned' => true,
            'status' => '0',
            'description' => 'Requerimiento recibido OK.',
            'holder' => '2-7',
            'last_annotation' => '2005-05-04 18:13:06',
            'collection_rut' => null,
            'collection_address' => null,
            'sworn_declaration' => 'EXISTE',
            'token' => null,
        ], $response->getData());
    }

    public function testTheDetailWithCollection(): void
    {
        $response = new GetAssignmentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>0</SII:ESTADO>',
            '<RUT_TENEDOR>2-7</RUT_TENEDOR><FECHA_ULT_ANOT>2005-05-04 18:13:06</FECHA_ULT_ANOT><RUT_COBRANZA>3-5</RUT_COBRANZA><DIR_COBRANZA>Calle 1</DIR_COBRANZA><DEC_JURADA>NO EXISTE</DEC_JURADA>'
        )));

        $this->assertSame('3-5', $response->getData()['collection_rut']);
        $this->assertSame('Calle 1', $response->getData()['collection_address']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function errorsProvider(): array
    {
        return [
            'data' => ['1', 'Error de datos.'],
            'without information' => ['2', 'Error: no se encontró información.'],
            'inactive token' => ['3', 'Error de autenticación: token inactivo.'],
            'token that does not exist' => ['4', 'Error de autenticación: token no existe.'],
            'permissions' => ['10', 'Error de permisos: el usuario conectado no tiene permiso en la empresa.'],
            'document that does not exist or is not assigned' => ['-10', 'Error: el documento solicitado no existe o no ha sido cedido.'],
            'related company' => ['-15', 'Error de permisos: la empresa relacionada no está vinculada con el documento consultado.'],
        ];
    }

    #[DataProvider('errorsProvider')]
    public function testAnErrorMeansThatTheDocumentIsNotAssigned(string $code, string $description): void
    {
        $response = new GetAssignmentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>' . $code . '</SII:ESTADO>'
        )));

        $this->assertFalse($response->isAssigned());
        $this->assertSame($code, $response->getStatus());
        $this->assertSame($description, $response->getDescription());
        $this->assertSame(Status::Danger, $response->getStatusType());
    }
}
