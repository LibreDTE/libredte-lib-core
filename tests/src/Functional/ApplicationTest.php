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

namespace libredte\lib\Tests\Functional;

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Contract\BillingPackageInterface;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]class ApplicationTest extends TestCase
{
    /**
     * @var array{services: array<string, class-string>}
     */
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

        $bag = new DocumentBag(
            inputData: $data,
            options: [
                'parser' => [
                    'strategy' => 'json',
                ],
            ]
        );

        $result =
            Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getParserWorker()
            ->parse($bag)
        ;

        $this->assertSame($bag, $result);
        $this->assertNotEmpty($result->getParsedData());
    }
}
