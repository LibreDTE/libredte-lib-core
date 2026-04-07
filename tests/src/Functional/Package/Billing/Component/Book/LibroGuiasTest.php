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
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LibroGuiasInterface;
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
class LibroGuiasTest extends TestCase
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
     * Verifica que el libro de guías de despacho se construya correctamente
     * a partir de guías de venta y de traslado.
     */
    public function testBuildLibroGuiasConGuiasVentaYTraslado(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::GUIAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoLibro'         => 'ESPECIAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: [
                [
                    'Folio'    => 1,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'RznSoc'   => 'Cliente Venta SA',
                    'TpoOper'  => 1,
                    'MntNeto'  => 100000,
                    'TasaImp'  => 19,
                    'IVA'      => 19000,
                    'MntTotal' => 119000,
                ],
                [
                    'Folio'    => 2,
                    'FchDoc'   => '2024-01-15',
                    'RUTDoc'   => '12345678-9',
                    'RznSoc'   => 'Bodega Propia',
                    'TpoOper'  => 5,
                    'MntNeto'  => 50000,
                    'TasaImp'  => 0,
                    'IVA'      => 0,
                    'MntTotal' => 50000,
                ],
            ]
        );

        $bag = $this->loader->load($bag);
        $libro = $this->builder->build($bag);

        $this->assertInstanceOf(LibroGuiasInterface::class, $libro);
        $this->assertStringContainsString('<LibroGuia', $libro->getXml());
        $this->assertStringContainsString('<TotGuiaVenta>', $libro->getXml());
        $this->assertStringContainsString('<TotTraslado>', $libro->getXml());
        $this->assertSame(2, $libro->countDetalle());
    }

    /**
     * Verifica que las guías anuladas se registren en el resumen sin sumar al
     * monto total de ventas.
     */
    public function testBuildLibroGuiasConGuiasAnuladas(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::GUIAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoLibro'         => 'ESPECIAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: [
                [
                    'Folio'    => 1,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'TpoOper'  => 1,
                    'MntNeto'  => 100000,
                    'TasaImp'  => 19,
                    'IVA'      => 19000,
                    'MntTotal' => 119000,
                ],
                [
                    'Folio'    => 2,
                    'Anulado'  => 1,
                    'FchDoc'   => '2024-01-11',
                    'RUTDoc'   => '12345678-9',
                    'MntTotal' => 0,
                ],
            ]
        );

        $bag = $this->loader->load($bag);
        $libro = $this->builder->build($bag);

        $this->assertStringContainsString('<TotFolAnulado>', $libro->getXml());
    }

    /**
     * Verifica que el monto total de ventas se calcule correctamente en el
     * resumen del período.
     */
    public function testBuildLibroGuiasCalculaMontosVentaCorrectamente(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::GUIAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoLibro'         => 'ESPECIAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: [
                [
                    'Folio'    => 1,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'TpoOper'  => 1,
                    'MntNeto'  => 100000,
                    'TasaImp'  => 19,
                    'IVA'      => 19000,
                    'MntTotal' => 119000,
                ],
                [
                    'Folio'    => 2,
                    'FchDoc'   => '2024-01-20',
                    'RUTDoc'   => '98765432-1',
                    'TpoOper'  => 1,
                    'MntNeto'  => 80000,
                    'TasaImp'  => 19,
                    'IVA'      => 15200,
                    'MntTotal' => 95200,
                ],
            ]
        );

        $bag = $this->loader->load($bag);
        $libro = $this->builder->build($bag);

        $this->assertStringContainsString('<TotGuiaVenta>2</TotGuiaVenta>', $libro->getXml());
        $this->assertStringContainsString('<TotMntGuiaVta>214200</TotMntGuiaVta>', $libro->getXml());
    }

    /**
     * Verifica que el libro de guías generado supera la validación de esquema
     * XSD y que la firma electrónica es válida.
     */
    public function testValidarEsquemaYFirmaLibroGuias(): void
    {
        $certificate = (new CertificateFaker(new CertificateLoader()))->createFake();

        $bag = new BookBag(
            tipo: TipoLibro::GUIAS,
            caratula: [
                'RutEmisorLibro'    => '76192083-9',
                'RutEnvia'          => '76192083-9',
                'PeriodoTributario' => '2024-01',
                'FchResol'          => '2014-08-22',
                'NroResol'          => 80,
                'TipoLibro'         => 'ESPECIAL',
                'TipoEnvio'         => 'TOTAL',
            ],
            detalle: [
                [
                    'Folio'    => 1,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'TpoOper'  => 1,
                    'MntNeto'  => 100000,
                    'TasaImp'  => 19,
                    'IVA'      => 19000,
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
