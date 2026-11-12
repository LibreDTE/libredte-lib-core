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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Exchange;

use Derafu\Array\ArrayReader;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\DocumentResponseWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\EnvioRecibos;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\RespuestaEnvio;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\TipoDocumentoRespuesta;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\DocumentResponseException;
use libredte\lib\Core\Package\Billing\Component\Exchange\ExchangeComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildEnvioRecibosJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildRespuestaEnvioJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponseWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BuildEnvioRecibosJob::class)]
#[CoversClass(BuildRespuestaEnvioJob::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(ExchangeComponent::class)]
#[CoversClass(DocumentResponseWorker::class)]
#[CoversClass(DocumentResponseException::class)]
#[CoversClass(ExchangeDocumentBag::class)]
#[CoversClass(EnvioRecibos::class)]
#[CoversClass(RespuestaEnvio::class)]
class DocumentResponseWorkerTest extends TestCase
{
    private DocumentResponseWorkerInterface $worker;

    private CertificateInterface $certificate;

    protected function setUp(): void
    {
        $this->worker = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getExchangeComponent()
            ->getDocumentResponseWorker()
        ;

        $this->certificate = (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9');
    }

    private function envioRecibos(): EnvioRecibos
    {
        return $this->worker->buildEnvioRecibos(new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::ENVIO_RECIBOS,
            caratula: ['RutResponde' => '76192083-9', 'RutRecibe' => '88888888-8'],
            data: [[
                'TipoDoc' => 33,
                'Folio' => 1,
                'FchEmis' => '2024-01-15',
                'RUTEmisor' => '88888888-8',
                'RUTRecep' => '76192083-9',
                'MntTotal' => 100000,
                'Recinto' => 'Oficina central',
                'RutFirma' => '76192083-9',
            ]],
            certificate: $this->certificate,
        ));
    }

    private function respuestaEnvio(): RespuestaEnvio
    {
        return $this->worker->buildRespuestaEnvio(new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::RESPUESTA_ENVIO,
            caratula: ['RutResponde' => '76192083-9', 'RutRecibe' => '88888888-8', 'IdRespuesta' => 1],
            data: ['resultado_dte' => [[
                'TipoDTE' => 33,
                'Folio' => 1,
                'FchEmis' => '2024-01-15',
                'RUTEmisor' => '88888888-8',
                'RUTRecep' => '76192083-9',
                'MntTotal' => 100000,
                'CodEnvio' => 1,
                'EstadoDTE' => 0,
                'EstadoDTEGlosa' => 'ACEPTADO OK',
            ]]],
            certificate: $this->certificate,
        ));
    }

    public function testTheComponentExposesTheWorker(): void
    {
        $component = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getExchangeComponent()
        ;

        $this->assertSame($this->worker, $component->getWorkers()['document_response']);
    }

    public function testBuildPutsTheDocumentInTheBag(): void
    {
        $bag = new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::ENVIO_RECIBOS,
            caratula: ['RutResponde' => '76192083-9', 'RutRecibe' => '88888888-8'],
            data: [[
                'TipoDoc' => 33, 'Folio' => 7, 'FchEmis' => '2024-01-15', 'RUTEmisor' => '88888888-8',
                'RUTRecep' => '76192083-9', 'MntTotal' => 5000, 'Recinto' => 'Bodega', 'RutFirma' => '76192083-9',
            ]],
            certificate: $this->certificate,
        );

        $document = $this->worker->buildEnvioRecibos($bag);

        $this->assertSame($document, $bag->getDocument());
    }

    public function testBuildEnvioRecibosWithInvalidDataIsReportedAsDocumentResponseException(): void
    {
        $bag = new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::ENVIO_RECIBOS,
            caratula: ['RutResponde' => '76192083-9', 'RutRecibe' => '88888888-8'],
            data: ['recibo' => 'esto no es un recibo'],
        );

        try {
            $this->worker->buildEnvioRecibos($bag);
            $this->fail('A DocumentResponseException was expected.');
        } catch (DocumentResponseException $e) {
            $this->assertStringContainsString('EnvioRecibos', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
            $this->assertNull($bag->getDocument());
        }
    }

    public function testBuildRespuestaEnvioWithInvalidDataIsReportedAsDocumentResponseException(): void
    {
        $bag = new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::RESPUESTA_ENVIO,
            caratula: ['RutResponde' => '76192083-9', 'RutRecibe' => '88888888-8', 'IdRespuesta' => 1],
            data: ['resultado_dte' => 'esto no es una lista'],
        );

        try {
            $this->worker->buildRespuestaEnvio($bag);
            $this->fail('A DocumentResponseException was expected.');
        } catch (DocumentResponseException $e) {
            $this->assertStringContainsString('RespuestaDTE', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
            $this->assertNull($bag->getDocument());
        }
    }

    public function testValidateSchemaOfAnXmlStringDetectsTheDocumentByItsRoot(): void
    {
        $recibos = $this->envioRecibos();
        $respuesta = $this->respuestaEnvio();

        $fromRecibos = $this->worker->validateSchema($recibos->saveXml());
        $fromRespuesta = $this->worker->validateSchema($respuesta->saveXml());

        $this->assertSame('EnvioRecibos', $fromRecibos->getDomDocument()->documentElement?->localName);
        $this->assertSame('RespuestaDTE', $fromRespuesta->getDomDocument()->documentElement?->localName);
    }

    public function testValidateSchemaOfAnXmlDocument(): void
    {
        $recibos = $this->envioRecibos();

        $validated = $this->worker->validateSchema($recibos->getXmlDocument());

        $this->assertSame('EnvioRecibos', $validated->getDomDocument()->documentElement?->localName);
    }

    public function testValidateSchemaOfAnExchangeDocumentReturnsItsXml(): void
    {
        $recibos = $this->envioRecibos();

        $validated = $this->worker->validateSchema($recibos);

        $this->assertSame('EnvioRecibos', $validated->getDomDocument()->documentElement?->localName);
    }

    public function testValidateSchemaRejectsAnInvalidDocumentOfAKnownRoot(): void
    {
        $xml = str_replace('<RutRecibe>88888888-8</RutRecibe>', '<RutRecibe>no-es-un-rut</RutRecibe>', $this->envioRecibos()->saveXml());
        $this->assertStringContainsString('no-es-un-rut', $xml);

        $this->expectException(\Derafu\Xml\Exception\XmlException::class);
        $this->worker->validateSchema($xml);
    }

    public function testValidateSchemaRejectsAnUnknownRootElement(): void
    {
        $this->expectException(DocumentResponseException::class);
        $this->expectExceptionMessage('EnvioDTE');
        $this->worker->validateSchema('<EnvioDTE xmlns="http://www.sii.cl/SiiDte"/>');
    }

    public function testValidateSignatureAcceptsAnExchangeDocumentAnXmlStringAndAnXmlDocument(): void
    {
        $document = $this->respuestaEnvio();
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($document->saveXml());

        foreach ([$document, $document->saveXml(), $xmlDocument] as $source) {
            $results = $this->worker->validateSignature($source);

            $this->assertNotEmpty($results);
            foreach ($results as $result) {
                $this->assertTrue($result->isValid());
            }
        }
    }

    public function testValidateSignatureDetectsADocumentThatWasAlteredAfterSigning(): void
    {
        $xml = str_replace('<MntTotal>100000</MntTotal>', '<MntTotal>999999</MntTotal>', $this->respuestaEnvio()->saveXml());
        $this->assertStringContainsString('999999', $xml);

        $invalid = array_filter(
            $this->worker->validateSignature($xml),
            fn ($result) => !$result->isValid()
        );

        $this->assertNotEmpty($invalid);
    }

    public function testExchangeDocumentXmlAndSerialization(): void
    {
        $document = $this->envioRecibos();

        $this->assertStringStartsWith('<?xml', $document->saveXml());
        $this->assertStringStartsWith('<EnvioRecibos', $document->getXml());
        $this->assertStringNotContainsString('<?xml', $document->getXml());

        $array = $document->toArray();
        $this->assertSame($document->saveXml(), ArrayReader::fromArray($array)->requireString('xml'));

        $json = ArrayReader::fromValue($document->jsonSerialize());
        $this->assertSame($document->saveXml(), base64_decode($json->requireString('xml'), true));
        $this->assertJson((string) json_encode($document));
    }

    public function testSignatureNamespaceDependsOnTheDocument(): void
    {
        $this->assertSame(
            $this->envioRecibos()->getSignatureNamespace(),
            $this->envioRecibos()->getSignatureNamespace()
        );
        $this->assertNotSame('', (string) $this->respuestaEnvio()->getSchema());
        $this->assertSame('EnvioRecibos_v10.xsd', $this->envioRecibos()->getSchema());
        $this->assertSame('RespuestaEnvioDTE_v10.xsd', $this->respuestaEnvio()->getSchema());
    }
}
