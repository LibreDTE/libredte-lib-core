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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Book;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Selector\Selector;
use Derafu\Support\Arr;
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
class LibroComprasTest extends TestCase
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

    public static function dataProviderForTestFromArraySource(): array
    {
        return require self::getFixturesPath('books/libro_compras.php');
    }

    #[DataProvider('dataProviderForTestFromArraySource')]
    public function testFromArraySource($input, $expected): void
    {
        // Construir la bolsa de trabajo con los datos del libro.
        $bag = new BookBag(
            tipo: TipoLibro::COMPRAS,
            caratula: $input['caratula'] ?? [],
            detalle: $input['detalle'] ?? [],
            certificate: $this->certificate,
            emisor: $this->emisor,
        );

        // Cargar los datos de entrada y normalizarlos.
        $bag = $this->loader->load($bag);

        // Construir el libro de compras.
        $book = $this->builder->build($bag);
        assert($book instanceof LibroComprasVentasInterface);
        $this->assertInstanceOf(LibroComprasVentasInterface::class, $book);

        // Verifica que el libro de compras generado supera la validación de
        // esquema XSD y que la firma electrónica es válida.
        $this->validator->validateSchema($bag);
        $result = $this->validator->validateSignature($bag);
        $this->assertTrue(
            $result->isValid(),
            $result->getError()?->getMessage() ?? 'No se pudo validar la firma electrónica.'
        );

        // Realizar las verificaciones sobre el contenido del libro generado.
        $data = $book->toArray();
        foreach ($expected as $key => $expectedValue) {
            $actualValue = Selector::get($data, $key);
            if ($actualValue !== null) {
                if (!is_array($expectedValue)) {
                    $actualValue = Arr::cast([$actualValue])[0];
                } else {
                    $actualValue = Arr::cast($actualValue);
                }
            }
            $this->assertSame($expectedValue, $actualValue, "Key: $key");
        }
    }

    public static function dataProviderForTestSimplificadoFromArraySource(): array
    {
        return require self::getFixturesPath('books/libro_compras_simplificado.php');
    }

    #[DataProvider('dataProviderForTestSimplificadoFromArraySource')]
    public function testSimplificadoFromArraySource($input, $expected): void
    {
        // Construir la bolsa con la opción de libro simplificado.
        $bag = new BookBag(
            tipo: TipoLibro::COMPRAS,
            caratula: $input['caratula'] ?? [],
            detalle: $input['detalle'] ?? [],
            options: ['builder' => ['simplificado' => true]],
            certificate: $this->certificate,
            emisor: $this->emisor,
        );

        // Cargar los datos de entrada y normalizarlos.
        $bag = $this->loader->load($bag);

        // Construir el libro de compras simplificado.
        $book = $this->builder->build($bag);
        assert($book instanceof LibroComprasVentasInterface);
        $this->assertInstanceOf(LibroComprasVentasInterface::class, $book);

        // El libro simplificado declara el esquema correcto y no se firma.
        $this->assertTrue($book->isSimplificado());
        $this->assertSame('LibroCVS_v10.xsd', $book->getSchema());
        $this->assertStringNotContainsString('<Signature', $book->getXml());

        // Validar solo el esquema XSD (los libros simplificados no se firman).
        $this->validator->validateSchema($bag);

        // Realizar las verificaciones sobre el contenido del libro generado.
        $data = $book->toArray();
        foreach ($expected as $key => $expectedValue) {
            $actualValue = Selector::get($data, $key);
            if ($actualValue !== null) {
                if (!is_array($expectedValue)) {
                    $actualValue = Arr::cast([$actualValue])[0];
                } else {
                    $actualValue = Arr::cast($actualValue);
                }
            }
            $this->assertSame($expectedValue, $actualValue, "Key: $key");
        }
    }
}
