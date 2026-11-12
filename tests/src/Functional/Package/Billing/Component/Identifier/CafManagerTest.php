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

use Derafu\Translation\Exception\Core\TranslatableRuntimeException;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Service\CafManager;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFolio;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateArrayJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafManager::class)]
#[CoversClass(CafFolio::class)]
#[CoversClass(Caf::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(CreateArrayJob::class)]
#[CoversClass(CreateXmlJob::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
class CafManagerTest extends TestCase
{
    private CafFakerWorkerInterface $cafFaker;

    private CafManager $manager;

    protected function setUp(): void
    {
        $this->cafFaker = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafFakerWorker()
        ;

        $this->manager = new CafManager();
    }

    /**
     * Crea un CAF falso (con llaves reales) y lo agrega al gestor.
     */
    private function addCaf(int $dte, int $desde, int $hasta): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), $dte, $desde, $hasta);
        $this->manager->add($caf->getXml());
    }

    public function testConsumeDeliversFoliosInOrderAndNeverRepeats(): void
    {
        $this->addCaf(33, 1, 20);

        $folios = [];
        for ($i = 0; $i < 20; $i++) {
            $folios[] = $this->manager->consume(33)->getFolio();
        }

        $this->assertSame(range(1, 20), $folios);
        $this->assertFalse($this->manager->hasEnoughFolios(33));
    }

    public function testConsumeCrossesFromOneCafToTheNextOne(): void
    {
        // Se agregan en desorden: el gestor debe ordenar por folio desde.
        $this->addCaf(33, 11, 13);
        $this->addCaf(33, 1, 3);

        $consumed = [];
        for ($i = 0; $i < 6; $i++) {
            $folio = $this->manager->consume(33);
            $consumed[] = [$folio->getFolio(), $folio->getCaf()->getFolioDesde()];
        }

        $this->assertSame(
            [[1, 1], [2, 1], [3, 1], [11, 11], [12, 11], [13, 11]],
            $consumed
        );
    }

    public function testConsumeWithoutFoliosThrows(): void
    {
        $this->addCaf(33, 1, 2);
        $this->manager->consume(33);
        $this->manager->consume(33);

        $this->expectException(TranslatableRuntimeException::class);
        $this->manager->consume(33);
    }

    public function testConsumeOfTypeWithoutCafsThrows(): void
    {
        $this->addCaf(33, 1, 2);

        $this->expectException(TranslatableRuntimeException::class);
        $this->manager->consume(34);
    }

    public function testFoliosOfDifferentTypesAreIndependent(): void
    {
        $this->addCaf(33, 1, 5);
        $this->addCaf(61, 1, 5);

        $this->manager->consume(33);
        $this->manager->consume(33);

        $this->assertSame('3-5', $this->manager->getAvailableRange(33));
        $this->assertSame('1-5', $this->manager->getAvailableRange(61));
        $this->assertSame(1, $this->manager->consume(61)->getFolio());
    }

    public function testAddIgnoresCafWithSameTypeAndStartingFolio(): void
    {
        $this->addCaf(33, 1, 10);
        $this->addCaf(33, 1, 10);

        $this->assertCount(1, $this->manager->getCafs());
    }

    public function testGetCafsIsSortedByTypeAndThenByStartingFolio(): void
    {
        $this->addCaf(61, 1, 5);
        $this->addCaf(33, 21, 30);
        $this->addCaf(33, 1, 10);

        $ids = array_map(
            fn ($caf) => $caf->getId(),
            $this->manager->getCafs()
        );

        $this->assertSame(['CAF33D1H10', 'CAF33D21H30', 'CAF61D1H5'], $ids);
    }

    public function testMarkConsumedAcceptsRangesWithGaps(): void
    {
        $this->addCaf(33, 1, 20);
        $this->manager->markConsumed(33, '1-6,10,14,16-20');

        $this->assertSame('7-9,11-13,15', $this->manager->getAvailableRange(33));
        $this->assertSame(7, $this->manager->consume(33)->getFolio());
    }

    public function testMarkConsumedAcceptsArrayOfIntegers(): void
    {
        $this->addCaf(33, 1, 5);
        $this->manager->markConsumed(33, [1, 2, 4]);

        $this->assertSame('3,5', $this->manager->getAvailableRange(33));
    }

    public function testMarkConsumedIsAccumulative(): void
    {
        $this->addCaf(33, 1, 10);
        $this->manager->markConsumed(33, '1-3');
        $this->manager->markConsumed(33, '5');

        $this->assertSame('4,6-10', $this->manager->getAvailableRange(33));
    }

    public function testRangesToleratesSpacesAroundNumbers(): void
    {
        $this->addCaf(33, 1, 10);
        $this->manager->markConsumed(33, ' 1 - 3 , 5 ');

        $this->assertSame('4,6-10', $this->manager->getAvailableRange(33));
    }

    public function testSetAvailableRangeKeepsOnlyTheIndicatedFolios(): void
    {
        $this->addCaf(33, 1, 10);
        $this->manager->setAvailableRange(33, '1-3,10');

        $this->assertSame('1-3,10', $this->manager->getAvailableRange(33));

        $folios = [];
        while ($this->manager->hasEnoughFolios(33)) {
            $folios[] = $this->manager->consume(33)->getFolio();
        }
        $this->assertSame([1, 2, 3, 10], $folios);
    }

    public function testSetAvailableRangeReplacesPreviousConsumedState(): void
    {
        $this->addCaf(33, 1, 10);
        $this->manager->markConsumed(33, '1-10');
        $this->assertFalse($this->manager->hasEnoughFolios(33));

        $this->manager->setAvailableRange(33, [4, 5]);

        $this->assertSame('4-5', $this->manager->getAvailableRange(33));
    }

    public function testSetAvailableRangeAcrossSeveralCafs(): void
    {
        $this->addCaf(33, 1, 5);
        $this->addCaf(33, 6, 10);
        $this->manager->setAvailableRange(33, '4-7');

        // Los dos CAFs son contiguos, el rango se compacta entre ellos.
        $this->assertSame('4-7', $this->manager->getAvailableRange(33));
    }

    public function testSetAvailableRangeBeforeAddingCafsThrows(): void
    {
        $this->expectException(TranslatableRuntimeException::class);
        $this->manager->setAvailableRange(33, '1-5');
    }

    public function testGetAvailableRangeOfTypeWithoutCafsIsEmpty(): void
    {
        $this->assertSame('', $this->manager->getAvailableRange(33));
    }

    public function testGetAvailableRangeIsEmptyWhenAllFoliosAreConsumed(): void
    {
        $this->addCaf(33, 1, 2);
        $this->manager->markConsumed(33, '1-2');

        $this->assertSame('', $this->manager->getAvailableRange(33));
    }

    public function testGetAvailableRangeWithSingleFolioCaf(): void
    {
        $this->addCaf(33, 7, 7);

        $this->assertSame('7', $this->manager->getAvailableRange(33));
    }

    public function testGetAvailableRangeJoinsContiguousCafs(): void
    {
        $this->addCaf(33, 1, 5);
        $this->addCaf(33, 6, 10);
        $this->manager->consume(33);
        $this->manager->consume(33);
        $this->manager->consume(33);

        $this->assertSame('4-10', $this->manager->getAvailableRange(33));
    }

    public function testGetAvailableRangeKeepsSeparatedCafsApart(): void
    {
        $this->addCaf(33, 1, 5);
        $this->addCaf(33, 11, 15);

        $this->assertSame('1-5,11-15', $this->manager->getAvailableRange(33));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function provideOverlappingRanges(): array
    {
        // El CAF cargado va del folio 11 al 20.
        return [
            'overlaps the end' => [16, 25],
            'overlaps the start' => [5, 13],
            'inside' => [13, 15],
            'contains it' => [5, 30],
            'shares the last folio' => [20, 25],
            'shares the first folio' => [5, 11],
            'single folio inside' => [15, 15],
            'same start, longer' => [11, 30],
            'same start, shorter' => [11, 15],
            'same end, longer' => [5, 20],
        ];
    }

    #[DataProvider('provideOverlappingRanges')]
    public function testAddRejectsACafThatOverlapsALoadedOne(int $desde, int $hasta): void
    {
        $this->addCaf(33, 11, 20);
        $this->manager->consume(33);

        try {
            $this->addCaf(33, $desde, $hasta);
            $this->fail('An exception was expected.');
        } catch (TranslatableRuntimeException) {
            // El gestor queda como estaba.
            $this->assertCount(1, $this->manager->getCafs());
            $this->assertSame('12-20', $this->manager->getAvailableRange(33));
        }
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function provideRangesThatOverlapOneOfSeveralCafs(): array
    {
        // Los CAF cargados van del 1 al 10 y del 21 al 30.
        return [
            'spans both' => [8, 22],
            'overlaps only the second' => [25, 40],
            'overlaps only the second at its start' => [21, 21],
            'contains both' => [1, 30],
            'contains the second' => [20, 31],
            'inside the second' => [22, 28],
        ];
    }

    #[DataProvider('provideRangesThatOverlapOneOfSeveralCafs')]
    public function testAddRejectsACafThatOverlapsAnyOfSeveralLoadedCafs(int $desde, int $hasta): void
    {
        // Se cargan en desorden para no depender del orden de llegada.
        $this->addCaf(33, 21, 30);
        $this->addCaf(33, 1, 10);

        try {
            $this->addCaf(33, $desde, $hasta);
            $this->fail('An exception was expected.');
        } catch (TranslatableRuntimeException) {
            $this->assertCount(2, $this->manager->getCafs());
            $this->assertSame('1-10,21-30', $this->manager->getAvailableRange(33));
        }
    }

    public function testAddAcceptsACafThatFitsInTheGapBetweenLoadedCafs(): void
    {
        $this->addCaf(33, 1, 10);
        $this->addCaf(33, 21, 30);

        $this->addCaf(33, 11, 20);

        $this->assertCount(3, $this->manager->getCafs());
        $this->assertSame('1-30', $this->manager->getAvailableRange(33));
    }

    public function testAddIgnoresAnIdenticalCafEvenWhenAnotherOneIsLoadedBefore(): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), 33, 21, 30);
        $this->addCaf(33, 1, 10);
        $this->manager->add($caf->getXml());
        $this->manager->add($caf->getXml());

        $this->assertCount(2, $this->manager->getCafs());
    }

    public function testAddAcceptsContiguousCafsAndTheSameRangeOfAnotherType(): void
    {
        $this->addCaf(33, 11, 20);
        $this->addCaf(33, 21, 30);
        $this->addCaf(33, 1, 10);
        $this->addCaf(61, 5, 15);

        $this->assertCount(4, $this->manager->getCafs());
        $this->assertSame('1-30', $this->manager->getAvailableRange(33));
        $this->assertSame('5-15', $this->manager->getAvailableRange(61));
    }

    public function testResetConsumedFoliosMakesEverythingAvailableAgain(): void
    {
        $this->addCaf(33, 1, 5);
        $this->manager->markConsumed(33, '1-5');
        $this->assertFalse($this->manager->hasEnoughFolios(33));

        $this->manager->resetConsumedFolios(33);

        $this->assertSame('1-5', $this->manager->getAvailableRange(33));
        $this->assertSame(1, $this->manager->consume(33)->getFolio());
    }

    public function testResetConsumedFoliosOnlyAffectsTheGivenType(): void
    {
        $this->addCaf(33, 1, 5);
        $this->addCaf(61, 1, 5);
        $this->manager->markConsumed(33, '1-5');
        $this->manager->markConsumed(61, '1-5');

        $this->manager->resetConsumedFolios(33);

        $this->assertSame('1-5', $this->manager->getAvailableRange(33));
        $this->assertSame('', $this->manager->getAvailableRange(61));
    }

    public function testHasEnoughFoliosBoundaries(): void
    {
        $this->addCaf(33, 1, 3);
        $this->addCaf(33, 11, 12);

        // 5 folios disponibles entre los dos CAFs.
        $this->assertTrue($this->manager->hasEnoughFolios(33));
        $this->assertTrue($this->manager->hasEnoughFolios(33, 5));
        $this->assertFalse($this->manager->hasEnoughFolios(33, 6));

        $this->manager->consume(33);
        $this->assertTrue($this->manager->hasEnoughFolios(33, 4));
        $this->assertFalse($this->manager->hasEnoughFolios(33, 5));
    }

    public function testHasEnoughFoliosOfTypeWithoutCafsIsFalse(): void
    {
        $this->assertFalse($this->manager->hasEnoughFolios(33));
    }

    public function testRemoveDeletesOnlyTheCafWithThatStartingFolio(): void
    {
        $this->addCaf(33, 1, 5);
        $this->addCaf(33, 6, 10);

        $this->manager->remove(33, 1);

        $this->assertSame(['CAF33D6H10'], array_map(
            fn ($caf) => $caf->getId(),
            $this->manager->getCafs()
        ));
        $this->assertSame('6-10', $this->manager->getAvailableRange(33));
        $this->assertSame(6, $this->manager->consume(33)->getFolio());
    }

    public function testRemoveFreesTheLastFolioOfTheCafToo(): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), 33, 1, 3);
        $this->manager->add($caf->getXml());
        // Queda otro CAF cargado, así no se limpia todo el tipo al quitar el primero.
        $this->addCaf(33, 11, 13);
        $this->manager->markConsumed(33, '1-3');

        $this->manager->remove(33, 1);
        $this->manager->add($caf->getXml());

        $this->assertSame('1-3,11-13', $this->manager->getAvailableRange(33));
        $this->assertSame(1, $this->manager->consume(33)->getFolio());
        $this->assertSame(2, $this->manager->consume(33)->getFolio());
        $this->assertSame(3, $this->manager->consume(33)->getFolio());
    }

    public function testRemoveThenAddingTheSameCafRestoresAllItsFolios(): void
    {
        $caf = $this->cafFaker->create(new Emisor('76192083-9'), 33, 1, 5);
        $this->manager->add($caf->getXml());
        $this->manager->consume(33);
        $this->manager->consume(33);

        $this->manager->remove(33, 1);
        $this->manager->add($caf->getXml());

        $this->assertSame('1-5', $this->manager->getAvailableRange(33));
    }

    public function testRemoveOfTheLastCafLeavesTheTypeEmpty(): void
    {
        $this->addCaf(33, 1, 5);
        $this->addCaf(61, 1, 5);
        $this->manager->consume(33);

        $this->manager->remove(33, 1);

        $this->assertFalse($this->manager->hasEnoughFolios(33));
        $this->assertSame('', $this->manager->getAvailableRange(33));
        $this->assertCount(1, $this->manager->getCafs());

        $this->expectException(TranslatableRuntimeException::class);
        $this->manager->setAvailableRange(33, '1-5');
    }

    public function testRemoveOfTypeWithoutCafsThrows(): void
    {
        $this->expectException(TranslatableRuntimeException::class);
        $this->manager->remove(33, 1);
    }

    public function testRemoveWithUnknownStartingFolioThrowsAndKeepsTheCaf(): void
    {
        $this->addCaf(33, 1, 5);

        try {
            $this->manager->remove(33, 2);
            $this->fail('An exception was expected.');
        } catch (TranslatableRuntimeException) {
            $this->assertCount(1, $this->manager->getCafs());
            $this->assertSame('1-5', $this->manager->getAvailableRange(33));
        }
    }

    public function testGetCafForFolio(): void
    {
        $this->addCaf(33, 1, 5);
        $this->addCaf(33, 11, 15);

        $this->assertSame('CAF33D1H5', $this->manager->getCafForFolio(33, 1)->getId());
        $this->assertSame('CAF33D1H5', $this->manager->getCafForFolio(33, 5)->getId());
        $this->assertSame('CAF33D11H15', $this->manager->getCafForFolio(33, 11)->getId());
        $this->assertSame('CAF33D11H15', $this->manager->getCafForFolio(33, 15)->getId());
    }

    public function testGetCafForFolioInGapBetweenCafsThrows(): void
    {
        $this->addCaf(33, 1, 5);
        $this->addCaf(33, 11, 15);

        $this->expectException(TranslatableRuntimeException::class);
        $this->manager->getCafForFolio(33, 6);
    }

    public function testGetCafForFolioOutOfRangeThrows(): void
    {
        $this->addCaf(33, 1, 5);

        $this->expectException(TranslatableRuntimeException::class);
        $this->manager->getCafForFolio(33, 6);
    }

    public function testGetCafForFolioOfTypeWithoutCafsThrows(): void
    {
        $this->expectException(TranslatableRuntimeException::class);
        $this->manager->getCafForFolio(33, 1);
    }

    public function testConsumedFolioBelongsToTheCafThatContainsIt(): void
    {
        $this->addCaf(33, 1, 2);
        $this->addCaf(33, 11, 12);

        $this->manager->consume(33);
        $this->manager->consume(33);
        $folio = $this->manager->consume(33);

        $this->assertSame(11, $folio->getFolio());
        $this->assertTrue($folio->getCaf()->isEnRango($folio->getFolio()));
        $this->assertSame(
            $folio->getCaf()->getId(),
            $this->manager->getCafForFolio(33, $folio->getFolio())->getId()
        );
    }
}
