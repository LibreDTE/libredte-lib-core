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

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\DocumentResponseWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\RespuestaEnvio;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\TipoDocumentoRespuesta;
use libredte\lib\Core\Package\Billing\Component\Exchange\ExchangeComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildRespuestaEnvioJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponseWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DocumentResponseWorker::class)]
#[CoversClass(BuildRespuestaEnvioJob::class)]
#[CoversClass(RespuestaEnvio::class)]
#[CoversClass(AbstractExchangeDocument::class)]
#[CoversClass(ExchangeDocumentBag::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(ExchangeComponent::class)]
class RespuestaEnvioTest extends TestCase
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

        $this->certificate = (new CertificateFaker(new CertificateLoader()))->createFake();
    }

    /**
     * Generates the `RespuestaDTE` XML with `RecepcionEnvio`.
     *
     * Use case: the receiver acknowledges receipt of the full shipment (EnvioDTE)
     * and reports reception status for the envelope and each DTE included in it.
     * Used to indicate whether the shipment was received correctly or rejected
     * due to schema error, signature error, wrong receiver RUT, etc.
     *
     * Required fields in `recepcion_envio`:
     *   - `NmbEnvio`: name of the sent file.
     *   - `CodEnvio`: shipment tracking code.
     *   - `EnvioDTEID`: root node ID of the received EnvioDTE.
     *   - `Digest`: base64 SHA1 hash of the received EnvioDTE.
     *   - `RutEmisor`: EnvioDTE issuer RUT.
     *   - `RutReceptor`: EnvioDTE receiver RUT.
     *   - `EstadoRecepEnv`: status code (0 = compliant, 1..99 = rejected).
     *   - `RecepEnvGlosa`: status description.
     *   - `NroDTE`: number of DTE included in the shipment.
     *   - `RecepcionDTE`: list with reception status of each DTE.
     */
    public function testRecepcionEnvio(): void
    {
        $bag = new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::RESPUESTA_ENVIO,
            caratula: [
                'RutResponde' => '76192083-9',
                'RutRecibe' => '88888888-8',
                'IdRespuesta' => 1,
            ],
            data: [
                'recepcion_envio' => [
                    [
                        'NmbEnvio' => 'EnvioDTE_88888888-8.xml',
                        'CodEnvio' => 1,
                        'EnvioDTEID' => 'SetDoc',
                        'Digest' => base64_encode(sha1('EnvioDTE_88888888-8', true)),
                        'RutEmisor' => '88888888-8',
                        'RutReceptor' => '76192083-9',
                        'EstadoRecepEnv' => 0,
                        'RecepEnvGlosa' => 'Envío Recibido Conforme',
                        'NroDTE' => 1,
                        'RecepcionDTE' => [
                            'TipoDTE' => 33,
                            'Folio' => 1,
                            'FchEmis' => '2024-01-15',
                            'RUTEmisor' => '88888888-8',
                            'RUTRecep' => '76192083-9',
                            'MntTotal' => 100000,
                            'EstadoRecepDTE' => 0,
                            'RecepDTEGlosa' => 'DTE Recibido OK',
                        ],
                    ],
                ],
            ],
            certificate: $this->certificate,
        );

        $document = $this->worker->buildRespuestaEnvio($bag);

        $this->assertInstanceOf(RespuestaEnvio::class, $document);
        $this->assertSame('LibreDTE_ResultadoEnvio', $document->getId());
        $this->assertTrue($document->isRecepcionEnvio());
        $this->assertFalse($document->isResultadoDTE());

        $this->worker->validateSchema($document);
        $results = $this->worker->validateSignature($document);
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->isValid());
    }

    /**
     * Generates the `RespuestaDTE` XML with `ResultadoDTE`.
     *
     * Use case: the receiver reports the commercial validation outcome for each
     * DTE received individually (not the envelope as a whole).
     * Used to indicate whether each DTE was accepted, accepted with discrepancies,
     * or rejected, along with the reason.
     *
     * Required fields in `resultado_dte`:
     *   - `TipoDTE`: DTE type code.
     *   - `Folio`: DTE folio number.
     *   - `FchEmis`: issue date (YYYY-MM-DD).
     *   - `RUTEmisor`: DTE issuer RUT.
     *   - `RUTRecep`: DTE receiver RUT.
     *   - `MntTotal`: DTE total amount.
     *   - `CodEnvio`: tracking code of the shipment that contained the DTE.
     *   - `EstadoDTE`: result code (0 = accepted, 1 = with discrepancies, 2 = rejected).
     *   - `EstadoDTEGlosa`: result description.
     */
    public function testResultadoDte(): void
    {
        $bag = new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::RESPUESTA_ENVIO,
            caratula: [
                'RutResponde' => '76192083-9',
                'RutRecibe' => '88888888-8',
                'IdRespuesta' => 1,
            ],
            data: [
                'resultado_dte' => [
                    [
                        'TipoDTE' => 33,
                        'Folio' => 1,
                        'FchEmis' => '2024-01-15',
                        'RUTEmisor' => '88888888-8',
                        'RUTRecep' => '76192083-9',
                        'MntTotal' => 100000,
                        'CodEnvio' => 1,
                        'EstadoDTE' => 0,
                        'EstadoDTEGlosa' => 'ACEPTADO OK',
                    ],
                ],
            ],
            certificate: $this->certificate,
        );

        $document = $this->worker->buildRespuestaEnvio($bag);

        $this->assertInstanceOf(RespuestaEnvio::class, $document);
        $this->assertSame('LibreDTE_ResultadoEnvio', $document->getId());
        $this->assertFalse($document->isRecepcionEnvio());
        $this->assertTrue($document->isResultadoDTE());

        $this->worker->validateSchema($document);
        $results = $this->worker->validateSignature($document);
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->isValid());
    }
}
