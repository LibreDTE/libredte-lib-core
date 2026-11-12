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

use DateTimeImmutable;
use Derafu\Array\ArrayReader;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Factory\TipoDocumentoFactory;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafValidatorException;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateArrayJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoDocumentoFactory::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(Caf::class)]
#[CoversClass(SiiEnvironment::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(CafValidatorWorker::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(CreateArrayJob::class)]
#[CoversClass(CreateXmlJob::class)]
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

        $xml = ValueCaster::requireString(file_get_contents($file));
        $caf = $this->cafLoader->load($xml);

        $this->cafValidator->validate($caf);

        $this->assertSame($code, $caf->getTipoDocumento());
    }

    /**
     * Suma (o resta) a una fecha `Y-m-d` el intervalo indicado.
     */
    private static function addDays(string $date, string $modifier): string
    {
        return (new DateTimeImmutable($date))->modify($modifier)->format('Y-m-d');
    }

    /**
     * Crea un CAF falso y lo recarga con el XML modificado por `$mutate`.
     *
     * @param callable(string): string $mutate
     */
    private function createMutatedCaf(int $dte, callable $mutate): Caf
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), $dte, 1, 10);

        $caf = $this->cafLoader->load($mutate($caf->getXml()));
        assert($caf instanceof Caf);

        return $caf;
    }

    public function testGetXmlIsAFragmentThatCanBeLoadedAgain(): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), 33, 5, 9);

        $xml = $caf->getXml();
        $reloaded = $this->cafLoader->load($xml);

        $this->assertStringStartsWith('<AUTORIZACION>', $xml);
        $this->assertSame($caf->getId(), $reloaded->getId());
        $this->assertSame($caf->getPrivateKey(), $reloaded->getPrivateKey());
        $this->assertStringStartsWith('<?xml', $caf->saveXml());
    }

    public function testGetAutorizacionReturnsTheCafNode(): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), 33, 5, 9);

        $autorizacion = $caf->getAutorizacion();

        $reader = ArrayReader::fromArray($autorizacion);
        $this->assertSame('76192083-9', $reader->requireAsString('DA.RE'));
        $this->assertSame('33', $reader->requireAsString('DA.TD'));
        $this->assertSame($caf->getFirma(), $reader->requireAsString('FRMA'));
    }

    public function testRangeHelpers(): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), 33, 5, 9);

        $this->assertSame('CAF33D5H9', $caf->getId());
        $this->assertSame(5, $caf->getCantidadFolios());
        $this->assertFalse($caf->isEnRango(4));
        $this->assertTrue($caf->isEnRango(5));
        $this->assertTrue($caf->isEnRango(9));
        $this->assertFalse($caf->isEnRango(10));

        $single = $this->cafFaker->create(new Emisor('76192083-9'), 33, 7);
        $this->assertSame(1, $single->getCantidadFolios());
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function provideHasVencimiento(): array
    {
        return [
            'factura' => [33, true],
            'factura compra' => [46, true],
            'factura exportacion' => [110, false],
            'nota credito' => [61, true],
            'nota debito' => [56, true],
            'guia despacho' => [52, false],
            'boleta' => [39, false],
            'boleta exenta' => [41, false],
            'factura exenta' => [34, false],
        ];
    }

    #[DataProvider('provideHasVencimiento')]
    public function testFechaVencimientoDependsOnDocumentType(int $dte, bool $vence): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), $dte, 1, 10);

        $this->assertSame($vence, $caf->hasVencimiento());
        if ($vence) {
            $esperada = self::addDays($caf->getFechaAutorizacion(), '+ 180 days');
            $this->assertSame($esperada, $caf->getFechaVencimiento());
            $this->assertSame($esperada, $caf->requireFechaVencimiento());
        } else {
            $this->assertNull($caf->getFechaVencimiento());
            $this->expectException(CafException::class);
            $caf->requireFechaVencimiento();
        }
    }

    public function testIsVigenteUsesTheExpirationDateOnlyWhenTheCafExpires(): void
    {
        $factura = $this->cafFaker->create(new Emisor('76192083-9'), 33, 1, 10);
        $boleta = $this->cafFaker->create(new Emisor('76192083-9'), 39, 1, 10);
        $vencimiento = $factura->requireFechaVencimiento();
        $antes = self::addDays($vencimiento, '- 1 day');
        $despues = self::addDays($vencimiento, '+ 1 day');

        $this->assertTrue($factura->isVigente());
        $this->assertTrue($factura->isVigente($antes));
        $this->assertTrue($factura->isVigente($antes . 'T23:59:59'));
        $this->assertFalse($factura->isVigente($despues));

        // Una boleta no vence nunca.
        $this->assertTrue($boleta->isVigente('2099-12-31'));
    }

    public function testCafIsValidFor180DaysCountingTheAuthorizationDay(): void
    {
        $fecha = self::addDays(date('Y-m-d'), '- 10 days');
        $caf = $this->createMutatedCaf(
            33,
            fn (string $xml) => (string) preg_replace('#<FA>[^<]*</FA>#', '<FA>' . $fecha . '</FA>', $xml)
        );
        $ultimoDia = self::addDays($fecha, '+ 179 days');
        $vencimiento = self::addDays($fecha, '+ 180 days');

        // El día de la autorización es el primer día de vigencia.
        $this->assertTrue($caf->isVigente($fecha));
        $this->assertTrue($caf->isVigente($fecha . 'T00:00:00'));

        // El día 180 de vigencia es el último, hasta el último segundo.
        $this->assertTrue($caf->isVigente($ultimoDia));
        $this->assertTrue($caf->isVigente($ultimoDia . 'T23:59:59'));

        // La fecha de vencimiento es el primer día en que ya no está vigente.
        $this->assertSame($vencimiento, $caf->getFechaVencimiento());
        $this->assertFalse($caf->isVigente($vencimiento));
        $this->assertFalse($caf->isVigente($vencimiento . 'T00:00:00'));
        $this->assertFalse($caf->isVigente($vencimiento . 'T12:00:00'));
    }

    public function testMesesAutorizacionOfACafAuthorizedToday(): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), 33, 1, 10);

        $this->assertSame(0.0, (float) $caf->getMesesAutorizacion());
    }

    public function testMesesAutorizacionOfAnOldCaf(): void
    {
        $fecha = self::addDays(date('Y-m-d'), '- 3 months - 15 days');
        $caf = $this->createMutatedCaf(
            33,
            fn (string $xml) => (string) preg_replace('#<FA>[^<]*</FA>#', '<FA>' . $fecha . '</FA>', $xml)
        );

        $meses = $caf->getMesesAutorizacion();

        $this->assertGreaterThanOrEqual(3.4, $meses);
        $this->assertLessThanOrEqual(3.6, $meses);
        $this->assertFalse($caf->isVigente(self::addDays($fecha, '+ 181 days')));
        $this->assertTrue($caf->isVigente(self::addDays($fecha, '+ 179 days')));
    }

    public function testMesesAutorizacionOfACafOlderThanAYear(): void
    {
        $fecha = self::addDays(date('Y-m-d'), '- 1 year - 3 months - 15 days');
        $caf = $this->createMutatedCaf(
            33,
            fn (string $xml) => (string) preg_replace('#<FA>[^<]*</FA>#', '<FA>' . $fecha . '</FA>', $xml)
        );

        $meses = $caf->getMesesAutorizacion();

        $this->assertGreaterThanOrEqual(15.4, $meses);
        $this->assertLessThanOrEqual(15.6, $meses);
    }

    /**
     * @return array<string, array{int, SiiEnvironment|null, int|null}>
     */
    public static function provideCafEnvironment(): array
    {
        return [
            'fake caf' => [666, null, null],
            'staging' => [100, SiiEnvironment::STAGING, 1],
            'production' => [300, SiiEnvironment::PRODUCTION, 0],
            'unknown idk' => [999, null, null],
        ];
    }

    #[DataProvider('provideCafEnvironment')]
    public function testEnvironmentComesFromTheIdkOfTheCaf(int $idk, ?SiiEnvironment $environment, ?int $certificacion): void
    {
        $caf = $this->createMutatedCaf(
            33,
            fn (string $xml) => str_replace('<IDK>666</IDK>', '<IDK>' . $idk . '</IDK>', $xml)
        );

        $this->assertSame($idk, $caf->getIdk());
        $this->assertSame($environment, $caf->getEnvironment());
        $this->assertSame($certificacion, $caf->getCertificacion());
    }

    public function testToArrayAndJsonSerialize(): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), 33, 5, 9);

        $array = $caf->toArray();
        $this->assertSame('CAF33D5H9', $array['id']);
        $this->assertSame(5, $array['cantidad_folios']);
        $this->assertSame(666, $array['idk']);
        $this->assertNull($array['ambiente']);
        $this->assertNull($array['certificacion']);
        $this->assertTrue($array['vence']);
        $this->assertTrue($array['vigente']);

        $json = ArrayReader::fromValue($caf->jsonSerialize());
        $this->assertSame($array['xml'], base64_decode($json->requireString('xml'), true));
        $this->assertSame($array['id'], $json->requireString('id'));

        // El CAF completo, con las llaves, se puede serializar a JSON.
        $this->assertJson((string) json_encode($caf));
    }

    public function testPemKeysWithoutLineBreaksAreRestored(): void
    {
        $original = $this->cafFaker->create(new Emisor('76192083-9'), 33, 1, 10);

        // Así vienen las llaves en algunos CAF: todo en una sola línea.
        $xml = (string) preg_replace_callback(
            '#<(RSASK|RSAPUBK)>(.*?)</\1>#s',
            fn (array $m) => '<' . $m[1] . '>' . str_replace("\n", '', $m[2]) . '</' . $m[1] . '>',
            $original->getXml()
        );
        $flat = $this->cafLoader->load($xml);

        $this->assertSame(trim($original->getPublicKey()), $flat->getPublicKey());
        $this->assertSame(trim($original->getPrivateKey()), $flat->getPrivateKey());

        // Las llaves restauradas son utilizables por OpenSSL.
        $this->cafValidator->validate($flat);
        $this->addToAssertionCount(1);
    }

    public function testValidatorRejectsCorruptPrivateKey(): void
    {
        $caf = $this->createMutatedCaf(
            33,
            fn (string $xml) => (string) preg_replace('#<RSASK>.*?</RSASK>#s', '<RSASK>no es una llave</RSASK>', $xml)
        );

        $this->expectException(CafValidatorException::class);
        $this->cafValidator->validate($caf);
    }

    public function testValidatorRejectsCorruptPublicKey(): void
    {
        $caf = $this->createMutatedCaf(
            33,
            fn (string $xml) => (string) preg_replace('#<RSAPUBK>.*?</RSAPUBK>#s', '<RSAPUBK>no es una llave</RSAPUBK>', $xml)
        );

        $this->expectException(CafValidatorException::class);
        $this->cafValidator->validate($caf);
    }

    public function testValidatorRejectsPublicKeyThatDoesNotMatchThePrivateKey(): void
    {
        $other = $this->cafFaker->create(new Emisor('76192083-9'), 33, 1, 10);
        $otherPublicKey = $other->getPublicKey();

        $caf = $this->createMutatedCaf(
            33,
            fn (string $xml) => (string) preg_replace(
                '#<RSAPUBK>.*?</RSAPUBK>#s',
                '<RSAPUBK>' . $otherPublicKey . '</RSAPUBK>',
                $xml
            )
        );

        $this->expectException(CafValidatorException::class);
        $this->cafValidator->validate($caf);
    }

    public function testValidatorRejectsCafSignedForAnUnknownSiiCertificate(): void
    {
        $caf = $this->createMutatedCaf(
            33,
            fn (string $xml) => str_replace('<IDK>666</IDK>', '<IDK>999999</IDK>', $xml)
        );

        $this->expectException(CafValidatorException::class);
        $this->cafValidator->validate($caf);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function provideSiiCertificateIdk(): array
    {
        return [
            'produccion' => [300],
            'certificacion' => [100],
        ];
    }

    #[DataProvider('provideSiiCertificateIdk')]
    public function testValidatorRejectsCafWithSignatureNotMadeByTheSii(int $idk): void
    {
        $caf = $this->createMutatedCaf(
            33,
            fn (string $xml) => str_replace('<IDK>666</IDK>', '<IDK>' . $idk . '</IDK>', $xml)
        );

        $this->expectException(CafValidatorException::class);
        $this->cafValidator->validate($caf);
    }
}
