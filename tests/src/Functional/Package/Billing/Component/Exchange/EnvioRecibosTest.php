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
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\EnvioRecibos;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\TipoDocumentoRespuesta;
use libredte\lib\Core\Package\Billing\Component\Exchange\ExchangeComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildEnvioRecibosJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponseWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(DocumentResponseWorker::class)]
#[CoversClass(BuildEnvioRecibosJob::class)]
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

    /**
     * @return array<string, array{
     *     recibos: list<array{
     *         TipoDoc: int,
     *         Folio: int,
     *         FchEmis: string,
     *         RUTEmisor: string,
     *         RUTRecep: string,
     *         MntTotal: int,
     *         Recinto: string,
     *         RutFirma: string
     *     }>,
     *     expected: array{
     *         id: string,
     *         ids_recibos: list<string>
     *     }
     * }>
     */
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
     * Generates the `EnvioRecibos` XML with goods or services receipts.
     *
     * Use case: the receiver of one or more DTE wants to certify compliant
     * receipt of the goods or services described in those documents.
     * Each `Recibo` is signed individually with ID `LibreDTE_T{tipo}F{folio}`
     * and `SetRecibos` is signed as a set with ID `LibreDTE_SetDteRecibidos`.
     *
     * Required fields per receipt:
     *   - `TipoDoc`: DTE type code.
     *   - `Folio`: DTE folio number.
     *   - `FchEmis`: issue date (YYYY-MM-DD).
     *   - `RUTEmisor`: DTE issuer RUT.
     *   - `RUTRecep`: DTE receiver RUT.
     *   - `MntTotal`: DTE total amount.
     *   - `Recinto`: place where receipt takes place.
     *   - `RutFirma`: RUT of whoever signs the receipt.
     * @param list<array{
     *     TipoDoc: int,
     *     Folio: int,
     *     FchEmis: string,
     *     RUTEmisor: string,
     *     RUTRecep: string,
     *     MntTotal: int,
     *     Recinto: string,
     *     RutFirma: string
     * }> $recibos
     * @param array{id: string, ids_recibos: list<string>} $expected
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
            data: $recibos,
            certificate: $this->certificate,
        );

        $document = $this->worker->buildEnvioRecibos($bag);

        $this->assertInstanceOf(EnvioRecibos::class, $document);
        $this->assertSame($expected['id'], $document->getId());

        $xml = $document->saveXml();
        foreach ($expected['ids_recibos'] as $idRecibo) {
            $this->assertStringContainsString($idRecibo, $xml);
        }

        $this->worker->validateSchema($document);

        $results = $this->worker->validateSignature($document);
        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertTrue($result->isValid());
        }
    }
}
