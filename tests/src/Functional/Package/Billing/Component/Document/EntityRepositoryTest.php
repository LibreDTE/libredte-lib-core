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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Document;

use Derafu\Lib\Core\Package\Prime\Component\Entity\Contract\ManagerWorkerInterface;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\GuiaDespachoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaClausulaVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaFormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaModalidadVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPais;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPuerto;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTipoBulto;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTransporte;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaUnidad;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPagoExportacion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\MedioPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TagXml;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Traslado;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\TipoDocumentoRepository;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(CategoriaDocumento::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ImpuestoAdicionalRetencion::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(ImpuestoAdicionalRetencionRepository::class)]
#[CoversClass(TipoDocumentoRepository::class)]
class EntityRepositoryTest extends TestCase
{
    private ManagerWorkerInterface $manager;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->manager = $app
            ->getPrimePackage()
            ->getEntityComponent()
            ->getManagerWorker()
        ;
    }

    public function testEntityRepositoryAduanaClausulaVenta(): void
    {
        $repository = $this->manager->getRepository(AduanaClausulaVenta::class);
        $this->assertSame('FOB', $repository->find(5)->getGlosa());
        $this->assertSame(1, $repository->findBy(['glosa' => 'CIF'])[0]->getCodigo());
    }

    public function testEntityRepositoryAduanaFormaPago(): void
    {
        $repository = $this->manager->getRepository(AduanaFormaPago::class);
        $this->assertSame('S/PAGO', $repository->find(21)->getGlosa());
        $this->assertSame(1, $repository->findBy(['glosa' => 'COB1'])[0]->getCodigo());
    }

    public function testEntityRepositoryAduanaModalidadVenta(): void
    {
        $repository = $this->manager->getRepository(
            AduanaModalidadVenta::class
        );
        $this->assertSame('Sin pago', $repository->find(9)->getGlosa());
        $this->assertSame(1, $repository->findBy(['glosa' => 'A firme'])[0]->getCodigo());
    }

    public function testEntityRepositoryAduanaPais(): void
    {
        $repository = $this->manager->getRepository(AduanaPais::class);
        $this->assertSame('ESTONIA', $repository->find(549)->getGlosa());
        $this->assertSame(563, $repository->findBy(['glosa' => 'ALEMANIA'])[0]->getCodigo());
    }

    public function testEntityRepositoryAduanaPuerto(): void
    {
        $repository = $this->manager->getRepository(AduanaPuerto::class);
        $this->assertSame('HELSINSKI', $repository->find(581)->getGlosa());
        $this->assertSame(111, $repository->findBy(['glosa' => 'MONTREAL'])[0]->getCodigo());
    }

    public function testEntityRepositoryAduanaTipoBulto(): void
    {
        $repository = $this->manager->getRepository(AduanaTipoBulto::class);
        $this->assertSame('ATAUD', $repository->find(86)->getGlosa());
        $this->assertSame(1, $repository->findBy(['glosa' => 'POLVO'])[0]->getCodigo());
    }

    public function testEntityRepositoryAduanaTransporte(): void
    {
        $repository = $this->manager->getRepository(AduanaTransporte::class);
        $this->assertSame('Aéreo', $repository->find(4)->getGlosa());
    }

    public function testEntityRepositoryAduanaUnidad(): void
    {
        $repository = $this->manager->getRepository(AduanaUnidad::class);
        $this->assertSame('MT2', $repository->find(15)->getGlosa());
        $this->assertSame(1, $repository->findBy(['glosa' => 'TMB'])[0]->getCodigo());
    }

    public function testEntityRepositoryComunas(): void
    {
        $repository = $this->manager->getRepository(Comuna::class);
        //$this->assertSame('', $repository->find()->getGlosa());

        $this->assertSame('Santiago', $repository->find('HUECHURABA')->getCiudad());
        $this->assertSame('Santiago', $repository->find('LAS CONDES')->getCiudad());
        $this->assertFalse($repository->find('NON_EXISTENT')->getCiudad());

        // Buscar dirección regional usando comuna.
        $this->assertSame('ARICA', $repository->find('Arica')->getDireccionRegional());
        $this->assertSame('IQUIQUE', $repository->find('iquique')->getDireccionRegional());
        $this->assertSame('NO_EXISTE', $repository->find('No_Existe')->getDireccionRegional());

        // Buscar dirección regional usando código sucursal.
        $this->assertSame('SUC 123', $repository->find(123)->getDireccionRegional());
        $this->assertSame('SUC 456', $repository->find(456)->getDireccionRegional());

        // Buscar dirección regional usando valor vacío.
        $this->assertSame('N.N.', $repository->find('')->getDireccionRegional());
        $this->assertSame('N.N.', $repository->find(null)->getDireccionRegional());
    }

    public function testEntityRepositoryTagXml(): void
    {
        $repository = $this->manager->getRepository(TagXml::class);
        $this->assertSame('Peso neto', $repository->find('PesoNeto')->getGlosa());
        $this->assertSame('FmaPagExp', $repository->findBy(['glosa' => 'Forma pago exp.'])[0]->getCodigo());
    }

    public function testEntityRepositoryFormaPago(): void
    {
        $repository = $this->manager->getRepository(FormaPago::class);
        $this->assertSame('Crédito', $repository->find(2)->getGlosa());
        $this->assertSame(1, $repository->findBy(['glosa' => 'Contado'])[0]->getCodigo());
    }

    public function testEntityRepositoryFormaPagoExportacion(): void
    {
        $repository = $this->manager->getRepository(
            FormaPagoExportacion::class
        );
        $this->assertSame('Cobranza más de 1 año', $repository->find(2)->getGlosa());
        $this->assertSame(21, $repository->findBy(['glosa' => 'Sin pago'])[0]->getCodigo());
    }

    public function testEntityRepositoryImpuestoAdicionalRetencion(): void
    {
        $repository = $this->manager->getRepository(
            ImpuestoAdicionalRetencion::class
        );

        $this->assertSame(
            'Cervezas y Bebidas Alcoh.',
            $repository->find(26)->getGlosa()
        );

        $this->assertSame('R', $repository->find(15)->getTipo());
        $this->assertSame('A', $repository->find(17)->getTipo());
        $this->assertFalse($repository->find(999)->getTipo());
        $this->assertSame('IVA retenido', $repository->find(15)->getGlosa());
        $this->assertSame('Licores, Piscos, Whisky', $repository->find(24)->getGlosa());
        $this->assertSame('Impto. cód. 999', $repository->find(999)->getGlosa());
        $this->assertSame(19.0, $repository->find(15)->getTasa());
        $this->assertSame(31.5, $repository->find(24)->getTasa());
        $this->assertFalse($repository->find(999)->getTasa());
    }

    public function testEntityRepositoryMedioPago(): void
    {
        $repository = $this->manager->getRepository(MedioPago::class);
        $this->assertSame(
            'Tarjeta de crédito o débito',
            $repository->find('TC')->glosa
        );
        $this->assertSame('EF', $repository->findBy(['glosa' => 'Efectivo'])[0]->getCodigo());
    }

    public function testEntityRepositoryTipoDocumento(): void
    {
        $repository = $this->manager->getRepository(
            TipoDocumentoInterface::class
        );

        $this->assertInstanceOf(TipoDocumentoRepository::class, $repository);
        assert($repository instanceof TipoDocumentoRepository);

        $this->assertSame(
            'Factura no afecta o exenta electrónica',
            $repository->find(34)->getNombre()
        );

        $this->assertSame(
            'Referencia no oficial del SII.',
            $repository->find('HES')->getCategoria()->getNombre()
        );

        $this->assertSame(
            'boleta_afecta',
            $repository->find(39)->getAlias()
        );

        $this->assertSame(
            41,
            $repository->findByAlias('boleta_exenta')->getCodigo()
        );

        $this->assertSame(
            52,
            $repository->findByInterface(GuiaDespachoInterface::class)->getCodigo()
        );

        $documentos = $repository->getDocumentos();
        $this->assertSame(67, count($documentos));

        $documentos = $repository->getDocumentosTributarios();
        $this->assertSame(33, count($documentos));

        $documentos = $repository->getDocumentosInformativos();
        $this->assertSame(21, count($documentos));

        $documentos = $repository->getDocumentosTributariosElectronicos();
        $this->assertSame(12, count($documentos));
        foreach ($documentos as $doc) {
            $this->assertNotEmpty(
                $doc->getAlias(),
                sprintf(
                    'Documento %s debe tener alias.',
                    $doc->getCodigo()
                )
            );
            $this->assertTrue(
                $doc->getInterface() !== null && !class_exists($doc->getInterface()),
                sprintf(
                    'Documento %s debe tener una interfaz PHP definida.',
                    $doc->getCodigo()
                )
            );
        }

        $documentos = $repository->getDocumentosTributariosElectronicosCedibles();
        $this->assertSame(5, count($documentos));

        $documentos = $repository->getDocumentosDisponibles();
        $this->assertSame(11, count($documentos));
    }

    public function testEntityRepositoryTraslado(): void
    {
        $repository = $this->manager->getRepository(Traslado::class);
        $this->assertSame('Traslados internos', $repository->find(5)->getGlosa());
        $this->assertSame(9, $repository->findBy(['glosa' => 'Venta para exportación'])[0]->getCodigo());
    }
}
