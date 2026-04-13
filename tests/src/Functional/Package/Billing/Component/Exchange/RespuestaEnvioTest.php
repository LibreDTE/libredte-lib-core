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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Exchange;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\DocumentResponseWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\AbstractExchangeDocument;
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
     * Genera el XML `RespuestaDTE` con `RecepcionEnvio`.
     *
     * Caso de uso: el receptor acusa recibo del envío completo (EnvioDTE) y
     * reporta el estado de recepción del sobre y de cada DTE incluido en él.
     * Se usa para indicar si el envío fue recibido correctamente o rechazado
     * por error de schema, firma, RUT receptor incorrecto, etc.
     *
     * Campos requeridos en `recepcion_envio`:
     *   - `NmbEnvio`: nombre del archivo enviado.
     *   - `CodEnvio`: código de tracking del envío.
     *   - `EnvioDTEID`: ID del nodo raíz del EnvioDTE recibido.
     *   - `Digest`: hash SHA1 en base64 del EnvioDTE recibido.
     *   - `RutEmisor`: RUT del emisor del EnvioDTE.
     *   - `RutReceptor`: RUT del receptor del EnvioDTE.
     *   - `EstadoRecepEnv`: código de estado (0 = conforme, 1..99 = rechazado).
     *   - `RecepEnvGlosa`: descripción del estado.
     *   - `NroDTE`: cantidad de DTE incluidos en el envío.
     *   - `RecepcionDTE`: lista con el estado de recepción de cada DTE.
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
     * Genera el XML `RespuestaDTE` con `ResultadoDTE`.
     *
     * Caso de uso: el receptor comunica el resultado de la validación comercial
     * de cada DTE recibido de forma individual (no del sobre en su conjunto).
     * Se usa para indicar si cada DTE fue aceptado, aceptado con discrepancias
     * o rechazado, junto con el motivo.
     *
     * Campos requeridos en `resultado_dte`:
     *   - `TipoDTE`: código del tipo de DTE.
     *   - `Folio`: número de folio del DTE.
     *   - `FchEmis`: fecha de emisión (YYYY-MM-DD).
     *   - `RUTEmisor`: RUT del emisor del DTE.
     *   - `RUTRecep`: RUT del receptor del DTE.
     *   - `MntTotal`: monto total del DTE.
     *   - `CodEnvio`: código de tracking del envío que contenía el DTE.
     *   - `EstadoDTE`: código de resultado (0 = aceptado, 1 = con discrepancias, 2 = rechazado).
     *   - `EstadoDTEGlosa`: descripción del resultado.
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
