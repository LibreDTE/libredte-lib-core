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

namespace libredte\lib\Tests\Unit\Service;

use LogicException;
use libredte\lib\Core\Service\ArrayDataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
#[CoversClass(ArrayDataProvider::class)]
final class ArrayDataProviderTest extends TestCase
{
    private ArrayDataProvider $provider;

    protected function setUp(): void
    {
        $dataFilepath = getenv('LIBREDTE_TESTS_DIR')
            . '/resources/data/%s.php'
        ;
        $this->provider = new ArrayDataProvider($dataFilepath);
    }

    public function testGetDataLoadsDataFromFile(): void
    {
        $data = $this->provider->getData('sample');

        $this->assertIsArray($data);
        $this->assertSame(['foo' => 'bar'], $data);
    }

    public function testGetValueReturnsCorrectValue(): void
    {
        $this->provider->addData('sample', ['foo' => 'bar']);

        $this->assertSame('bar', $this->provider->getValue('sample', 'foo'));
        $this->assertSame('default', $this->provider->getValue('sample', 'baz', 'default'));
    }

    public function testHasDataReturnsFalseForNonExistentKey(): void
    {
        $this->assertFalse($this->provider->hasData('non_existent'));
    }

    public function testAddDataStoresDataCorrectly(): void
    {
        $this->provider->addData('test', ['key' => 'value']);

        $this->assertTrue($this->provider->hasData('test'));
        $this->assertSame(['key' => 'value'], $this->provider->getData('test'));
    }

    public function testRemoveDataDeletesData(): void
    {
        $this->provider->addData('test', ['key' => 'value']);
        $this->provider->removeData('test');

        $this->assertFalse($this->provider->hasData('test'));
    }

    public function testGetDataThrowsExceptionForMissingFile(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No se encontraron datos para la clave non_existent.');

        $this->provider->getData('non_existent');
    }

    public function testLoadDataThrowsExceptionForInvalidData(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No se encontraron datos para la clave invalid.');

        $this->provider->getData('invalid');
    }
}
