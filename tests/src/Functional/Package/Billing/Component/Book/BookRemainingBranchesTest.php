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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Book;

use Derafu\Array\ArrayReader;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\BookComponent;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroComprasVentas;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\ResumenVentasDiarias;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Support\BookBag;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\AbstractLibroComprasVentasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroCompras\BuilderStrategy as LibroComprasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroGuias\BuilderStrategy as LibroGuiasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroVentas\BuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\ResumenVentasDiarias\BuilderStrategy as ResumenBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractLibroComprasVentasArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\LibroGuias\ArrayLoaderStrategy as LibroGuiasArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\ResumenVentasDiarias\ArrayLoaderStrategy as ResumenArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\LoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LibroComprasVentas::class)]
#[CoversClass(ResumenVentasDiarias::class)]
#[CoversClass(BuilderStrategy::class)]
#[CoversClass(ImpuestoAdicionalRetencion::class)]
#[CoversClass(ImpuestoAdicionalRetencionRepository::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(LoaderWorker::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBook::class)]
#[CoversClass(BookComponent::class)]
#[CoversClass(TipoLibro::class)]
#[CoversClass(BookBag::class)]
#[CoversClass(AbstractLibroComprasVentasBuilderStrategy::class)]
#[CoversClass(LibroComprasBuilderStrategy::class)]
#[CoversClass(LibroGuiasBuilderStrategy::class)]
#[CoversClass(ResumenBuilderStrategy::class)]
#[CoversClass(AbstractArrayLoaderStrategy::class)]
#[CoversClass(AbstractLibroComprasVentasArrayLoaderStrategy::class)]
#[CoversClass(LibroGuiasArrayLoaderStrategy::class)]
#[CoversClass(ResumenArrayLoaderStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(AutorizacionDte::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
class BookRemainingBranchesTest extends TestCase
{
    private LoaderWorkerInterface $loader;

    private BuilderWorkerInterface $builder;

    private CertificateInterface $certificate;

    private EmisorInterface $emisor;

    protected function setUp(): void
    {
        $component = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getBookComponent()
        ;

        $this->loader = $component->getLoaderWorker();
        $this->builder = $component->getBuilderWorker();

        $this->certificate = (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9');
        $this->emisor = (new EmisorFactory())->create([
            'rut' => '76192083-9',
            'razon_social' => 'SASCO SpA',
            'autorizacion_dte' => ['fecha_resolucion' => '2014-08-22', 'numero_resolucion' => 80],
        ]);
    }

    /**
     * @param array<string, mixed> $caratula
     * @param array<int, array<string, mixed>> $detalle
     */
    private function build(TipoLibro $tipo, array $caratula, array $detalle): BookBag
    {
        $bag = new BookBag(
            tipo: $tipo,
            caratula: $caratula,
            detalle: $detalle,
            certificate: $this->certificate,
            emisor: $this->emisor,
        );
        $this->loader->load($bag);
        $this->builder->build($bag);

        return $bag;
    }

    public function testBagTakesCaratulaAndDetalleFromTheBookWhenItHasNoneOfItsOwn(): void
    {
        $built = $this->build(TipoLibro::VENTAS, ['PeriodoTributario' => '2024-01'], [[
            'TpoDoc' => 33, 'NroDoc' => 1, 'FchDoc' => '2024-01-05', 'RUTDoc' => '11111111-1',
            'TasaImp' => 19, 'MntNeto' => 1000,
        ]]);

        $bag = new BookBag(TipoLibro::VENTAS);
        $bag->setBook($built->requireBook());

        $this->assertSame($built->requireBook()->getCaratula(), $bag->getCaratula());
        $this->assertSame($built->requireBook()->getDetalle(), $bag->getDetalle());
        $this->assertSame('76192083-9', $bag->getCaratula()['RutEmisorLibro']);
    }

    public function testPartiallyWithheldVatAndVehicleTaxAreTotalizedInTheBook(): void
    {
        $bag = $this->build(TipoLibro::COMPRAS, ['PeriodoTributario' => '2024-01'], [[
            'TpoDoc' => 33, 'NroDoc' => 10, 'FchDoc' => '2024-01-05', 'RUTDoc' => '11111111-1',
            'TasaImp' => 19, 'MntNeto' => 100000, 'MntIVA' => 19000,
            'OtrosImp' => ['CodImp' => 15, 'TasaImp' => 19, 'MntImp' => 10000],
            'ImpVehiculo' => 5000,
        ]]);

        $detalle = ArrayReader::fromArray($bag->requireBook()->getDetalle()[0]);
        $this->assertSame('10000', $detalle->requireAsString('IVARetParcial'));
        $this->assertSame('9000', $detalle->requireAsString('IVANoRetenido'));

        $xml = $bag->requireBook()->saveXml();
        $this->assertStringContainsString('<TotIVARetParcial>10000</TotIVARetParcial>', $xml);
        $this->assertStringContainsString('<TotIVANoRetenido>9000</TotIVANoRetenido>', $xml);
        $this->assertStringContainsString('<TotImpVehiculo>5000</TotImpVehiculo>', $xml);
    }

    public function testGuidesCalculateTheVatAndTheTotalAndCountTheAnnulledGuides(): void
    {
        $bag = $this->build(TipoLibro::GUIAS, ['PeriodoTributario' => '2024-01'], [
            [
                'Folio' => 1, 'FchDoc' => '2024-01-10', 'RUTDoc' => '12345678-9', 'RznSoc' => 'Cliente',
                'TpoOper' => 1, 'MntNeto' => 100000, 'TasaImp' => 19,
            ],
            [
                'Folio' => 2, 'FchDoc' => '2024-01-11', 'Anulado' => 2,
            ],
        ]);

        $detalle = ArrayReader::fromArray($bag->requireBook()->getDetalle()[0]);
        $this->assertSame('19000', $detalle->requireAsString('IVA'));
        $this->assertSame('119000', $detalle->requireAsString('MntTotal'));

        $xml = $bag->requireBook()->saveXml();
        $this->assertStringContainsString('<TotGuiaAnulada>1</TotGuiaAnulada>', $xml);
        $this->assertStringContainsString('<TotGuiaVenta>1</TotGuiaVenta>', $xml);
    }

    public function testDailySalesSummarySkipsDetailsWithoutDocumentTypeAndFoliosWithoutNumber(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::RVD,
            caratula: ['FchInicio' => '2024-01-10', 'FchFinal' => '2024-01-10'],
            detalle: [['TpoDoc' => 39, 'FchDoc' => '2024-01-10', 'TasaImp' => 19, 'MntTotal' => 1190]],
            certificate: $this->certificate,
            emisor: $this->emisor,
        );
        $this->loader->load($bag);
        $bag->setDetalle([
            ['TpoDoc' => 0, 'FchDoc' => '2024-01-10', 'MntTotal' => 5],
            ['FchDoc' => '2024-01-10', 'MntTotal' => 7],
            ...$bag->getDetalle(),
        ]);
        $this->builder->build($bag);

        $xml = $bag->requireBook()->saveXml();
        $this->assertSame(1, substr_count($xml, '<TipoDocumento>'));
        $this->assertStringContainsString('<TipoDocumento>39</TipoDocumento>', $xml);
        $this->assertStringContainsString('<FoliosEmitidos>1</FoliosEmitidos>', $xml);
        $this->assertStringNotContainsString('<RangoUtilizados>', $xml);
    }
}
