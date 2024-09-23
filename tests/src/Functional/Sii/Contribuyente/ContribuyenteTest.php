<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Tests\Functional\Core\Sii\Contribuyente;

use libredte\lib\Core\Helper\Rut;
use libredte\lib\Core\Repository\DocumentoTipoRepository;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Sii\Contribuyente\Contribuyente;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\Caf;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\CafFaker;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test funcional para la clase Contribuyente.
 */
#[CoversClass(Contribuyente::class)]
#[CoversClass(Rut::class)]
#[CoversClass(DocumentoTipoRepository::class)]
#[CoversClass(ArrayDataProvider::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
#[CoversClass(Caf::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(DocumentoTipo::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
class ContribuyenteTest extends TestCase
{
    private Contribuyente $contribuyente;

    protected function setUp(): void
    {
        $this->contribuyente = new Contribuyente(
            rut: '12345678-5',
            razon_social: 'Test Razon Social',
            giro: 'Comercio',
            actividad_economica: 123,
            telefono: '+56 9 88775544',
            email: 'test@example.com',
            direccion: '123 Calle Falsa',
            comuna: 'Santiago'
        );
    }

    /**
     * Test para verificar la creación y obtención de los datos del contribuyente.
     */
    public function testContribuyenteData(): void
    {
        $this->assertEquals('12345678-5', $this->contribuyente->getRut());
        $this->assertEquals('Test Razon Social', $this->contribuyente->getRazonSocial());
        $this->assertEquals('Comercio', $this->contribuyente->getGiro());
        $this->assertEquals(123, $this->contribuyente->getActividadEconomica());
        $this->assertEquals('+56 9 88775544', $this->contribuyente->getTelefono());
        $this->assertEquals('test@example.com', $this->contribuyente->getEmail());
        $this->assertEquals('123 Calle Falsa', $this->contribuyente->getDireccion());
        $this->assertEquals('Santiago', $this->contribuyente->getComuna());
    }

    /**
     * Test para verificar la generación de un certificado ficticio.
     */
    public function testFakeCertificateGeneration(): void
    {
        $fakeCert = $this->contribuyente->getFakeCertificate();
        $this->assertInstanceOf(Certificate::class, $fakeCert);

        $this->assertEquals('12345678-5', $fakeCert->getID());
    }

    /**
     * Test para verificar la generación de un CAF ficticio.
     */
    public function testFakeCafGeneration(): void
    {
        $caf = $this->contribuyente->getFakeCaf(33, 1000, 1100);
        $this->assertInstanceOf(Caf::class, $caf);

        $this->assertEquals('CAF33D1000H1100', $caf->getID());
        $this->assertEquals(33, $caf->getTipoDocumento()->getCodigo());
        $this->assertEquals(1000, $caf->getFolioDesde());
        $this->assertEquals(1100, $caf->getFolioHasta());
    }
}
