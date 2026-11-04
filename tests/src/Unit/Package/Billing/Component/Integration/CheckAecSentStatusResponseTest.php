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
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\EstadoEnvioAec;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\CheckAecSentStatusResponse;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Tests de la respuesta de la consulta del estado del envío de un AEC.
 */
#[CoversClass(AbstractSiiRtcQueryResponse::class)]
#[CoversClass(CheckAecSentStatusResponse::class)]
#[UsesClass(EstadoEnvioAec::class)]
class CheckAecSentStatusResponseTest extends TestCase
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

    public function testAnEnvelopeInProcess(): void
    {
        $response = new CheckAecSentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>0</SII:ESTADO>',
            '<TRACKID>123</TRACKID><ESTADO_ENVIO>UPL</ESTADO_ENVIO><DESC_ESTADO>Envio En Proceso de Upload</DESC_ESTADO>'
        )), ['Token' => 'token', 'TrackId' => 123]);

        $this->assertTrue($response->isRequestOk());
        $this->assertSame(123, $response->getTrackId());
        $this->assertSame('UPL', $response->getStatus());
        $this->assertSame('Envío en proceso de upload', $response->getDescription());
        $this->assertSame('UPL - Envío en proceso de upload', $response->getMessage());
        $this->assertSame(EstadoEnvioAec::UPL, $response->getEstadoEnvioAec());
        $this->assertFalse($response->isFinal());
        $this->assertSame(Status::Info, $response->getStatusType());
    }

    public function testARejectedEnvelope(): void
    {
        $response = new CheckAecSentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>0</SII:ESTADO>',
            '<TRACKID>123</TRACKID><ESTADO_ENVIO>RFS</ESTADO_ENVIO><DESC_ESTADO>Rechazado por Firma de Sobre</DESC_ESTADO>'
        )));

        $this->assertSame('RFS', $response->getStatus());
        $this->assertTrue($response->getData()['error']);
        $this->assertTrue($response->isFinal());
        $this->assertSame(Status::Danger, $response->getStatusType());
    }

    public function testAnAcceptedEnvelope(): void
    {
        $response = new CheckAecSentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>0</SII:ESTADO>',
            '<TRACKID>123</TRACKID><ESTADO_ENVIO>EOK</ESTADO_ENVIO><DESC_ESTADO>Envio Aceptado</DESC_ESTADO>'
        )));

        $this->assertFalse($response->getData()['error']);
        $this->assertTrue($response->isFinal());
        $this->assertSame(Status::Success, $response->getStatusType());
    }

    public function testAnUnknownStateIsReportedWithTheDescriptionOfTheSii(): void
    {
        $response = new CheckAecSentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>0</SII:ESTADO>',
            '<TRACKID>123</TRACKID><ESTADO_ENVIO>XYZ</ESTADO_ENVIO><DESC_ESTADO>Estado nuevo</DESC_ESTADO>'
        )));

        $this->assertNull($response->getEstadoEnvioAec());
        $this->assertSame('XYZ', $response->getStatus());
        $this->assertSame('Estado nuevo', $response->getDescription());
        $this->assertFalse($response->isFinal());
        $this->assertSame(Status::Info, $response->getStatusType());
    }

    public function testAnErrorOfTheQueryIsReportedWithTheStatusOfTheHeader(): void
    {
        $response = new CheckAecSentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>10</SII:ESTADO>'
        )), ['Token' => 'token', 'TrackId' => 123]);

        $this->assertFalse($response->isRequestOk());
        $this->assertSame('10', $response->getStatus());
        $this->assertSame(
            'Error de permisos: el usuario conectado no tiene permiso en la empresa.',
            $response->getDescription()
        );
        $this->assertSame(123, $response->getTrackId());
        $this->assertNull($response->getEstadoEnvioAec());
        $this->assertTrue($response->getData()['error']);
        $this->assertSame(Status::Danger, $response->getStatusType());
    }

    public function testAnErrorWithoutAKnownCodeUsesTheGlossOfTheSii(): void
    {
        $response = new CheckAecSentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>77</SII:ESTADO><SII:GLOSA>Algo pasó</SII:GLOSA>'
        )), ['TrackId' => 5]);

        $this->assertSame('Algo pasó', $response->getDescription());
    }

    public function testTheTokenIsNotSerialized(): void
    {
        $response = new CheckAecSentStatusResponse($this->decode($this->respuesta(
            '<SII:ESTADO>0</SII:ESTADO>',
            '<TRACKID>123</TRACKID><ESTADO_ENVIO>EOK</ESTADO_ENVIO>'
        )), ['Token' => 'secret-token', 'TrackId' => 123]);

        $this->assertSame('secret-token', $response->toArray()['token']);
        $this->assertArrayNotHasKey('token', $response->jsonSerialize());
    }
}
