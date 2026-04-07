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

use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LibroComprasVentasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Support\BookBag;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\LoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\ValidatorWorker;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LoaderWorker::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(ValidatorWorker::class)]
class LibroVentasTest extends TestCase
{
    private LoaderWorkerInterface $loader;

    private BuilderWorkerInterface $builder;

    private ValidatorWorkerInterface $validator;

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
    }

    /**
     * Verifica que el libro de ventas se construya correctamente a partir de
     * un arreglo de detalles con documentos afectos.
     */
    public function testBuildLibroVentasConDocumentosAfectos(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::VENTAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoOperacion'     => 'VENTA',
                'TipoLibro'         => 'MENSUAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: [
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 1,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'RznSoc'   => 'Empresa Compradora SpA',
                    'MntNeto'  => 100000,
                    'MntIVA'   => 19000,
                    'MntTotal' => 119000,
                ],
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 2,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-20',
                    'RUTDoc'   => '98765432-1',
                    'RznSoc'   => 'Otro Cliente Ltda',
                    'MntNeto'  => 50000,
                    'MntIVA'   => 9500,
                    'MntTotal' => 59500,
                ],
            ]
        );

        $bag = $this->loader->load($bag);
        $libro = $this->builder->build($bag);
        assert($libro instanceof LibroComprasVentasInterface);

        $this->assertSame('VENTA', $libro->getTipoOperacion());
        $this->assertStringContainsString('<LibroComprasVentas', $libro->getXml());
        $this->assertStringContainsString('<TipoOperacion>VENTA</TipoOperacion>', $libro->getXml());
        $this->assertStringContainsString('<TotalesPeriodo>', $libro->getXml());
        $this->assertStringContainsString('<TpoDoc>33</TpoDoc>', $libro->getXml());
    }

    /**
     * Verifica que el resumen del período calcule correctamente los totales
     * agrupados por tipo de documento.
     */
    public function testBuildLibroVentasCalculaTotalesCorrectamente(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::VENTAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoOperacion'     => 'VENTA',
                'TipoLibro'         => 'MENSUAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: [
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 1,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'MntNeto'  => 100000,
                    'MntIVA'   => 19000,
                    'MntTotal' => 119000,
                ],
                [
                    'TpoDoc'   => 61,
                    'NroDoc'   => 1,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-15',
                    'RUTDoc'   => '12345678-9',
                    'MntNeto'  => -20000,
                    'MntIVA'   => -3800,
                    'MntTotal' => -23800,
                ],
            ]
        );

        $bag = $this->loader->load($bag);
        $libro = $this->builder->build($bag);

        $this->assertStringContainsString('<TpoDoc>33</TpoDoc>', $libro->getXml());
        $this->assertStringContainsString('<TpoDoc>61</TpoDoc>', $libro->getXml());
        $this->assertSame(2, $libro->countDetalle());
    }

    /**
     * Verifica que un libro de ventas sin detalles genere un XML válido con
     * ResumenPeriodo vacío.
     */
    public function testBuildLibroVentasSinDetalles(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::VENTAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoOperacion'     => 'VENTA',
                'TipoLibro'         => 'MENSUAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: []
        );

        $bag = $this->loader->load($bag);
        $libro = $this->builder->build($bag);

        $this->assertInstanceOf(LibroComprasVentasInterface::class, $libro);
        $this->assertSame(0, $libro->countDetalle());
    }

    /**
     * Verifica que el libro de ventas generado supera la validación de esquema
     * XSD y que la firma electrónica es válida.
     */
    public function testValidarEsquemaYFirmaLibroVentas(): void
    {
        $certificate = (new CertificateFaker(new CertificateLoader()))->createFake();

        $bag = new BookBag(
            tipo: TipoLibro::VENTAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoOperacion'     => 'VENTA',
                'TipoLibro'         => 'MENSUAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: [
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 1,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'MntNeto'  => 100000,
                    'MntIVA'   => 19000,
                    'MntTotal' => 119000,
                ],
            ],
            certificate: $certificate,
        );

        $bag = $this->loader->load($bag);
        $libro = $this->builder->build($bag);

        $this->validator->validateSchema($bag);
        $result = $this->validator->validateSignature($bag);
        $this->assertTrue($result->isValid());
    }
}
