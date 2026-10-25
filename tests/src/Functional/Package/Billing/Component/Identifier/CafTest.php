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
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoDocumentoFactory::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(CafValidatorWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
class CafTest extends TestCase
{
    private CafFakerWorkerInterface $cafFaker;

    private CafLoaderWorkerInterface $cafLoader;

    private CafValidatorWorkerInterface $cafValidator;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->cafFaker = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafFakerWorker()
        ;

        $this->cafLoader = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafLoaderWorker()
        ;

        $this->cafValidator = $app
            ->getPackageRegistry()
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
        $caf = $this->cafFaker->create($emisor, 33, 1, 100);

        // Verificar que se pueda obtener el RUT del emisor.
        $this->assertSame('76192083-9', $caf->getEmisor()['rut']);

        // Verificar el rango de folios.
        $this->assertSame(1, $caf->getFolioDesde());
        $this->assertSame(100, $caf->getFolioHasta());

        // Verificar que las claves pública y privada no estén vacías.
        $this->assertNotEmpty($caf->getPrivateKey());
        $this->assertNotEmpty($caf->getPublicKey());

        // Verificar que el tipo de documento sea el esperado.
        $this->assertSame(33, $caf->getTipoDocumento());
    }

    /**
     * Test que verifica que los datos del CAF falso se generen correctamente.
     */
    public function testCafFakerGeneratesCorrectXml(): void
    {
        // Generar el CAF.
        $emisor = new Emisor('76192083-9');
        $caf = $this->cafFaker->create($emisor, 61, 200, 300);

        // Verificar que los folios y el tipo de documento sean correctos.
        $this->assertSame(200, $caf->getFolioDesde());
        $this->assertSame(300, $caf->getFolioHasta());
        $this->assertSame(61, $caf->getTipoDocumento());

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
        $caf = $this->cafFaker->create($emisor, 33, 1);

        // Validar el CAF falso. Se validará clave pública y privada del CAF,
        // pero no se validará la firma del CAF en si, pues es falsa.
        $this->cafValidator->validate($caf);

        $this->addToAssertionCount(1);
    }

    /**
     * Test para verificar que el CAF falso esté en el ambiente de LibreDTE.
     */
    public function testCafFakerGeneratesLibreDteAmbiente(): void
    {
        // Generar el CAF.
        $emisor = new Emisor('76192083-9');
        $caf = $this->cafFaker->create($emisor, 33, 1);

        // Verificar que el ambiente sea el de LibreDTE.
        $this->assertNull($caf->getEnvironment());
    }

    /**
     * Test para verificar la generación de claves públicas y privadas válidas.
     */
    public function testCafFakerGeneratesValidKeysContent(): void
    {
        // Generar el CAF.
        $emisor = new Emisor('76192083-9');
        $caf = $this->cafFaker->create($emisor, 33, 1);

        // Verificar que la clave privada es válida.
        $this->assertStringContainsString('PRIVATE KEY', $caf->getPrivateKey());

        // Verificar que la clave pública es válida.
        $this->assertStringContainsString('PUBLIC KEY', $caf->getPublicKey());
    }

    /**
     * @return array<string, array{code: int, file: string}>
     */
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
        $caf = $this->cafLoader->load($xml);

        $this->cafValidator->validate($caf);

        $this->assertSame($code, $caf->getTipoDocumento());
    }
}
