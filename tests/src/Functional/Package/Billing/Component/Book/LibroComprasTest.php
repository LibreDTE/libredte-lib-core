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
class LibroComprasTest extends TestCase
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
     * Verifica que el libro de compras se construya correctamente a partir de
     * un arreglo de detalles con documentos de proveedores.
     */
    public function testBuildLibroComprasConDocumentos(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::COMPRAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoOperacion'     => 'COMPRA',
                'TipoLibro'         => 'MENSUAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: [
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 1001,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-05',
                    'RUTDoc'   => '87654321-0',
                    'RznSoc'   => 'Proveedor Nacional SA',
                    'MntNeto'  => 200000,
                    'MntIVA'   => 38000,
                    'MntTotal' => 238000,
                ],
            ]
        );

        $bag = $this->loader->load($bag);
        $libro = $this->builder->build($bag);
        assert($libro instanceof LibroComprasVentasInterface);

        $this->assertSame('COMPRA', $libro->getTipoOperacion());
        $this->assertStringContainsString('<LibroComprasVentas', $libro->getXml());
        $this->assertStringContainsString('<TipoOperacion>COMPRA</TipoOperacion>', $libro->getXml());
        $this->assertStringContainsString('<TotalesPeriodo>', $libro->getXml());
    }

    /**
     * Verifica que el libro de compras soporte IVA no recuperable en el
     * cálculo de totales.
     */
    public function testBuildLibroComprasConIvaNoRecuperable(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::COMPRAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoOperacion'     => 'COMPRA',
                'TipoLibro'         => 'MENSUAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: [
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 2001,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '11111111-1',
                    'MntNeto'  => 50000,
                    'IVANoRec' => [
                        ['CodIVANoRec' => 1, 'MntIVANoRec' => 9500],
                    ],
                    'MntTotal' => 59500,
                ],
            ]
        );

        $bag = $this->loader->load($bag);
        $libro = $this->builder->build($bag);

        $this->assertInstanceOf(LibroComprasVentasInterface::class, $libro);
        $this->assertStringContainsString('<TotIVANoRec>', $libro->getXml());
    }

    /**
     * Verifica que tanto el libro de compras como el de ventas usen la misma
     * entidad LibroComprasVentas pero con TipoOperacion distinto.
     */
    public function testLibroComprasYVentasProducenMismaEntidad(): void
    {
        $caratula = [
            'RutEmisorLibro'    => '76192083-9',
            'RutEnvia'          => '76192083-9',
            'PeriodoTributario' => '2024-01',
            'FchResol'          => '2014-08-22',
            'NroResol'          => 80,
            'TipoLibro'         => 'MENSUAL',
            'TipoEnvio'         => 'TOTAL',
        ];

        $bag = new BookBag(
            tipo: TipoLibro::COMPRAS,
            caratula: array_merge($caratula, ['TipoOperacion' => 'COMPRA']),
            detalle: []
        );
        $bag = $this->loader->load($bag);
        $libroCompras = $this->builder->build($bag);

        $this->assertInstanceOf(LibroComprasVentasInterface::class, $libroCompras);
        assert($libroCompras instanceof LibroComprasVentasInterface);
        $this->assertSame('COMPRA', $libroCompras->getTipoOperacion());
    }

    /**
     * Verifica que el libro de compras generado supera la validación de esquema
     * XSD y que la firma electrónica es válida.
     */
    public function testValidarEsquemaYFirmaLibroCompras(): void
    {
        $certificate = (new CertificateFaker(new CertificateLoader()))->createFake();

        $bag = new BookBag(
            tipo: TipoLibro::COMPRAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoOperacion'     => 'COMPRA',
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
