<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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

use libredte\lib\Core\Repository\MediosDePagoRepository;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\PathManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MediosDePagoRepository::class)]
#[CoversClass(ArrayDataProvider::class)]
#[CoversClass(PathManager::class)]
class MediosDePagoRepositoryTest extends TestCase
{
    private MediosDePagoRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new MediosDePagoRepository();
    }

    public function testGetValor(): void
    {
        $this->assertEquals('Efectivo', $this->repository->getValor('EF'));
        $this->assertEquals('Tarjeta de crédito o débito', $this->repository->getValor('TC'));
    }

    public function testGetCodigo(): void
    {
        $this->assertEquals('EF', $this->repository->getCodigo('Efectivo'));
        $this->assertEquals('TC', $this->repository->getCodigo('Tarjeta de crédito o débito'));
    }
}
