<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Integration;

use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiWsdlResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\CheckXmlDocumentSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentSignatureResponse;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Pruebas de las respuestas del SII cuando traen elementos vacíos.
 *
 * El SII responde elementos vacíos (`<SRV_CODE/>`) y el XML se decodifica con
 * el elemento vacío como texto vacío (`''`), no como `null`. Una respuesta no
 * debe cambiar de resultado, ni fallar, por eso.
 */
#[CoversClass(AbstractSiiWsdlResponse::class)]
#[CoversClass(CheckXmlDocumentSentStatusResponse::class)]
#[CoversClass(ValidateDocumentSignatureResponse::class)]
class SiiEmptyElementsTest extends TestCase
{
    /**
     * Decodifica una respuesta del SII como se hace al recibirla.
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

    public function testErrorCodesThatAreEmptyAreNotPartOfTheDescription(): void
    {
        $response = new CheckXmlDocumentSentStatusResponse($this->decode($this->respuesta(
            '<ESTADO>-11</ESTADO><ERR_CODE>2</ERR_CODE><SQL_CODE/><SRV_CODE/>'
        )));

        $this->assertSame('-11', $response->getStatus());
        $this->assertSame('Error de Proceso', $response->getDescription());
    }

    public function testAnEmptyBodyIsAnEmptyBody(): void
    {
        $response = new CheckXmlDocumentSentStatusResponse($this->decode($this->respuesta(
            '<ESTADO>EPR</ESTADO><GLOSA>Envio Procesado</GLOSA>'
        )));

        $this->assertSame([], $response->getBody());
        $this->assertSame([], $response->getData()['documents']);
    }

    public function testAnEmptyDescriptionIsAnEmptyText(): void
    {
        $response = new CheckXmlDocumentSentStatusResponse($this->decode($this->respuesta(
            '<ESTADO>SOK</ESTADO><GLOSA/>'
        )));

        $this->assertSame('', $response->getDescription());
        $this->assertSame('SOK', $response->getReviewStatus());
    }

    public function testCountersOfADocumentThatAreEmptyAreZero(): void
    {
        $response = new CheckXmlDocumentSentStatusResponse($this->decode($this->respuesta(
            '<ESTADO>EPR</ESTADO><GLOSA>Envio Procesado</GLOSA>',
            '<TIPO_DOCTO>33</TIPO_DOCTO><INFORMADOS>8</INFORMADOS><ACEPTADOS/><RECHAZADOS/><REPAROS/>'
        )));

        $this->assertSame(
            ['reported' => 8, 'accepted' => 0, 'rejected' => 0, 'repairs' => 0],
            $response->getData()['resume']
        );
    }

    public function testCountersOfSeveralDocumentsThatAreEmptyAreZero(): void
    {
        $response = new CheckXmlDocumentSentStatusResponse($this->decode($this->respuesta(
            '<ESTADO>EPR</ESTADO><GLOSA>Envio Procesado</GLOSA>',
            '<TIPO_DOCTO>33</TIPO_DOCTO><INFORMADOS>8</INFORMADOS><ACEPTADOS>7</ACEPTADOS><RECHAZADOS/><REPAROS>1</REPAROS>'
            . '<TIPO_DOCTO>61</TIPO_DOCTO><INFORMADOS>2</INFORMADOS><ACEPTADOS/><RECHAZADOS>2</RECHAZADOS><REPAROS/>'
        )));

        $this->assertSame(
            ['reported' => 10, 'accepted' => 7, 'rejected' => 2, 'repairs' => 1],
            $response->getData()['resume']
        );
    }

    public function testTheStatusIsTheFirstOneThatIsNotEmpty(): void
    {
        $response = new ValidateDocumentSignatureResponse($this->decode($this->respuesta(
            '<ESTADO/>',
            '<ESTADO>DOK</ESTADO><RECIBIDO>SI</RECIBIDO>'
        )));

        $this->assertSame('DOK', $response->getStatus());
    }
}
