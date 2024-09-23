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

namespace libredte\lib\Tests\Functional\Sii\Dte\AutorizacionFolio;

use libredte\lib\Core\Helper\Rut;
use libredte\lib\Core\Repository\DocumentoTipoRepository;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Sii\Contribuyente\Contribuyente;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\Caf;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\CafFaker;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlUtils;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Caf::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(Rut::class)]
#[CoversClass(DocumentoTipoRepository::class)]
#[CoversClass(ArrayDataProvider::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(DocumentoTipo::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
class CafTest extends TestCase
{
    /**
     * Test que verifica la generación de un CAF falso con claves válidas.
     */
    public function testCafFakerGeneratesValidKeys(): void
    {
        // Crear una instancia de CafFaker.
        $cafFaker = new CafFaker();

        // Generar el CAF.
        $caf = $cafFaker->create();

        // Verificar que se pueda obtener el RUT del emisor.
        $this->assertEquals('76192083-9', $caf->getEmisor()->getRut());

        // Verificar el rango de folios.
        $this->assertEquals(1, $caf->getFolioDesde());
        $this->assertEquals(100, $caf->getFolioHasta());

        // Verificar que las claves pública y privada no estén vacías.
        $this->assertNotEmpty($caf->getPrivateKey());
        $this->assertNotEmpty($caf->getPublicKey());

        // Verificar que el tipo de documento sea el esperado.
        $this->assertEquals(33, $caf->getTipoDocumento()->getCodigo());
    }

    /**
     * Test que verifica que los datos del CAF falso se generen correctamente.
     */
    public function testCafFakerGeneratesCorrectXml(): void
    {
        // Crear una instancia de CafFaker.
        $cafFaker = new CafFaker();

        // Personalizar el rango de folios y el tipo de documento.
        $cafFaker->setRangoFolios(200, 300);
        $cafFaker->setTipoDocumento(61);

        // Generar el CAF.
        $caf = $cafFaker->create();

        // Verificar que los folios y el tipo de documento sean correctos.
        $this->assertEquals(200, $caf->getFolioDesde());
        $this->assertEquals(300, $caf->getFolioHasta());
        $this->assertEquals(61, $caf->getTipoDocumento()->getCodigo());

        // Verificar que la fecha de autorización esté en el formato correcto.
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $caf->getFechaAutorizacion());
    }

    /**
     * Test que verifica que un CAF falso no sea validado por la firma del SII.
     */
    public function testCafFakerFailsValidation(): void
    {
        // Crear una instancia de CafFaker.
        $cafFaker = new CafFaker();

        // Generar el CAF.
        $caf = $cafFaker->create();

        // Validar el CAF falso. Se validará clave pública y privada del CAF,
        // pero no se validará la firma del CAF en si, pues es falsa.
        $caf->validate();

        $this->assertTrue(true);
    }

    /**
     * Test para verificar que el CAF falso esté en el ambiente de LibreDTE.
     */
    public function testCafFakerGeneratesLibreDteAmbiente(): void
    {
        // Crear una instancia de CafFaker.
        $cafFaker = new CafFaker();

        // Generar el CAF.
        $caf = $cafFaker->create();

        // Verificar que el ambiente sea el de LibreDTE.
        $this->assertEquals(null, $caf->getAmbiente());
    }

    /**
     * Test para verificar la generación de claves públicas y privadas válidas.
     */
    public function testCafFakerGeneratesValidKeysContent(): void
    {
        // Crear una instancia de CafFaker.
        $cafFaker = new CafFaker();

        // Generar el CAF.
        $caf = $cafFaker->create();

        // Verificar que la clave privada es válida.
        $this->assertStringContainsString('PRIVATE KEY', $caf->getPrivateKey());

        // Verificar que la clave pública es válida.
        $this->assertStringContainsString('PUBLIC KEY', $caf->getPublicKey());
    }

    public static function provideCafCertificacion(): array
    {
        $codes = [33, 34, 39, 41, 43, 46, 52, 56, 61, 110, 111, 112];
        $files = [];

        foreach ($codes as $code) {
            $files['caf_' . $code] = [
                'code' => $code,
                'file' => PathManager::getTestsPath() . sprintf(
                    '/resources/caf/%d.xml',
                    $code
                ),
            ];
        }

        return $files;
    }

    #[DataProvider('provideCafCertificacion')]
    public function testCafCertificacion(int $code, string $file): void
    {
        if (!file_exists($file)) {
            $this->markTestSkipped();
        }

        $xml = file_get_contents($file);

        $caf = new Caf();
        $caf->loadXML($xml);
        $caf->validate();

        $this->assertEquals($code, $caf->getTipoDocumento()->getCodigo());
    }
}
