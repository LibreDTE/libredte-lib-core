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
     * Test that verifies generation of a fake CAF with valid keys.
     */
    public function testCafFakerGeneratesValidKeys(): void
    {
        // Generate the CAF.
        $emisor = new Emisor('76192083-9');
        $caf = $this->cafFaker->create($emisor, 33, 1, 100);

        // Verify the issuer RUT can be obtained.
        $this->assertSame('76192083-9', $caf->getEmisor()['rut']);

        // Verify the folio range.
        $this->assertSame(1, $caf->getFolioDesde());
        $this->assertSame(100, $caf->getFolioHasta());

        // Verify public and private keys are not empty.
        $this->assertNotEmpty($caf->getPrivateKey());
        $this->assertNotEmpty($caf->getPublicKey());

        // Verify the document type is as expected.
        $this->assertSame(33, $caf->getTipoDocumento());
    }

    /**
     * Test that verifies fake CAF data is generated correctly.
     */
    public function testCafFakerGeneratesCorrectXml(): void
    {
        // Generate the CAF.
        $emisor = new Emisor('76192083-9');
        $caf = $this->cafFaker->create($emisor, 61, 200, 300);

        // Verify folios and document type are correct.
        $this->assertSame(200, $caf->getFolioDesde());
        $this->assertSame(300, $caf->getFolioHasta());
        $this->assertSame(61, $caf->getTipoDocumento());

        // Verify the authorization date is in the correct format.
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $caf->getFechaAutorizacion());
    }

    /**
     * Test that verifies a fake CAF is not validated by the SII signature.
     */
    public function testCafFakerFailsValidation(): void
    {
        // Generate the CAF.
        $emisor = new Emisor('76192083-9');
        $caf = $this->cafFaker->create($emisor, 33, 1);

        // Validate the fake CAF. Public and private CAF keys are validated,
        // but the CAF signature is not, because it is fake.
        $this->cafValidator->validate($caf);

        $this->addToAssertionCount(1);
    }

    /**
     * Test that verifies the fake CAF is in the LibreDTE environment.
     */
    public function testCafFakerGeneratesLibreDteAmbiente(): void
    {
        // Generate the CAF.
        $emisor = new Emisor('76192083-9');
        $caf = $this->cafFaker->create($emisor, 33, 1);

        // Verify the environment is LibreDTE.
        $this->assertNull($caf->getEnvironment());
    }

    /**
     * Test that verifies generation of valid public and private keys.
     */
    public function testCafFakerGeneratesValidKeysContent(): void
    {
        // Generate the CAF.
        $emisor = new Emisor('76192083-9');
        $caf = $this->cafFaker->create($emisor, 33, 1);

        // Verify the private key is valid.
        $this->assertStringContainsString('PRIVATE KEY', $caf->getPrivateKey());

        // Verify the public key is valid.
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
