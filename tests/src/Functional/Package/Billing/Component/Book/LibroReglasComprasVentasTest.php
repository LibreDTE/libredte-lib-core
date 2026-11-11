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
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Value\ValueType;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\BookComponent;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LibroComprasVentasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroComprasVentas;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use libredte\lib\Core\Package\Billing\Component\Book\Support\BookBag;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\AbstractLibroComprasVentasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroVentas\BuilderStrategy as VentasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractLibroComprasVentasArrayLoaderStrategy;
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
use Throwable;

/**
 * Reglas de los libros de ventas y compras que no valida el esquema: los datos
 * de la carátula que exigen el libro especial y el de rectificación, y los tipos
 * de documento que el libro de ventas deja fuera del detalle.
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
#[CoversClass(VentasBuilderStrategy::class)]
#[CoversClass(AbstractLibroComprasVentasArrayLoaderStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(AutorizacionDte::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(LibroComprasVentas::class)]
class LibroReglasComprasVentasTest extends TestCase
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
     * @return array{caratula: array<string, mixed>, detalle: array<int, array<string, mixed>>}
     */
    private function input(TipoLibro $tipo): array
    {
        $fixture = $tipo === TipoLibro::VENTAS ? 'books/libro_ventas.php' : 'books/libro_compras.php';
        $cases = ArrayReader::fromValue(require self::getFixturesPath($fixture))->toMap();
        $input = ArrayReader::fromArray(array_values($cases))->requireReader(0)->requireReader('input');

        return ['caratula' => $input->requireMap('caratula'), 'detalle' => $input->requireIndexedOf('detalle', ValueType::map())];
    }

    /**
     * @param array<string, mixed> $caratula What is added to the cover of the fixture.
     * @param array<int, array<string, mixed>>|null $detalle Another detail, instead of the one of the fixture.
     * @param array<string, mixed> $options
     */
    private function build(
        TipoLibro $tipo,
        array $caratula = [],
        ?array $detalle = null,
        array $options = []
    ): BookBagInterface {
        $input = $this->input($tipo);

        $bag = $this->loader->load(new BookBag(
            tipo: $tipo,
            caratula: $caratula + $input['caratula'],
            detalle: $detalle ?? $input['detalle'],
            options: $options,
            certificate: $this->certificate,
            emisor: $this->emisor,
        ));
        $this->builder->build($bag);

        return $bag;
    }

    private function xmlOf(BookBagInterface $bag): string
    {
        $book = $bag->getBook();
        $this->assertInstanceOf(LibroComprasVentasInterface::class, $book);

        return $book->saveXml();
    }

    /**
     * @param array<string, mixed> $caratula
     */
    private function assertCoverRefused(TipoLibro $tipo, array $caratula, string $type, string $field): void
    {
        try {
            $this->build($tipo, $caratula);
            $this->fail('A book without ' . $field . ' was built.');
        } catch (BookException $e) {
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertSame(
                'The cover of a book of type ' . $type . ' must have ' . $field . '.',
                $e->getPrevious()->getMessage()
            );
        }
    }

    /**
     * @return array<string, array{TipoLibro}>
     */
    public static function salesAndPurchases(): array
    {
        return [
            'sales' => [TipoLibro::VENTAS],
            'purchases' => [TipoLibro::COMPRAS],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function missingValues(): array
    {
        return [
            'not given' => ['__not_given__'],
            'null' => [null],
            'false' => [false],
            'empty' => [''],
        ];
    }

    #[DataProvider('salesAndPurchases')]
    public function testASpecialBookWithoutTheNotificationFolioIsRefused(TipoLibro $tipo): void
    {
        $this->assertCoverRefused($tipo, ['TipoLibro' => 'ESPECIAL'], 'ESPECIAL', 'FolioNotificacion');
    }

    #[DataProvider('missingValues')]
    public function testWhatIsNullFalseOrEmptyIsNotTheNotificationFolio(mixed $value): void
    {
        $caratula = ['TipoLibro' => 'ESPECIAL'];
        if ($value !== '__not_given__') {
            $caratula['FolioNotificacion'] = $value;
        }

        $this->assertCoverRefused(TipoLibro::VENTAS, $caratula, 'ESPECIAL', 'FolioNotificacion');
    }

    #[DataProvider('salesAndPurchases')]
    public function testASpecialBookWithTheNotificationFolioIsBuilt(TipoLibro $tipo): void
    {
        $bag = $this->build($tipo, ['TipoLibro' => 'ESPECIAL', 'FolioNotificacion' => 102006]);

        $this->assertStringContainsString('<FolioNotificacion>102006</FolioNotificacion>', $this->xmlOf($bag));
        $this->validator->validateSchema($bag);
    }

    #[DataProvider('salesAndPurchases')]
    public function testARectificationBookWithoutTheAuthorizationCodeIsRefused(TipoLibro $tipo): void
    {
        $this->assertCoverRefused($tipo, ['TipoLibro' => 'RECTIFICA'], 'RECTIFICA', 'CodAutRec');
        $this->assertCoverRefused($tipo, ['TipoLibro' => 'RECTIFICA', 'CodAutRec' => ''], 'RECTIFICA', 'CodAutRec');
    }

    #[DataProvider('salesAndPurchases')]
    public function testARectificationBookWithTheAuthorizationCodeIsBuilt(TipoLibro $tipo): void
    {
        $bag = $this->build($tipo, ['TipoLibro' => 'RECTIFICA', 'CodAutRec' => 'ABC123']);

        $this->assertStringContainsString('<CodAutRec>ABC123</CodAutRec>', $this->xmlOf($bag));
        $this->validator->validateSchema($bag);
    }

    #[DataProvider('salesAndPurchases')]
    public function testAMonthlyBookNeedsNeitherOfThem(TipoLibro $tipo): void
    {
        $bag = $this->build($tipo, ['TipoLibro' => 'MENSUAL']);

        $this->assertStringNotContainsString('<FolioNotificacion>', $this->xmlOf($bag));
        $this->assertStringNotContainsString('<CodAutRec>', $this->xmlOf($bag));
        $this->validator->validateSchema($bag);
    }

    public function testTheSimplifiedBookHasTheSameRules(): void
    {
        try {
            $this->build(TipoLibro::VENTAS, ['TipoLibro' => 'ESPECIAL'], options: ['builder' => ['simplificado' => true, 'detalle' => false]]);
            $this->fail('A simplified special book without folio was built.');
        } catch (BookException $e) {
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertSame('The cover of a book of type ESPECIAL must have FolioNotificacion.', $e->getPrevious()->getMessage());
        }
    }

    /**
     * A row of the detail of the fixture with another type and number.
     *
     * @return array<string, mixed>
     */
    private function row(TipoLibro $tipo, int $tpoDoc, int $nroDoc): array
    {
        $row = $this->input($tipo)['detalle'][0];
        $row['TpoDoc'] = $tpoDoc;
        $row['NroDoc'] = $nroDoc;

        return $row;
    }

    /**
     * @return array<string, array{int}>
     */
    public static function typesLeftOutOfTheDetail(): array
    {
        $types = [];
        foreach (VentasBuilderStrategy::TIPOS_FUERA_DEL_DETALLE as $type) {
            $types['type ' . $type] = [$type];
        }

        return $types;
    }

    #[DataProvider('typesLeftOutOfTheDetail')]
    public function testTheSalesBookLeavesCertainTypesOutOfTheDetailButCountsThemInTheSummary(int $type): void
    {
        $input = $this->input(TipoLibro::VENTAS);
        $bag = $this->build(TipoLibro::VENTAS, detalle: [...$input['detalle'], $this->row(TipoLibro::VENTAS, $type, 9000)]);

        $xml = $this->xmlOf($bag);
        $this->assertSame(count($input['detalle']), substr_count($xml, '<Detalle>'));
        $this->assertSame(0, preg_match('#<Detalle>.*?<TpoDoc>' . $type . '</TpoDoc>.*?</Detalle>#s', $xml));
        $this->assertSame(1, preg_match('#<TotalesPeriodo>\s*<TpoDoc>' . $type . '</TpoDoc>\s*<TotDoc>1</TotDoc>#', $xml));
    }

    public function testTheSalesBookWithTicketsPassesTheSchemaAndIsSigned(): void
    {
        $input = $this->input(TipoLibro::VENTAS);
        $bag = $this->build(TipoLibro::VENTAS, detalle: [...$input['detalle'], $this->row(TipoLibro::VENTAS, 39, 9000), $this->row(TipoLibro::VENTAS, 41, 9001)]);

        $this->validator->validateSchema($bag);
        $this->assertTrue($this->validator->validateSignature($bag)->isValid());
    }

    public function testTheDetailThatTheBagKeepsHasAllTheRows(): void
    {
        $input = $this->input(TipoLibro::VENTAS);
        $bag = $this->build(TipoLibro::VENTAS, detalle: [...$input['detalle'], $this->row(TipoLibro::VENTAS, 39, 9000)]);

        $this->assertCount(count($input['detalle']) + 1, $bag->getDetalle());
    }

    public function testTheSalesBookWithOnlyTypesLeftOutHasNoDetail(): void
    {
        $bag = $this->build(TipoLibro::VENTAS, detalle: [$this->row(TipoLibro::VENTAS, 39, 9000)]);

        $this->assertStringNotContainsString('<Detalle>', $this->xmlOf($bag));
        $this->validator->validateSchema($bag);
    }

    public function testThePurchasesBookKeepsAllTheTypesInTheDetail(): void
    {
        $input = $this->input(TipoLibro::COMPRAS);
        $bag = $this->build(TipoLibro::COMPRAS, detalle: [...$input['detalle'], $this->row(TipoLibro::COMPRAS, 39, 9000)]);

        $this->assertSame(count($input['detalle']) + 1, substr_count($this->xmlOf($bag), '<Detalle>'));
    }
}
