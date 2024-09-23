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

use libredte\lib\Core\Repository\AduanaRepository;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\PathManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AduanaRepository::class)]
#[CoversClass(ArrayDataProvider::class)]
#[CoversClass(PathManager::class)]
class AduanaRepositoryTest extends TestCase
{
    private AduanaRepository $aduanaRepository;

    protected function setUp(): void
    {
        $this->aduanaRepository = new AduanaRepository();
    }

    public function testGetGlosaCorrect(): void
    {
        $glosa = $this->aduanaRepository->getGlosa('FmaPagExp');
        $this->assertEquals('Forma pago exp.', $glosa);
    }

    public function testGetGlosaIncorrect(): void
    {
        $glosa = $this->aduanaRepository->getGlosa('NonExistentTag');
        $this->assertFalse($glosa);
    }

    public function testGetValorCorrect(): void
    {
        $valor = $this->aduanaRepository->getValor('FmaPagExp', 1);
        $this->assertEquals('COB1', $valor);
    }

    public function testGetValorIncorrectTag(): void
    {
        $valor = $this->aduanaRepository->getValor('NonExistentTag', 1);
        $this->assertEquals('1', $valor);
    }

    public function testGetValorIncorrectCodigo(): void
    {
        $valor = $this->aduanaRepository->getValor('FmaPagExp', 999);
        $this->assertEquals('999', $valor);
    }

    public function testGetCodigoCorrect(): void
    {
        $codigo = $this->aduanaRepository->getCodigo('FmaPagExp', 'COB1');
        $this->assertEquals(1, $codigo);
    }

    public function testGetCodigoIncorrectTag(): void
    {
        $codigo = $this->aduanaRepository->getCodigo('NonExistentTag', 'COB1');
        $this->assertEquals('COB1', $codigo);
    }

    public function testGetCodigoIncorrectValue(): void
    {
        $codigo = $this->aduanaRepository->getCodigo('FmaPagExp', 'NON_EXISTENT');
        $this->assertEquals('NON_EXISTENT', $codigo);
    }

    public function testGetNacionalidades(): void
    {
        $nacionalidades = $this->aduanaRepository->getNacionalidades();
        $this->assertIsArray($nacionalidades);
        $this->assertArrayHasKey(563, $nacionalidades); // Alemania
    }

    public function testGetNacionalidadCorrect(): void
    {
        $nacionalidad = $this->aduanaRepository->getNacionalidad(563);
        $this->assertEquals('ALEMANIA', $nacionalidad);
    }

    public function testGetNacionalidadIncorrect(): void
    {
        $nacionalidad = $this->aduanaRepository->getNacionalidad(9999);
        $this->assertEquals('9999', $nacionalidad);
    }

    public function testGetFormasDePago(): void
    {
        $formasDePago = $this->aduanaRepository->getFormasDePago();
        $this->assertIsArray($formasDePago);
        $this->assertArrayHasKey(1, $formasDePago); // COB1
    }

    public function testGetModalidadesDeVenta(): void
    {
        $modalidadesDeVenta = $this->aduanaRepository->getModalidadesDeVenta();
        $this->assertIsArray($modalidadesDeVenta);
        $this->assertArrayHasKey(1, $modalidadesDeVenta); // A firme
    }

    public function testGetClausulasDeVenta(): void
    {
        $clausulasDeVenta = $this->aduanaRepository->getClausulasDeVenta();
        $this->assertIsArray($clausulasDeVenta);
        $this->assertArrayHasKey(1, $clausulasDeVenta); // CIF
    }

    public function testGetTransportes(): void
    {
        $transportes = $this->aduanaRepository->getTransportes();
        $this->assertIsArray($transportes);
        $this->assertArrayHasKey(1, $transportes); // Marítima, fluvial y lacustre
    }

    public function testGetPuertos(): void
    {
        $puertos = $this->aduanaRepository->getPuertos();
        $this->assertIsArray($puertos);
        $this->assertArrayHasKey(111, $puertos); // MONTREAL
    }

    public function testGetUnidades(): void
    {
        $unidades = $this->aduanaRepository->getUnidades();
        $this->assertIsArray($unidades);
        $this->assertArrayHasKey(1, $unidades); // TMB
    }

    public function testGetBultos(): void
    {
        $bultos = $this->aduanaRepository->getBultos();
        $this->assertIsArray($bultos);
        $this->assertArrayHasKey(1, $bultos); // POLVO
    }
}
