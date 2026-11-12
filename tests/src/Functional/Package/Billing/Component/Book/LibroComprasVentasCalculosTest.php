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

use Derafu\Array\ArrayPath;
use Derafu\Array\ArrayReader;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\BookComponent;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroComprasVentas;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoOperacion;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use libredte\lib\Core\Package\Billing\Component\Book\Support\BookBag;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\AbstractLibroComprasVentasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractLibroComprasVentasArrayLoaderStrategy;
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
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Cálculos de la carga y de la construcción de los libros de compras y ventas.
 */
#[CoversClass(AbstractBook::class)]
#[CoversClass(LibroComprasVentas::class)]
#[CoversClass(TipoLibro::class)]
#[CoversClass(TipoOperacion::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(AbstractLibroComprasVentasBuilderStrategy::class)]
#[CoversClass(LoaderWorker::class)]
#[CoversClass(AbstractArrayLoaderStrategy::class)]
#[CoversClass(AbstractLibroComprasVentasArrayLoaderStrategy::class)]
#[CoversClass(ImpuestoAdicionalRetencion::class)]
#[CoversClass(ImpuestoAdicionalRetencionRepository::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(AutorizacionDte::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(BookComponent::class)]
#[CoversClass(BookBag::class)]
#[CoversClass(BookException::class)]
class LibroComprasVentasCalculosTest extends TestCase
{
    private LoaderWorkerInterface $loader;

    private BuilderWorkerInterface $builder;

    private EmisorInterface $emisor;

    protected function setUp(): void
    {
        $component = Application::getInstance()->getPackageRegistry()->getBillingPackage()->getBookComponent();
        $this->loader = $component->getLoaderWorker();
        $this->builder = $component->getBuilderWorker();
        $this->emisor = (new EmisorFactory())->create([
            'rut' => '76192083-9',
            'razon_social' => 'SASCO SpA',
            'autorizacion_dte' => ['fecha_resolucion' => '2014-08-22', 'numero_resolucion' => 80],
        ]);
    }

    /**
     * @param list<array<string, mixed>> $detalle
     */
    private function bag(TipoLibro $tipo, array $detalle): BookBag
    {
        $bag = new BookBag(
            tipo: $tipo,
            caratula: ['PeriodoTributario' => '2024-01'],
            detalle: $detalle,
            certificate: (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9'),
            emisor: $this->emisor,
        );
        $this->loader->load($bag);
        $this->builder->build($bag);

        return $bag;
    }

    /**
     * Totales del período de un tipo de documento, como los dejó el libro.
     *
     * @param list<array<string, mixed>> $detalle
     */
    private function totales(TipoLibro $tipo, array $detalle, int $tpoDoc = 33): ArrayReader
    {
        $resumen = $this->bag($tipo, $detalle)->requireBook()->getResumen();
        $totales = ArrayReader::fromArray(
            ArrayPath::wrap(ArrayReader::fromArray($resumen)->requireMap('0'), 'TotalesPeriodo')
        )->requireList('TotalesPeriodo');

        foreach ($totales as $item) {
            $reader = ArrayReader::fromValue($item);
            if ($reader->requireAsInt('TpoDoc') === $tpoDoc) {
                return $reader;
            }
        }

        $this->fail('The book has no totals for the document type ' . $tpoDoc . '.');
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private static function doc(int $nro, array $extra = []): array
    {
        return array_merge([
            'TpoDoc' => 33, 'NroDoc' => $nro, 'FchDoc' => '2024-01-05', 'RUTDoc' => '11111111-1', 'TasaImp' => 19,
        ], $extra);
    }

    public function testDocumentsWithoutTypeAreNotCountedInTheSummary(): void
    {
        $totales = $this->totales(TipoLibro::COMPRAS, [
            self::doc(1, ['MntNeto' => 1000]),
            self::doc(2, ['TpoDoc' => 0, 'MntNeto' => 5000]),
        ]);

        $this->assertSame(1, $totales->requireAsInt('TotDoc'));
        $this->assertSame(1000, $totales->requireAsInt('TotMntNeto'));
    }

    public function testAnnulledDocumentsAreCountedButTheirAmountsAreNot(): void
    {
        $bag = $this->bag(TipoLibro::COMPRAS, [
            self::doc(1, ['MntNeto' => 1000]),
            ['TpoDoc' => 33, 'NroDoc' => 2, 'Anulado' => 'A', 'MntNeto' => 99999],
        ]);

        $totales = $bag->requireBook()->getResumen();
        $reader = ArrayReader::fromArray($totales)->requireReader('0.TotalesPeriodo');
        $this->assertSame(2, $reader->requireAsInt('TotDoc'));
        $this->assertSame(1, $reader->requireAsInt('TotAnulado'));
        $this->assertSame(1000, $reader->requireAsInt('TotMntNeto'));
        $this->assertSame(190, $reader->requireAsInt('TotMntIVA'));
        $this->assertSame(1190, $reader->requireAsInt('TotMntTotal'));
    }

    public function testAnnulledDocumentKeepsOnlyItsIdentification(): void
    {
        $bag = $this->bag(TipoLibro::COMPRAS, [
            self::doc(1, ['MntNeto' => 1000]),
            self::doc(2, ['Anulado' => 'A', 'MntNeto' => 99999, 'RznSoc' => 'Proveedor']),
        ]);

        $anulado = ArrayReader::fromArray($bag->requireBook()->getDetalle())->requireReader('1');

        $this->assertSame(33, $anulado->requireAsInt('TpoDoc'));
        $this->assertSame(2, $anulado->requireAsInt('NroDoc'));
        $this->assertSame('A', $anulado->requireString('Anulado'));
        $this->assertNull($anulado->getString('MntNeto'));
        $this->assertNull($anulado->getString('RznSoc'));
        $this->assertNull($anulado->getString('RUTDoc'));
    }

    public function testFixedAssetAmountsAreAddedUp(): void
    {
        $totales = $this->totales(TipoLibro::COMPRAS, [
            self::doc(1, ['MntNeto' => 1000, 'MntActivoFijo' => 400, 'MntIVAActivoFijo' => 76]),
            self::doc(2, ['MntNeto' => 2000, 'MntActivoFijo' => 600, 'MntIVAActivoFijo' => 114]),
            self::doc(3, ['MntNeto' => 500]),
        ]);

        $this->assertSame(1000, $totales->requireAsInt('TotMntActivoFijo'));
        $this->assertSame(190, $totales->requireAsInt('TotMntIVAActivoFijo'));
        $this->assertSame(3500, $totales->requireAsInt('TotMntNeto'));
    }

    public function testLatePaymentAndAmountWithoutCreditAreAddedUp(): void
    {
        $totales = $this->totales(TipoLibro::COMPRAS, [
            self::doc(1, ['MntNeto' => 1000, 'IVAFueraPlazo' => 7, 'MntSinCred' => 3]),
            self::doc(2, ['MntNeto' => 1000, 'IVAFueraPlazo' => 5, 'MntSinCred' => 2]),
            self::doc(3, ['MntNeto' => 1000]),
        ]);

        $this->assertSame(12, $totales->requireAsInt('TotIVAFueraPlazo'));
        $this->assertSame(5, $totales->requireAsInt('TotImpSinCredito'));
    }

    public function testCommonVatIsCalculatedFromTheProportionFactor(): void
    {
        $bag = $this->bag(TipoLibro::COMPRAS, [self::doc(1, ['MntNeto' => 100000, 'FctProp' => 60])]);

        $detalle = ArrayReader::fromArray($bag->requireBook()->getDetalle())->requireReader('0');
        $this->assertSame(19000, $detalle->requireAsInt('IVAUsoComun'));
        $this->assertSame(0, $detalle->requireAsInt('MntIVA'));
        $this->assertSame(119000, $detalle->requireAsInt('MntTotal'));
        $this->assertNull($detalle->getString('FctProp'));

        $totales = ArrayReader::fromArray($bag->requireBook()->getResumen())->requireReader('0.TotalesPeriodo');
        $this->assertSame(19000, $totales->requireAsInt('TotIVAUsoComun'));
        $this->assertSame(0.6, $totales->requireAsNumber('FctProp'));
        $this->assertSame(11400, $totales->requireAsInt('TotCredIVAUsoComun'));
    }

    public function testCommonVatCreditOfSeveralDocumentsIsTheSumOfEachOne(): void
    {
        $totales = $this->totales(TipoLibro::COMPRAS, [
            self::doc(1, ['MntNeto' => 100000, 'FctProp' => 50]),
            self::doc(2, ['MntNeto' => 10000, 'FctProp' => 50]),
        ]);

        $this->assertSame(19000 + 1900, $totales->requireAsInt('TotIVAUsoComun'));
        $this->assertSame(9500 + 950, $totales->requireAsInt('TotCredIVAUsoComun'));
    }

    public function testVatIsCalculatedWhenItIsMissingAndRoundedToTheNearestPeso(): void
    {
        $bag = $this->bag(TipoLibro::COMPRAS, [
            self::doc(1, ['MntNeto' => 333]),
            self::doc(2, ['MntNeto' => 1005]),
            self::doc(3, ['MntNeto' => 100000]),
        ]);

        $detalle = ArrayReader::fromArray($bag->requireBook()->getDetalle());
        $this->assertSame(63, $detalle->requireAsInt('0.MntIVA'));
        $this->assertSame(191, $detalle->requireAsInt('1.MntIVA'));
        $this->assertSame(19000, $detalle->requireAsInt('2.MntIVA'));
        $this->assertSame(396, $detalle->requireAsInt('0.MntTotal'));
    }

    public function testTotalIsCalculatedWithExemptAndNetAmountsAndVat(): void
    {
        $bag = $this->bag(TipoLibro::COMPRAS, [self::doc(1, ['MntExe' => 500, 'MntNeto' => 1000])]);

        $this->assertSame(1690, ArrayReader::fromArray($bag->requireBook()->getDetalle())->requireAsInt('0.MntTotal'));
    }

    public function testNotRecoverableVatIsAddedUpByCode(): void
    {
        $totales = $this->totales(TipoLibro::COMPRAS, [
            self::doc(1, ['MntNeto' => 1000, 'IVANoRec' => [['CodIVANoRec' => 1, 'MntIVANoRec' => 190]]]),
            self::doc(2, ['MntNeto' => 2000, 'IVANoRec' => [['CodIVANoRec' => 1, 'MntIVANoRec' => 380]]]),
            self::doc(3, ['MntNeto' => 3000, 'IVANoRec' => [['CodIVANoRec' => 4, 'MntIVANoRec' => 570]]]),
        ]);

        $porCodigo = [];
        foreach (ArrayReader::fromArray(ArrayPath::wrap($totales->toMap(), 'TotIVANoRec'))->requireList('TotIVANoRec') as $item) {
            $reader = ArrayReader::fromValue($item);
            $porCodigo[$reader->requireAsInt('CodIVANoRec')] = [
                $reader->requireAsInt('TotOpIVANoRec'),
                $reader->requireAsInt('TotMntIVANoRec'),
            ];
        }

        $this->assertSame([1 => [2, 570], 4 => [1, 570]], $porCodigo);
    }

    public function testOtherTaxesAreAddedUpByCode(): void
    {
        $totales = $this->totales(TipoLibro::COMPRAS, [
            self::doc(1, ['MntNeto' => 1000, 'OtrosImp' => [['CodImp' => 27, 'TasaImp' => 5, 'MntImp' => 50]]]),
            self::doc(2, ['MntNeto' => 2000, 'OtrosImp' => [['CodImp' => 27, 'TasaImp' => 5, 'MntImp' => 100]]]),
        ]);

        $otros = ArrayReader::fromArray(ArrayPath::wrap($totales->toMap(), 'TotOtrosImp'))->requireList('TotOtrosImp');
        $this->assertCount(1, $otros);
        $reader = ArrayReader::fromValue($otros[0]);
        $this->assertSame(27, $reader->requireAsInt('CodImp'));
        $this->assertSame(150, $reader->requireAsInt('TotMntImp'));
    }

    public function testBusinessNameIsCutToFiftyCharacters(): void
    {
        $bag = $this->bag(TipoLibro::COMPRAS, [self::doc(1, ['MntNeto' => 1000, 'RznSoc' => str_repeat('Ñ', 80)])]);

        $this->assertSame(
            str_repeat('Ñ', 50),
            ArrayReader::fromArray($bag->requireBook()->getDetalle())->requireString('0.RznSoc')
        );
    }

    public function testDocumentWithoutNetAmountHasNoVatFields(): void
    {
        $bag = $this->bag(TipoLibro::COMPRAS, [self::doc(1, ['MntExe' => 1000])]);

        $detalle = ArrayReader::fromArray($bag->requireBook()->getDetalle())->requireReader('0');
        $this->assertNull($detalle->getString('MntNeto'));
        $this->assertNull($detalle->getString('MntIVA'));
        $this->assertNull($detalle->getString('TasaImp'));
        $this->assertSame(1000, $detalle->requireAsInt('MntTotal'));
    }

    public function testBranchCodeThatIsZeroIsRemoved(): void
    {
        $bag = $this->bag(TipoLibro::COMPRAS, [self::doc(1, ['MntNeto' => 1000, 'CdgSIISucur' => 0])]);

        $this->assertNull(ArrayReader::fromArray($bag->requireBook()->getDetalle())->requireReader('0')->getString('CdgSIISucur'));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function provideNonNumericValues(): array
    {
        return [
            'neto' => [['MntNeto' => 'mil pesos'], 'MntNeto'],
            'tasa' => [['MntNeto' => 1000, 'TasaImp' => 'diecinueve'], 'TasaImp'],
            'iva no recuperable' => [['MntNeto' => 1000, 'IVANoRec' => [['CodIVANoRec' => 1, 'MntIVANoRec' => 'x']]], 'MntIVANoRec'],
            'otros impuestos' => [['MntNeto' => 1000, 'OtrosImp' => [['CodImp' => 27, 'TasaImp' => 5, 'MntImp' => 'x']]], 'MntImp'],
        ];
    }

    /**
     * @param array<string, mixed> $extra
     */
    #[DataProvider('provideNonNumericValues')]
    public function testNonNumericAmountsAreReportedWithTheDocumentAndTheField(array $extra, string $field): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::COMPRAS,
            caratula: ['PeriodoTributario' => '2024-01'],
            detalle: [self::doc(77, $extra)],
            emisor: $this->emisor,
        );

        try {
            $this->loader->load($bag);
            $this->fail('A BookException was expected.');
        } catch (BookException $e) {
            $this->assertStringContainsString($field, $e->getMessage());
            $this->assertStringContainsString('33/77', $e->getMessage());
        }
    }
}
