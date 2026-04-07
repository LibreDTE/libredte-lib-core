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
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ResumenVentasDiariasInterface;
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
class ResumenVentasDiariasTest extends TestCase
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
     * Verifica que el RVD se construya correctamente a partir de boletas de
     * un período de un día.
     */
    public function testBuildRvdConBoletasDeUnDia(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::RVD,
            caratula: [
                'RutEmisor'  => '76192083-9',
                'RutEnvia'   => '76192083-9',
                'FchResol'   => '2014-08-22',
                'NroResol'   => 80,
                'Correlativo' => 1,
                'SecEnvio'   => 1,
            ],
            detalle: [
                [
                    'TpoDoc'  => 39,
                    'NroDoc'  => 1,
                    'TasaImp' => 19,
                    'FchDoc'  => '2024-01-10',
                    'MntNeto' => 10000,
                    'MntIVA'  => 1900,
                    'MntTotal' => 11900,
                ],
                [
                    'TpoDoc'  => 39,
                    'NroDoc'  => 2,
                    'TasaImp' => 19,
                    'FchDoc'  => '2024-01-10',
                    'MntNeto' => 5000,
                    'MntIVA'  => 950,
                    'MntTotal' => 5950,
                ],
                [
                    'TpoDoc'  => 41,
                    'NroDoc'  => 1,
                    'TasaImp' => 0,
                    'FchDoc'  => '2024-01-10',
                    'MntExe'  => 2000,
                    'MntTotal' => 2000,
                ],
            ]
        );

        $bag = $this->loader->load($bag);
        $rvd = $this->builder->build($bag);

        $this->assertInstanceOf(ResumenVentasDiariasInterface::class, $rvd);
        $this->assertStringContainsString('<ConsumoFolios', $rvd->getXml());
        $this->assertStringContainsString('<TipoDocumento>39</TipoDocumento>', $rvd->getXml());
        $this->assertStringContainsString('<TipoDocumento>41</TipoDocumento>', $rvd->getXml());
        $this->assertStringContainsString('<FoliosEmitidos>', $rvd->getXml());
        $this->assertStringContainsString('<RangoUtilizados>', $rvd->getXml());
    }

    /**
     * Verifica que las fechas de inicio y fin se calculen correctamente a
     * partir de los documentos del detalle.
     */
    public function testBuildRvdCalculaFechasInicioYFin(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::RVD,
            caratula: [
                'RutEmisor'   => '76192083-9',
                'RutEnvia'    => '76192083-9',
                'FchResol'    => '2014-08-22',
                'NroResol'    => 80,
                'Correlativo' => 1,
                'SecEnvio'    => 1,
            ],
            detalle: [
                [
                    'TpoDoc'   => 39,
                    'NroDoc'   => 5,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-15',
                    'MntTotal' => 11900,
                ],
                [
                    'TpoDoc'   => 39,
                    'NroDoc'   => 6,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-20',
                    'MntTotal' => 5950,
                ],
            ]
        );

        $bag = $this->loader->load($bag);
        $rvd = $this->builder->build($bag);

        $this->assertStringContainsString('<FchInicio>2024-01-15</FchInicio>', $rvd->getXml());
        $this->assertStringContainsString('<FchFinal>2024-01-20</FchFinal>', $rvd->getXml());
    }

    /**
     * Verifica que los folios se agrupen en rangos continuos correctamente
     * en el resumen del RVD.
     */
    public function testBuildRvdAgrupaFoliosEnRangosContinuos(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::RVD,
            caratula: [
                'RutEmisor'   => '76192083-9',
                'RutEnvia'    => '76192083-9',
                'FchResol'    => '2014-08-22',
                'NroResol'    => 80,
                'Correlativo' => 1,
                'SecEnvio'    => 1,
            ],
            detalle: [
                ['TpoDoc' => 39, 'NroDoc' => 1, 'FchDoc' => '2024-01-10', 'MntTotal' => 1000],
                ['TpoDoc' => 39, 'NroDoc' => 2, 'FchDoc' => '2024-01-10', 'MntTotal' => 1000],
                ['TpoDoc' => 39, 'NroDoc' => 3, 'FchDoc' => '2024-01-10', 'MntTotal' => 1000],
                // Folio 5 rompe el rango continuo (falta el 4).
                ['TpoDoc' => 39, 'NroDoc' => 5, 'FchDoc' => '2024-01-10', 'MntTotal' => 1000],
            ]
        );

        $bag = $this->loader->load($bag);
        $rvd = $this->builder->build($bag);

        // Deben existir 2 rangos: [1-3] y [5-5].
        $this->assertSame(2, substr_count($rvd->getXml(), '<Inicial>'));
    }

    /**
     * Verifica que el RVD generado supera la validación de esquema XSD y que
     * la firma electrónica es válida.
     */
    public function testValidarEsquemaYFirmaRvd(): void
    {
        $certificate = (new CertificateFaker(new CertificateLoader()))->createFake();

        $bag = new BookBag(
            tipo: TipoLibro::RVD,
            caratula: [
                'RutEmisor'   => '76192083-9',
                'RutEnvia'    => '76192083-9',
                'FchResol'    => '2014-08-22',
                'NroResol'    => 80,
                'Correlativo' => 1,
                'SecEnvio'    => 1,
            ],
            detalle: [
                [
                    'TpoDoc'   => 39,
                    'NroDoc'   => 1,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'MntTotal' => 11900,
                ],
            ],
            certificate: $certificate,
        );

        $bag = $this->loader->load($bag);
        $rvd = $this->builder->build($bag);

        $this->validator->validateSchema($bag);
        $result = $this->validator->validateSignature($bag);
        $this->assertTrue($result->isValid());
    }
}
