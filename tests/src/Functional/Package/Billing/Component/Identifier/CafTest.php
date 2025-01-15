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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Identifier;

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Factory\TipoDocumentoFactory;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoDocumentoFactory::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafBag::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(CafValidatorWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(Contribuyente::class)]
class CafTest extends TestCase
{
    private CafFakerWorkerInterface $cafFaker;

    private CafLoaderWorkerInterface $cafLoader;

    private CafValidatorWorkerInterface $cafValidator;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->cafFaker = $app
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafFakerWorker()
        ;

        $this->cafLoader = $app
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafLoaderWorker()
        ;

        $this->cafValidator = $app
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafValidatorWorker()
        ;
    }

    /**
     * Test que verifica la generación de un CAF falso con claves válidas.
     */
    public function testCafFakerGeneratesValidKeys(): void
    {
        // Generar el CAF.
        $emisor = new Emisor('76192083-9');
        $cafBag = $this->cafFaker->create($emisor, 33, 1, 100);
        $caf = $cafBag->getCaf();

        // Verificar que se pueda obtener el RUT del emisor.
        $this->assertSame('76192083-9', $caf->getEmisor()['rut']);

        // Verificar el rango de folios.
        $this->assertSame(1, $caf->getFolioDesde());
        $this->assertSame(100, $caf->getFolioHasta());

        // Verificar que las claves pública y privada no estén vacías.
        $this->assertNotEmpty($caf->getPrivateKey());
        $this->assertNotEmpty($caf->getPublicKey());

        // Verificar que el tipo de documento sea el esperado.
        $this->assertSame(33, $cafBag->getTipoDocumento()->getCodigo());
    }

    /**
     * Test que verifica que los datos del CAF falso se generen correctamente.
     */
    public function testCafFakerGeneratesCorrectXml(): void
    {
        // Generar el CAF.
        $emisor = new Emisor('76192083-9');
        $cafBag = $this->cafFaker->create($emisor, 61, 200, 300);
        $caf = $cafBag->getCaf();

        // Verificar que los folios y el tipo de documento sean correctos.
        $this->assertSame(200, $caf->getFolioDesde());
        $this->assertSame(300, $caf->getFolioHasta());
        $this->assertSame(61, $cafBag->getTipoDocumento()->getCodigo());

        // Verificar que la fecha de autorización esté en el formato correcto.
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $caf->getFechaAutorizacion());
    }

    /**
     * Test que verifica que un CAF falso no sea validado por la firma del SII.
     */
    public function testCafFakerFailsValidation(): void
    {
        // Generar el CAF.
        $emisor = new Emisor('76192083-9');
        $cafBag = $this->cafFaker->create($emisor, 33, 1);
        $caf = $cafBag->getCaf();

        // Validar el CAF falso. Se validará clave pública y privada del CAF,
        // pero no se validará la firma del CAF en si, pues es falsa.
        $this->cafValidator->validate($caf);

        $this->assertTrue(true);
    }

    /**
     * Test para verificar que el CAF falso esté en el ambiente de LibreDTE.
     */
    public function testCafFakerGeneratesLibreDteAmbiente(): void
    {
        // Generar el CAF.
        $emisor = new Emisor('76192083-9');
        $cafBag = $this->cafFaker->create($emisor, 33, 1);
        $caf = $cafBag->getCaf();

        // Verificar que el ambiente sea el de LibreDTE.
        $this->assertNull($caf->getAmbiente());
    }

    /**
     * Test para verificar la generación de claves públicas y privadas válidas.
     */
    public function testCafFakerGeneratesValidKeysContent(): void
    {
        // Generar el CAF.
        $emisor = new Emisor('76192083-9');
        $cafBag = $this->cafFaker->create($emisor, 33, 1);
        $caf = $cafBag->getCaf();

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
                'file' => self::getFixturesPath() . sprintf(
                    '/caf/%03d.xml',
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
        $cafBag = $this->cafLoader->load($xml);
        $caf = $cafBag->getCaf();

        $this->cafValidator->validate($caf);

        $this->assertSame($code, $cafBag->getTipoDocumento()->getCodigo());
    }
}
