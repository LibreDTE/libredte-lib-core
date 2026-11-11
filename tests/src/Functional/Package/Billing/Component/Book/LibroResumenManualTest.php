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
 * El resumen manual del libro de ventas: los totales de
 * documentos que no están en el detalle (boletas en papel, pagos electrónicos).
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
class LibroResumenManualTest extends TestCase
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
     * The input of the first case of the sales books: its cover and its detail
     * (documents of the type 33).
     *
     * @return array{caratula: array<string, mixed>, detalle: array<int, array<string, mixed>>}
     */
    private function input(string $fixture = 'books/libro_ventas.php'): array
    {
        $cases = ArrayReader::fromValue(require self::getFixturesPath($fixture))->toMap();
        $input = ArrayReader::fromArray(array_values($cases))->requireReader(0)->requireReader('input');

        return ['caratula' => $input->requireMap('caratula'), 'detalle' => $input->requireIndexedOf('detalle', ValueType::map())];
    }

    /**
     * @param array<int, array<string, mixed>> $resumen
     * @param array<string, mixed> $options
     */
    private function bag(
        array $resumen,
        TipoLibro $tipo = TipoLibro::VENTAS,
        array $options = [],
        string $fixture = 'books/libro_ventas.php'
    ): BookBagInterface {
        $input = $this->input($fixture);

        return $this->loader->load(new BookBag(
            tipo: $tipo,
            caratula: $input['caratula'],
            detalle: $input['detalle'],
            options: $options,
            certificate: $this->certificate,
            emisor: $this->emisor,
            resumen: $resumen,
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $resumen
     * @param array<string, mixed> $options
     */
    private function build(
        array $resumen,
        TipoLibro $tipo = TipoLibro::VENTAS,
        array $options = [],
        string $fixture = 'books/libro_ventas.php'
    ): BookBagInterface {
        $bag = $this->bag($resumen, $tipo, $options, $fixture);
        $this->builder->build($bag);

        return $bag;
    }

    /**
     * The types of document of the summary of the period, in the order of the XML.
     *
     * @return list<string>
     */
    private function typesOf(BookBagInterface $bag): array
    {
        $book = $bag->getBook();
        $this->assertInstanceOf(LibroComprasVentasInterface::class, $book);
        preg_match_all('#<TotalesPeriodo>\s*<TpoDoc>(\d+)</TpoDoc>#', $book->saveXml(), $matches);

        return $matches[1];
    }

    private function totalOf(BookBagInterface $bag, int $tpoDoc): string
    {
        $book = $bag->getBook();
        $this->assertInstanceOf(LibroComprasVentasInterface::class, $book);
        $this->assertSame(1, preg_match('#<TotalesPeriodo>\s*<TpoDoc>' . $tpoDoc . '</TpoDoc>.*?</TotalesPeriodo>#s', $book->saveXml(), $matches));

        return $matches[0];
    }

    /**
     * @param array<int, array<string, mixed>> $resumen
     */
    private function assertRefused(string $message, array $resumen, TipoLibro $tipo = TipoLibro::VENTAS): void
    {
        $bag = $this->bag($resumen, $tipo);

        try {
            $this->builder->build($bag);
            $this->fail('The manual summary was accepted.');
        } catch (BookException $e) {
            // The worker wraps the error of the builder: the message is in the previous one.
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertSame($message, $e->getPrevious()->getMessage());
        }
    }

    public function testTheBookWithoutManualSummaryHasOnlyTheTotalsOfTheDetail(): void
    {
        $this->assertSame(['33'], $this->typesOf($this->build([])));
    }

    public function testTheManualSummaryIsAddedToTheTotalsOfTheDetailInTheOrderOfTheTypes(): void
    {
        $bag = $this->build([
            ['TpoDoc' => 48, 'TotDoc' => 2, 'TotMntExe' => 1000, 'TotMntTotal' => 1000],
            ['TpoDoc' => 35, 'TotDoc' => 10, 'TotMntNeto' => 8403, 'TotMntIVA' => 1597, 'TotMntTotal' => 10000],
        ]);

        $this->assertSame(['33', '35', '48'], $this->typesOf($bag));

        $boletas = $this->totalOf($bag, 35);
        $this->assertStringContainsString('<TotDoc>10</TotDoc>', $boletas);
        $this->assertStringContainsString('<TotMntNeto>8403</TotMntNeto>', $boletas);
        $this->assertStringContainsString('<TotMntIVA>1597</TotMntIVA>', $boletas);
        $this->assertStringContainsString('<TotMntTotal>10000</TotMntTotal>', $boletas);
    }

    public function testWhatTheSummaryDoesNotSayHasTheDefaultOfTheTotals(): void
    {
        $bag = $this->build([['TpoDoc' => '38', 'TotDoc' => '3']]);

        $total = $this->totalOf($bag, 38);
        $this->assertStringContainsString('<TotMntExe>0</TotMntExe>', $total);
        $this->assertStringContainsString('<TotMntNeto>0</TotMntNeto>', $total);
        $this->assertStringContainsString('<TotMntIVA>0</TotMntIVA>', $total);
        $this->assertStringContainsString('<TotMntTotal>0</TotMntTotal>', $total);
    }

    public function testTheBookWithManualSummaryPassesTheSchemaAndIsSigned(): void
    {
        $bag = $this->build([['TpoDoc' => 35, 'TotDoc' => 10, 'TotMntNeto' => 8403, 'TotMntIVA' => 1597, 'TotMntTotal' => 10000]]);

        $this->validator->validateSchema($bag);
        $result = $this->validator->validateSignature($bag);
        $this->assertTrue($result->isValid(), $result->getError()?->getMessage() ?? '');
    }

    public function testTheSimplifiedBookHasTheManualSummaryToo(): void
    {
        $bag = $this->build(
            [['TpoDoc' => 35, 'TotDoc' => 10, 'TotMntNeto' => 8403, 'TotMntIVA' => 1597, 'TotMntTotal' => 10000]],
            options: ['builder' => ['simplificado' => true, 'detalle' => false]]
        );

        $this->validator->validateSchema($bag);
        $this->assertSame(['33', '35'], $this->typesOf($bag));
    }

    public function testATypeThatIsInTheDetailCanNotHaveAManualSummary(): void
    {
        // A ticket in the detail: its type already has its totals.
        $input = $this->input();
        $ticket = $input['detalle'][0];
        $ticket['TpoDoc'] = 35;
        $ticket['NroDoc'] = 9999;
        $bag = $this->loader->load(new BookBag(
            tipo: TipoLibro::VENTAS,
            caratula: $input['caratula'],
            detalle: [...$input['detalle'], $ticket],
            certificate: $this->certificate,
            emisor: $this->emisor,
            resumen: [['TpoDoc' => 35, 'TotDoc' => 1]],
        ));

        try {
            $this->builder->build($bag);
            $this->fail('The manual summary of a type that is in the detail was accepted.');
        } catch (BookException $e) {
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertSame(
                'The document type 35 is in the detail of the book, so it can not have a manual summary.',
                $e->getPrevious()->getMessage()
            );
        }
    }

    public function testATypeRepeatedInTheManualSummaryIsRefused(): void
    {
        $this->assertRefused(
            'The document type 35 is repeated in the manual summary.',
            [['TpoDoc' => 35, 'TotDoc' => 1], ['TpoDoc' => '35', 'TotDoc' => 2]]
        );
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function incompleteEntries(): array
    {
        return [
            'without type' => [['TotDoc' => 1]],
            'without total of documents' => [['TpoDoc' => 35]],
            'empty' => [[]],
            'type null' => [['TpoDoc' => null, 'TotDoc' => 1]],
            'total of documents null' => [['TpoDoc' => 35, 'TotDoc' => null]],
        ];
    }

    /**
     * @param array<string, mixed> $entry
     */
    #[DataProvider('incompleteEntries')]
    public function testAnEntryWithoutTypeOrTotalOfDocumentsIsRefused(array $entry): void
    {
        $this->assertRefused('Element 2 of the manual summary must have TpoDoc and TotDoc.', [['TpoDoc' => 35, 'TotDoc' => 1], $entry]);
    }

    /**
     * @return array<string, array{int|string}>
     */
    public static function typesThatAreNotAllowed(): array
    {
        return [
            'an electronic invoice' => [34],
            'an electronic ticket' => [39],
            'an exempt electronic ticket' => [41],
            'zero' => [0],
            'text' => ['boleta'],
        ];
    }

    #[DataProvider('typesThatAreNotAllowed')]
    public function testOnlyTheTypesOfTicketsAndElectronicPaymentsHaveAManualSummary(int|string $type): void
    {
        $this->assertRefused(
            'The document type ' . $type . ' can not have a manual summary: it must be one of 35, 38, 48.',
            [['TpoDoc' => $type, 'TotDoc' => 1]]
        );
    }

    /**
     * @return array<string, array{TipoLibro}>
     */
    public static function booksWithoutManualSummary(): array
    {
        return [
            'purchases' => [TipoLibro::COMPRAS],
            'tickets' => [TipoLibro::BOLETAS],
            'dispatch guides' => [TipoLibro::GUIAS],
            'daily sales summary' => [TipoLibro::RVD],
        ];
    }

    #[DataProvider('booksWithoutManualSummary')]
    public function testTheOtherBooksRefuseAManualSummary(TipoLibro $tipo): void
    {
        $bag = new BookBag(
            tipo: $tipo,
            certificate: $this->certificate,
            emisor: $this->emisor,
            resumen: [['TpoDoc' => 35, 'TotDoc' => 1]],
        );

        try {
            $this->builder->build($bag);
            $this->fail('A book that does not have a manual summary accepted it.');
        } catch (BookException $e) {
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertSame(
                'The book "' . $tipo->value . '" does not have a manual summary.',
                $e->getPrevious()->getMessage()
            );
        }
    }

    public function testWhichBooksAcceptAManualSummary(): void
    {
        $this->assertTrue(TipoLibro::VENTAS->acceptsResumen());
        $this->assertFalse(TipoLibro::COMPRAS->acceptsResumen());
        $this->assertFalse(TipoLibro::BOLETAS->acceptsResumen());
        $this->assertFalse(TipoLibro::GUIAS->acceptsResumen());
        $this->assertFalse(TipoLibro::RVD->acceptsResumen());
    }

    public function testTheBagKeepsTheManualSummary(): void
    {
        $resumen = [['TpoDoc' => 35, 'TotDoc' => 1]];

        $bag = new BookBag(tipo: TipoLibro::VENTAS);
        $this->assertSame([], $bag->getResumen());
        $this->assertSame([], $bag->toArray()['resumen']);

        $this->assertSame($bag, $bag->setResumen($resumen));
        $this->assertSame($resumen, $bag->getResumen());
        $this->assertSame($resumen, $bag->toArray()['resumen']);
        $this->assertSame($resumen, (new BookBag(tipo: TipoLibro::VENTAS, resumen: $resumen))->getResumen());
    }
}
