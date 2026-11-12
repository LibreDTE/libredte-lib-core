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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\TradingParties;

use Derafu\Translation\Exception\Core\TranslatableLogicException;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Mandatario;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\PersonaNatural;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\MandatarioFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\TradingPartiesComponent;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Worker\MandatarioManagerWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(TradingPartiesComponent::class)]
#[CoversClass(MandatarioManagerWorker::class)]
#[CoversClass(MandatarioFactory::class)]
#[CoversClass(Mandatario::class)]
#[CoversClass(PersonaNatural::class)]
class MandatarioTest extends TestCase
{
    private MandatarioManagerWorkerInterface $manager;

    private MandatarioFactory $factory;

    protected function setUp(): void
    {
        $this->manager = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getTradingPartiesComponent()
            ->getMandatarioManagerWorker()
        ;

        $this->factory = new MandatarioFactory();
    }

    public function testComponentExposesTheMandatarioManager(): void
    {
        $component = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getTradingPartiesComponent()
        ;

        $this->assertSame($this->manager, $component->getWorkers()['mandatario_manager']);
    }

    public function testFactoryCreatesAMandatario(): void
    {
        $mandatario = $this->factory->create([
            'run' => '12345678-5',
            'nombre' => 'Juan Pérez',
            'email' => 'juan.perez@example.com',
        ]);

        $this->assertInstanceOf(Mandatario::class, $mandatario);
        $this->assertSame('12345678-5', $mandatario->getRun());
        $this->assertSame('Juan Pérez', $mandatario->getNombre());
        $this->assertSame('juan.perez@example.com', $mandatario->getEmail());
        $this->assertSame([
            'run' => '12345678-5',
            'nombre' => 'Juan Pérez',
            'email' => 'juan.perez@example.com',
        ], $mandatario->toArray());
    }

    public function testFactoryAcceptsRutAsAliasOfRun(): void
    {
        $mandatario = $this->factory->create([
            'rut' => '76192083-9',
            'nombre' => 'Juan',
            'email' => 'juan@example.com',
        ]);

        $this->assertSame('76192083-9', $mandatario->getRun());
    }

    public function testFactoryPrefersRunOverRut(): void
    {
        $mandatario = $this->factory->create([
            'run' => '12345678-5',
            'rut' => '76192083-9',
            'nombre' => 'Juan',
            'email' => 'juan@example.com',
        ]);

        $this->assertSame('12345678-5', $mandatario->getRun());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideRunFormats(): array
    {
        return [
            'with dots' => ['12.345.678-5'],
            'plain' => ['12345678-5'],
            'without hyphen' => ['123456785'],
            'with spaces' => [' 12345678-5 '],
        ];
    }

    #[DataProvider('provideRunFormats')]
    public function testFactoryNormalizesTheRun(string $run): void
    {
        $mandatario = $this->factory->create([
            'run' => $run,
            'nombre' => 'Juan',
            'email' => 'juan@example.com',
        ]);

        $this->assertSame('12345678-5', $mandatario->getRun());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function provideIncompleteData(): array
    {
        $ok = ['run' => '12345678-5', 'nombre' => 'Juan', 'email' => 'juan@example.com'];

        return [
            'empty' => [[]],
            'without run' => [array_diff_key($ok, ['run' => 1])],
            'without nombre' => [array_diff_key($ok, ['nombre' => 1])],
            'without email' => [array_diff_key($ok, ['email' => 1])],
            'empty run' => [['run' => ''] + $ok],
            'empty nombre' => [['nombre' => ''] + $ok],
            'empty email' => [['email' => ''] + $ok],
            'null run' => [['run' => null] + $ok],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('provideIncompleteData')]
    public function testFactoryRequiresRunNombreAndEmail(array $data): void
    {
        $this->expectException(TranslatableLogicException::class);
        $this->factory->create($data);
    }

    public function testCreateFakeCertificateFromMandatario(): void
    {
        $mandatario = $this->factory->create([
            'run' => '12345678-5',
            'nombre' => 'Juan Pérez',
            'email' => 'juan.perez@example.com',
        ]);

        $certificate = $this->manager->createFakeCertificate($mandatario);

        $this->assertSame('12345678-5', $certificate->getId());
        $this->assertSame('Juan Pérez', $certificate->getName());
        $this->assertSame('juan.perez@example.com', $certificate->getEmail());
        $this->assertTrue($certificate->isActive());
    }

    public function testMandatarioCanBeRecoveredFromTheFakeCertificate(): void
    {
        $original = $this->factory->create([
            'run' => '12345678-5',
            'nombre' => 'Juan Pérez',
            'email' => 'juan.perez@example.com',
        ]);

        $recovered = $this->manager->createFromCertificate(
            $this->manager->createFakeCertificate($original)
        );

        $this->assertSame($original->toArray(), $recovered->toArray());
    }

    public function testMandatarioWithRunEndingInKSurvivesTheRoundTrip(): void
    {
        $original = $this->factory->create([
            'run' => '7.775.757-K',
            'nombre' => 'María Núñez',
            'email' => 'maria@example.com',
        ]);

        $recovered = $this->manager->createFromCertificate(
            $this->manager->createFakeCertificate($original)
        );

        $this->assertSame($original->getRun(), $recovered->getRun());
        $this->assertSame('María Núñez', $recovered->getNombre());
    }
}
