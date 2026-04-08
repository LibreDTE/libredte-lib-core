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
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\EnvioRecibos;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\TipoDocumentoRespuesta;
use libredte\lib\Core\Package\Billing\Component\Exchange\ExchangeComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildEnvioRecibosJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\ValidateJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponseWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(DocumentResponseWorker::class)]
#[CoversClass(BuildEnvioRecibosJob::class)]
#[CoversClass(ValidateJob::class)]
#[CoversClass(EnvioRecibos::class)]
#[CoversClass(AbstractExchangeDocument::class)]
#[CoversClass(ExchangeDocumentBag::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(ExchangeComponent::class)]
class EnvioRecibosTest extends TestCase
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

    public static function dataProviderForTestEnvioRecibos(): array
    {
        $reciboBase = [
            'TipoDoc' => 33,
            'Folio' => 1,
            'FchEmis' => '2024-01-15',
            'RUTEmisor' => '88888888-8',
            'RUTRecep' => '76192083-9',
            'MntTotal' => 100000,
            'Recinto' => 'Oficina central',
            'RutFirma' => '76192083-9',
        ];

        return [
            'un recibo' => [
                'recibos' => [$reciboBase],
                'expected' => [
                    'id' => 'LibreDTE_SetDteRecibidos',
                    'ids_recibos' => ['LibreDTE_T33F1'],
                ],
            ],
            'multiples recibos' => [
                'recibos' => [
                    $reciboBase,
                    array_merge($reciboBase, ['Folio' => 2, 'MntTotal' => 200000]),
                ],
                'expected' => [
                    'id' => 'LibreDTE_SetDteRecibidos',
                    'ids_recibos' => ['LibreDTE_T33F1', 'LibreDTE_T33F2'],
                ],
            ],
        ];
    }

    /**
     * Genera el XML `EnvioRecibos` con recibos de mercaderías o servicios.
     *
     * Caso de uso: el receptor de uno o más DTE quiere acreditar la recepción
     * conforme de las mercaderías o servicios descritos en esos documentos.
     * Cada `Recibo` se firma individualmente con el ID `LibreDTE_T{tipo}F{folio}`
     * y el `SetRecibos` se firma como conjunto con ID `LibreDTE_SetDteRecibidos`.
     *
     * Campos requeridos por recibo:
     *   - `TipoDoc`: código del tipo de DTE.
     *   - `Folio`: número de folio del DTE.
     *   - `FchEmis`: fecha de emisión (YYYY-MM-DD).
     *   - `RUTEmisor`: RUT del emisor del DTE.
     *   - `RUTRecep`: RUT del receptor del DTE.
     *   - `MntTotal`: monto total del DTE.
     *   - `Recinto`: lugar donde se materializa la recepción.
     *   - `RutFirma`: RUT de quien firma el recibo.
     */
    #[DataProvider('dataProviderForTestEnvioRecibos')]
    public function testEnvioRecibos(array $recibos, array $expected): void
    {
        $bag = new ExchangeDocumentBag(
            tipo: TipoDocumentoRespuesta::ENVIO_RECIBOS,
            caratula: [
                'RutResponde' => '76192083-9',
                'RutRecibe' => '88888888-8',
            ],
            datos: $recibos,
            certificate: $this->certificate,
        );

        $document = $this->worker->buildEnvioRecibos($bag);

        $this->assertInstanceOf(EnvioRecibos::class, $document);
        $this->assertSame($expected['id'], $document->getId());

        $xml = $document->getXml();
        foreach ($expected['ids_recibos'] as $idRecibo) {
            $this->assertStringContainsString($idRecibo, $xml);
        }

        $this->assertTrue($this->worker->validate($document));
    }
}
