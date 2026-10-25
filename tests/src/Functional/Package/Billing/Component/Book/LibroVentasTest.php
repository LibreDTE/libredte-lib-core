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

use Derafu\Array\ArrayCaster;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Selector\Selector;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\BookComponent;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LibroComprasVentasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroComprasVentas;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Support\BookBag;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\AbstractLibroComprasVentasBuilderStrategy;
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
#[CoversClass(AbstractLibroComprasVentasArrayLoaderStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(AutorizacionDte::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(LibroComprasVentas::class)]
class LibroVentasTest extends TestCase
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
     * @return array<string, array{
     *     input: array{
     *         caratula?: array<string, mixed>,
     *         detalle?: array<int, array<string, mixed>>
     *     },
     *     expected: array<string, mixed>
     * }>
     */
    public static function dataProviderForTestFromArraySource(): array
    {
        return require self::getFixturesPath('books/libro_ventas.php');
    }

    /**
     * @param array{caratula?: array<string, mixed>, detalle?: array<int, array<string, mixed>>} $input
     * @param array<string, mixed> $expected
     */
    #[DataProvider('dataProviderForTestFromArraySource')]
    public function testFromArraySource($input, $expected): void
    {
        // Build the work bag with book data.
        $bag = new BookBag(
            tipo: TipoLibro::VENTAS,
            caratula: $input['caratula'] ?? [],
            detalle: $input['detalle'] ?? [],
            certificate: $this->certificate,
            emisor: $this->emisor,
        );

        // Load input data and normalize it.
        $bag = $this->loader->load($bag);

        // Build the sales book.
        $this->builder->build($bag);
        $book = $bag->getBook();
        assert($book instanceof LibroComprasVentasInterface);
        $this->assertInstanceOf(LibroComprasVentasInterface::class, $book);

        // Verify the generated sales book passes XSD schema validation
        // and has a valid electronic signature.
        $this->validator->validateSchema($bag);
        $result = $this->validator->validateSignature($bag);
        $this->assertTrue(
            $result->isValid(),
            $result->getError()?->getMessage() ?? 'Could not validate the electronic signature.'
        );

        // Run assertions on the generated book content.
        $data = $book->toArray();
        foreach ($expected as $key => $expectedValue) {
            $actualValue = Selector::get($data, $key);
            if ($actualValue !== null) {
                if (!is_array($expectedValue)) {
                    $actualValue = ArrayCaster::cast([$actualValue])[0];
                } else {
                    $actualValue = ArrayCaster::cast($actualValue);
                }
            }
            $this->assertSame($expectedValue, $actualValue, "Key: $key");
        }
    }

    /**
     * @return array<string, array{
     *     input: array{
     *         caratula?: array<string, mixed>,
     *         detalle?: array<int, array<string, mixed>>
     *     },
     *     expected: array<string, mixed>
     * }>
     */
    public static function dataProviderForTestSimplificadoFromArraySource(): array
    {
        return require self::getFixturesPath('books/libro_ventas_simplificado.php');
    }

    /**
     * @param array{caratula?: array<string, mixed>, detalle?: array<int, array<string, mixed>>} $input
     * @param array<string, mixed> $expected
     */
    #[DataProvider('dataProviderForTestSimplificadoFromArraySource')]
    public function testSimplificadoFromArraySource($input, $expected): void
    {
        // Build the bag with the simplified book option.
        $bag = new BookBag(
            tipo: TipoLibro::VENTAS,
            caratula: $input['caratula'] ?? [],
            detalle: $input['detalle'] ?? [],
            options: ['builder' => ['simplificado' => true]],
            certificate: $this->certificate,
            emisor: $this->emisor,
        );

        // Load input data and normalize it.
        $bag = $this->loader->load($bag);

        // Build the simplified sales book.
        $this->builder->build($bag);
        $book = $bag->getBook();
        assert($book instanceof LibroComprasVentasInterface);
        $this->assertInstanceOf(LibroComprasVentasInterface::class, $book);

        // The simplified book declares the correct schema and is not signed.
        $this->assertTrue($book->isSimplificado());
        $this->assertSame('LibroCVS_v10.xsd', $book->getSchema());
        $this->assertStringNotContainsString('<Signature', $book->saveXml());

        // Validate XSD schema only (simplified books are not signed).
        $this->validator->validateSchema($bag);

        // Run assertions on the generated book content.
        $data = $book->toArray();
        foreach ($expected as $key => $expectedValue) {
            $actualValue = Selector::get($data, $key);
            if ($actualValue !== null) {
                if (!is_array($expectedValue)) {
                    $actualValue = ArrayCaster::cast([$actualValue])[0];
                } else {
                    $actualValue = ArrayCaster::cast($actualValue);
                }
            }
            $this->assertSame($expectedValue, $actualValue, "Key: $key");
        }
    }
}
