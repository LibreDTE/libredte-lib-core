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

namespace libredte\lib\Tests\Functional\Repository;

use libredte\lib\Core\Repository\ImpuestosAdicionalesRepository;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\PathManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImpuestosAdicionalesRepository::class)]
#[CoversClass(ArrayDataProvider::class)]
#[CoversClass(PathManager::class)]
class ImpuestosAdicionalesRepositoryTest extends TestCase
{
    private ImpuestosAdicionalesRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new ImpuestosAdicionalesRepository();
    }

    public function testGetTipo(): void
    {
        $this->assertSame('R', $this->repository->getTipo(15));
        $this->assertSame('A', $this->repository->getTipo(17));
        $this->assertFalse($this->repository->getTipo(999));
    }

    public function testGetGlosa(): void
    {
        $this->assertSame('IVA retenido', $this->repository->getGlosa(15));
        $this->assertSame('Licores, Piscos, Whisky', $this->repository->getGlosa(24));
        $this->assertSame('Impto. cód. 999', $this->repository->getGlosa(999));
    }

    public function testGetTasa(): void
    {
        $this->assertSame(19, $this->repository->getTasa(15));
        $this->assertSame(31.5, $this->repository->getTasa(24));
        $this->assertFalse($this->repository->getTasa(999));
    }

    public function testGetRetenido(): void
    {
        $OtrosImp = [
            ['CodImp' => 15, 'MntImp' => 1000],
            ['CodImp' => 17, 'MntImp' => 500],
            ['CodImp' => 30, 'MntImp' => 700],
        ];

        $this->assertSame(1700, $this->repository->getRetenido($OtrosImp));
    }
}
