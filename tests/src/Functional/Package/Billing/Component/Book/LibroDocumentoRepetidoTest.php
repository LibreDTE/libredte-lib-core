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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Book;

use Derafu\Array\ArrayReader;
use Derafu\Array\Exception\DuplicateValueException;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\BookComponent;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroComprasVentas;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroGuias;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\ResumenVentasDiarias;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use libredte\lib\Core\Package\Billing\Component\Book\Support\BookBag;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\AbstractLibroComprasVentasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroCompras\BuilderStrategy as ComprasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroGuias\BuilderStrategy as GuiasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroVentas\BuilderStrategy as VentasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\ResumenVentasDiarias\BuilderStrategy as RvdBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractLibroComprasVentasArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\LibroGuias\ArrayLoaderStrategy as GuiasArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\ResumenVentasDiarias\ArrayLoaderStrategy as RvdArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\LoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Un documento no puede estar repetido en el detalle de un libro. El esquema no
 * lo valida, por eso lo valida el libro al construirse, con la clave de cada uno:
 * ventas, tipo y folio; compras, RUT del emisor, tipo y folio; guías, folio.
 */
#[CoversClass(LoaderWorker::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBook::class)]
#[CoversClass(BookComponent::class)]
#[CoversClass(TipoLibro::class)]
#[CoversClass(BookBag::class)]
#[CoversClass(AbstractLibroComprasVentasBuilderStrategy::class)]
#[CoversClass(ComprasBuilderStrategy::class)]
#[CoversClass(GuiasBuilderStrategy::class)]
#[CoversClass(AbstractLibroComprasVentasArrayLoaderStrategy::class)]
#[CoversClass(GuiasArrayLoaderStrategy::class)]
#[CoversClass(RvdBuilderStrategy::class)]
#[CoversClass(RvdArrayLoaderStrategy::class)]
#[CoversClass(ResumenVentasDiarias::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(AutorizacionDte::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(LibroComprasVentas::class)]
#[CoversClass(LibroGuias::class)]
#[UsesClass(VentasBuilderStrategy::class)]
class LibroDocumentoRepetidoTest extends TestCase
{
    private LoaderWorkerInterface $loader;

    private BuilderWorkerInterface $builder;

    private ValidatorWorkerInterface $validator;

    private CertificateInterface $certificate;

    private EmisorInterface $emisor;

    protected function setUp(): void
    {
        $book = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getBookComponent()
        ;

        $this->loader = $book->getLoaderWorker();
        $this->builder = $book->getBuilderWorker();
        $this->validator = $book->getValidatorWorker();

        $this->certificate = (new CertificateFaker(new CertificateLoader()))->createFake();
        $this->emisor = (new EmisorFactory())->create([
            'rut' => '76192083-9',
            'razon_social' => 'SASCO SpA',
            'autorizacion_dte' => [
                'fecha_resolucion' => '2014-08-22',
                'numero_resolucion' => 80,
            ],
        ]);
    }

    /**
     * The first row of the detail of the fixture of the book.
     *
     * @return array{caratula: array<string, mixed>, row: array<string, mixed>}
     */
    private function fixture(TipoLibro $tipo): array
    {
        $fixture = match ($tipo) {
            TipoLibro::VENTAS => 'books/libro_ventas.php',
            TipoLibro::COMPRAS => 'books/libro_compras.php',
            TipoLibro::RVD => 'books/resumen_ventas_diarias.php',
            default => 'books/libro_guias.php',
        };
        $cases = ArrayReader::fromValue(require self::getFixturesPath($fixture))->toMap();
        $input = ArrayReader::fromArray(array_values($cases))->requireReader(0)->requireReader('input');

        return ['caratula' => $input->requireMap('caratula'), 'row' => $input->requireReader('detalle')->requireMap(0)];
    }

    /**
     * A row of the fixture with other values.
     *
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    private function row(TipoLibro $tipo, array $changes = []): array
    {
        return $changes + $this->fixture($tipo)['row'];
    }

    /**
     * @param array<int, array<string, mixed>> $detalle
     */
    private function build(TipoLibro $tipo, array $detalle): BookBagInterface
    {
        $bag = $this->loader->load(new BookBag(
            tipo: $tipo,
            caratula: $this->fixture($tipo)['caratula'],
            detalle: $detalle,
            certificate: $this->certificate,
            emisor: $this->emisor,
        ));
        $this->builder->build($bag);

        return $bag;
    }

    /**
     * @param array<int, array<string, mixed>> $detalle
     * @param array<int, mixed> $values
     */
    private function assertRepeated(TipoLibro $tipo, array $detalle, int $index, int $first, array $values): DuplicateValueException
    {
        try {
            $this->build($tipo, $detalle);
            $this->fail('A book with a repeated document was built.');
        } catch (BookException $e) {
            // The worker wraps the error of the builder: the one of the package is the previous one.
            $previous = $e->getPrevious();
            $this->assertInstanceOf(DuplicateValueException::class, $previous);
            $this->assertSame($index, $previous->getIndex());
            $this->assertSame($first, $previous->getFirst());
            $this->assertSame($values, $previous->getValues());

            return $previous;
        }
    }

    public function testASalesDocumentWithTheSameTypeAndFolioIsRepeated(): void
    {
        $row = $this->row(TipoLibro::VENTAS, ['TpoDoc' => 33, 'NroDoc' => 21]);

        $e = $this->assertRepeated(TipoLibro::VENTAS, [$row, $row], 1, 0, [33, 21]);

        $this->assertSame('The item with key 1 repeats the item with key 0 (TpoDoc = 33, NroDoc = 21).', $e->getMessage());
    }

    public function testSalesDocumentsOfAnotherTypeOrFolioAreNotRepeated(): void
    {
        $bag = $this->build(TipoLibro::VENTAS, [
            $this->row(TipoLibro::VENTAS, ['TpoDoc' => 33, 'NroDoc' => 21]),
            $this->row(TipoLibro::VENTAS, ['TpoDoc' => 61, 'NroDoc' => 21]),
            $this->row(TipoLibro::VENTAS, ['TpoDoc' => 33, 'NroDoc' => 22]),
        ]);

        $this->validator->validateSchema($bag);
        $this->assertCount(3, $bag->getDetalle());
    }

    public function testTheRutOfTheIssuerDoesNotMakeAnotherSalesDocument(): void
    {
        $this->assertRepeated(TipoLibro::VENTAS, [
            $this->row(TipoLibro::VENTAS, ['TpoDoc' => 33, 'NroDoc' => 21, 'RUTDoc' => '11111111-1']),
            $this->row(TipoLibro::VENTAS, ['TpoDoc' => 33, 'NroDoc' => 21, 'RUTDoc' => '22222222-2']),
        ], 1, 0, [33, 21]);
    }

    public function testAPurchaseDocumentNeedsTheSameRutTypeAndFolioToBeRepeated(): void
    {
        $row = $this->row(TipoLibro::COMPRAS, ['TpoDoc' => 33, 'NroDoc' => 21, 'RUTDoc' => '11111111-1']);

        $e = $this->assertRepeated(TipoLibro::COMPRAS, [$row, $row], 1, 0, ['11111111-1', 33, 21]);

        $this->assertSame('The item with key 1 repeats the item with key 0 (RUTDoc = 11111111-1, TpoDoc = 33, NroDoc = 21).', $e->getMessage());
    }

    public function testTwoSuppliersCanHaveTheSameTypeAndFolioInPurchases(): void
    {
        $bag = $this->build(TipoLibro::COMPRAS, [
            $this->row(TipoLibro::COMPRAS, ['TpoDoc' => 33, 'NroDoc' => 21, 'RUTDoc' => '11111111-1']),
            $this->row(TipoLibro::COMPRAS, ['TpoDoc' => 33, 'NroDoc' => 21, 'RUTDoc' => '22222222-2']),
        ]);

        $this->validator->validateSchema($bag);
        $this->assertCount(2, $bag->getDetalle());
    }

    public function testAGuideWithTheSameFolioIsRepeated(): void
    {
        $row = $this->row(TipoLibro::GUIAS, ['Folio' => 4]);

        $e = $this->assertRepeated(TipoLibro::GUIAS, [$row, $row], 1, 0, [4]);

        $this->assertSame('The item with key 1 repeats the item with key 0 (Folio = 4).', $e->getMessage());
    }

    public function testGuidesWithAnotherFolioAreNotRepeated(): void
    {
        $bag = $this->build(TipoLibro::GUIAS, [
            $this->row(TipoLibro::GUIAS, ['Folio' => 4]),
            $this->row(TipoLibro::GUIAS, ['Folio' => 5]),
        ]);

        $this->validator->validateSchema($bag);
        $this->assertCount(2, $bag->getDetalle());
    }

    public function testACancelledDocumentIsRepeatedToo(): void
    {
        $this->assertRepeated(TipoLibro::GUIAS, [
            $this->row(TipoLibro::GUIAS, ['Folio' => 4]),
            $this->row(TipoLibro::GUIAS, ['Folio' => 4, 'Anulado' => 1]),
        ], 1, 0, [4]);
    }

    /**
     * @return array<string, array{mixed, mixed}>
     */
    public static function sameFolioWrittenDifferently(): array
    {
        return [
            'a number and a text' => [21, '21'],
            'zeros on the left' => [21, '0021'],
            'spaces' => ['21', ' 21 '],
        ];
    }

    #[DataProvider('sameFolioWrittenDifferently')]
    public function testTheFolioIsComparedWithoutTheWayItIsWritten(mixed $first, mixed $second): void
    {
        $this->assertRepeated(TipoLibro::VENTAS, [
            $this->row(TipoLibro::VENTAS, ['TpoDoc' => 33, 'NroDoc' => $first]),
            $this->row(TipoLibro::VENTAS, ['TpoDoc' => 33, 'NroDoc' => $second]),
        ], 1, 0, [33, $second]);
    }

    public function testADocumentOfTheDailySalesSummaryWithTheSameTypeAndFolioIsRepeated(): void
    {
        $row = $this->row(TipoLibro::RVD, ['TpoDoc' => 39, 'NroDoc' => 3]);

        $e = $this->assertRepeated(TipoLibro::RVD, [$row, $row], 1, 0, [39, 3]);

        $this->assertSame('The item with key 1 repeats the item with key 0 (TpoDoc = 39, NroDoc = 3).', $e->getMessage());
    }

    public function testTheSameFolioOfAnotherTypeIsNotRepeatedInTheDailySalesSummary(): void
    {
        $bag = $this->build(TipoLibro::RVD, [
            $this->row(TipoLibro::RVD, ['TpoDoc' => 39, 'NroDoc' => 3]),
            $this->row(TipoLibro::RVD, ['TpoDoc' => 41, 'NroDoc' => 3]),
        ]);

        $this->validator->validateSchema($bag);
        $this->assertCount(2, $bag->getDetalle());
    }

    public function testTheTypesLeftOutOfTheSalesDetailAreRepeatedToo(): void
    {
        $this->assertRepeated(TipoLibro::VENTAS, [
            $this->row(TipoLibro::VENTAS, ['TpoDoc' => 39, 'NroDoc' => 7]),
            $this->row(TipoLibro::VENTAS, ['TpoDoc' => 39, 'NroDoc' => 7]),
        ], 1, 0, [39, 7]);
    }
}
