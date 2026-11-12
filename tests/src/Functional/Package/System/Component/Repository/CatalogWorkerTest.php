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

namespace libredte\lib\Tests\Functional\Package\System\Component\Repository;

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\MedioPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\TipoDocumentoRepository;
use libredte\lib\Core\Package\System\Component\Repository\Contract\CatalogWorkerInterface;
use libredte\lib\Core\Package\System\Component\Repository\Exception\CatalogException;
use libredte\lib\Core\Package\System\Component\Repository\RepositoryComponent;
use libredte\lib\Core\Package\System\Component\Repository\Worker\CatalogWorker;
use libredte\lib\Core\Package\System\SystemPackage;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(SystemPackage::class)]
#[CoversClass(RepositoryComponent::class)]
#[CoversClass(CatalogWorker::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(MedioPago::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoDocumentoRepository::class)]
#[CoversClass(CategoriaDocumento::class)]
#[CoversClass(CodigoDocumento::class)]
class CatalogWorkerTest extends TestCase
{
    private CatalogWorkerInterface $catalog;

    protected function setUp(): void
    {
        $this->catalog = Application::getInstance()
            ->getPackageRegistry()
            ->getSystemPackage()
            ->getRepositoryComponent()
            ->getCatalogWorker()
        ;
    }

    /**
     * Verifica que los objetos entregados por el catálogo sean comunas.
     *
     * @param array<array-key, object> $objects
     * @return list<Comuna>
     */
    private function comunas(array $objects): array
    {
        $comunas = [];
        foreach ($objects as $object) {
            $this->assertInstanceOf(Comuna::class, $object);
            $comunas[] = $object;
        }

        return $comunas;
    }

    /**
     * @param array<array-key, object> $objects
     * @return list<string>
     */
    private function nombres(array $objects): array
    {
        return array_map(
            fn (Comuna $comuna) => $comuna->getNombre(),
            $this->comunas($objects)
        );
    }

    public function testComponentExposesTheCatalogWorker(): void
    {
        $component = Application::getInstance()
            ->getPackageRegistry()
            ->getSystemPackage()
            ->getRepositoryComponent()
        ;

        $this->assertSame($this->catalog, $component->getWorkers()['catalog']);
    }

    public function testListReturnsTheRegisteredRepositories(): void
    {
        $repositories = $this->catalog->list();

        $this->assertContains(Comuna::class, $repositories);
        $this->assertContains(MedioPago::class, $repositories);
        $this->assertContains(TipoDocumentoInterface::class, $repositories);
        $this->assertSame($repositories, array_values(array_unique($repositories)));
    }

    public function testEveryListedRepositoryCanBeExplored(): void
    {
        foreach ($this->catalog->list() as $repository) {
            $all = $this->catalog->findAll($repository);

            $this->assertNotEmpty($all, $repository);
            $this->assertSame(count($all), $this->catalog->count($repository), $repository);
        }
    }

    public function testFindByIdentifier(): void
    {
        $medio = $this->catalog->find(MedioPago::class, 'EF');

        $this->assertInstanceOf(MedioPago::class, $medio);
        $this->assertSame('Efectivo', (string) $medio);
    }

    public function testFindOfUnknownIdentifierReturnsNull(): void
    {
        $this->assertNull($this->catalog->find(MedioPago::class, 'NO_EXISTE'));
    }

    public function testFindWithIntegerIdentifier(): void
    {
        $tipo = $this->catalog->find(TipoDocumentoInterface::class, 33);

        $this->assertInstanceOf(TipoDocumento::class, $tipo);
    }

    public function testFindAllAndCount(): void
    {
        $this->assertCount(7, $this->catalog->findAll(MedioPago::class));
        $this->assertSame(7, $this->catalog->count(MedioPago::class));
    }

    public function testCountWithCriteriaMatchesFindBy(): void
    {
        $criteria = ['ciudad' => 'Santiago'];

        $found = $this->catalog->findBy(Comuna::class, $criteria);

        $this->assertNotEmpty($found);
        $this->assertSame(count($found), $this->catalog->count(Comuna::class, $criteria));
        $this->assertLessThan($this->catalog->count(Comuna::class), count($found));
        foreach ($this->comunas($found) as $comuna) {
            $this->assertSame('Santiago', $comuna->getCiudad());
        }
    }

    public function testCountWithCriteriaWithoutMatchesIsZero(): void
    {
        $this->assertSame(0, $this->catalog->count(Comuna::class, ['ciudad' => 'Ciudad Inexistente']));
        $this->assertSame([], $this->catalog->findBy(Comuna::class, ['ciudad' => 'Ciudad Inexistente']));
    }

    public function testFindByWithOrderLimitAndOffset(): void
    {
        $criteria = ['ciudad' => 'Santiago'];
        $all = $this->nombres(
            $this->catalog->findBy(Comuna::class, $criteria, ['nombre' => 'ASC'])
        );

        $page = $this->nombres(
            $this->catalog->findBy(Comuna::class, $criteria, ['nombre' => 'ASC'], 3, 2)
        );

        $sorted = $all;
        sort($sorted);
        $this->assertCount(3, $page);
        $this->assertSame(array_slice($all, 2, 3), $page);
        $this->assertSame($sorted[0], $all[0]);
    }

    public function testFindByWithDescendingOrderIsTheReverseOfAscending(): void
    {
        $asc = $this->nombres(
            $this->catalog->findBy(Comuna::class, ['ciudad' => 'Santiago'], ['nombre' => 'ASC'])
        );
        $desc = $this->nombres(
            $this->catalog->findBy(Comuna::class, ['ciudad' => 'Santiago'], ['nombre' => 'DESC'])
        );

        $this->assertSame(array_reverse($asc), $desc);
    }

    public function testFindOneByRespectsOrder(): void
    {
        $first = $this->catalog->findOneBy(Comuna::class, ['ciudad' => 'Santiago'], ['nombre' => 'ASC']);
        $last = $this->catalog->findOneBy(Comuna::class, ['ciudad' => 'Santiago'], ['nombre' => 'DESC']);

        $this->assertInstanceOf(Comuna::class, $first);
        $this->assertInstanceOf(Comuna::class, $last);
        $this->assertLessThan(0, strcmp($first->getNombre(), $last->getNombre()));
    }

    public function testFindOneByWithoutMatchesReturnsNull(): void
    {
        $this->assertNull($this->catalog->findOneBy(Comuna::class, ['ciudad' => 'Ciudad Inexistente']));
    }

    public function testUnknownRepositoryIsReportedAsCatalogException(): void
    {
        $operations = [
            fn () => $this->catalog->find('No\Existe', 1),
            fn () => $this->catalog->findAll('No\Existe'),
            fn () => $this->catalog->findBy('No\Existe'),
            fn () => $this->catalog->findOneBy('No\Existe'),
            fn () => $this->catalog->count('No\Existe'),
        ];

        foreach ($operations as $operation) {
            try {
                $operation();
                $this->fail('A CatalogException was expected.');
            } catch (CatalogException $e) {
                $this->assertStringContainsString('No\Existe', $e->getMessage());
                $this->assertNotNull($e->getPrevious());
            }
        }
    }
}
