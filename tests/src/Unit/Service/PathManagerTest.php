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

use libredte\lib\Core\Service\PathManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PathManager::class)]
class PathManagerTest extends TestCase
{
    /**
     * Directorio base de la aplicación.
     *
     * Se usa como el directorio real para poder hacer las comparaciones.
     *
     * @var string
     */
    private string $baseDir;

    public function setUp(): void
    {
        $this->baseDir = dirname(dirname(dirname(dirname(__DIR__))));
    }

    public function testGetCertificatesPath(): void
    {
        // Probar sin archivo específico.
        $expected = $this->baseDir. '/resources/certificates';
        $actual = PathManager::getCertificatesPath();
        $this->assertEquals($expected, $actual);

        // Probar con un archivo que si existe.
        $filename = '100.cer';
        $expected = $this->baseDir. '/resources/certificates/' . $filename;
        $actual = PathManager::getCertificatesPath($filename);
        $this->assertEquals($expected, $actual);

        // Probar con un archivo que no existe.
        $filename = '200.cer';
        $expected = $this->baseDir. '/resources/certificates/' . $filename;
        $actual = PathManager::getCertificatesPath($filename);
        $this->assertNull($actual);
    }
}
