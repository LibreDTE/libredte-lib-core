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

namespace libredte\lib\Tests\Functional;

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Contract\BillingPackageInterface;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
class ApplicationTest extends TestCase
{
    private array $testCases = [
        'services' => [
            'libredte.lib.billing' => BillingPackageInterface::class,
        ],
    ];

    public function testApplicationInitialization(): void
    {
        // Verificar que se puede obtener una instancia de la aplicación.
        $app = Application::getInstance();
        $this->assertInstanceOf(Application::class, $app);
    }

    public function testApplicationGetServices(): void
    {
        $app = Application::getInstance();

        foreach ($this->testCases['services'] as $name => $interface) {
            $this->assertInstanceOf($interface, $app->getService($name));
        }
    }

    public function testApplicationGetBillingDocumentParserAndParse(): void
    {
        $data = '{"Encabezado": {}}';

        $parsed =
            Application::getInstance()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getParserWorker()
            ->parse(new DocumentBag(
                inputData: $data,
                options: [
                    'parser' => [
                        'strategy' => 'json',
                    ],
                ]
            ))
        ;

        $this->assertNotEmpty($parsed);
        $this->assertIsArray($parsed);
    }
}
