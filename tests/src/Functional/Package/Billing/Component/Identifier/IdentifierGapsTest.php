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

use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafLoaderException;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateArrayJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(Caf::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(CafValidatorWorker::class)]
#[CoversClass(CreateArrayJob::class)]
#[CoversClass(CreateXmlJob::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
class IdentifierGapsTest extends TestCase
{
    private function loader(): CafLoaderWorker
    {
        $manager = Application::getInstance()->getService(RepositoryManagerInterface::class);
        $this->assertInstanceOf(RepositoryManagerInterface::class, $manager);

        return new CafLoaderWorker($manager);
    }

    public function testTheComponentIsBuiltFromItsWorkersAndExposesThem(): void
    {
        $loader = $this->loader();
        $faker = new CafFakerWorker(
            new CreateXmlJob(
                new CreateArrayJob(),
                new XmlService(new XmlEncoder(), new XmlDecoder(), new XmlValidator())
            ),
            $loader
        );
        $validator = new CafValidatorWorker();

        $component = new IdentifierComponent($faker, $loader, $validator);

        $this->assertSame($faker, $component->getCafFakerWorker());
        $this->assertSame($loader, $component->getCafLoaderWorker());
        $this->assertSame($validator, $component->getCafValidatorWorker());
        $this->assertSame(
            ['caf_faker' => $faker, 'caf_loader' => $loader, 'caf_validator' => $validator],
            $component->getWorkers()
        );
        $this->assertSame(1, $faker->create(new Emisor('76192083-9'), 33, 1, 3)->getFolioDesde());
    }

    public function testFakeCafWithAnInvertedRangeIsRejected(): void
    {
        $faker = Application::getInstance()->getPackageRegistry()->getBillingPackage()
            ->getIdentifierComponent()->getCafFakerWorker();

        $this->expectException(CafException::class);
        $faker->create(new Emisor('76192083-9'), 33, 10, 5);
    }

    public function testFakeCafOfAnUnknownDocumentTypeIsRejectedByTheLoader(): void
    {
        $faker = Application::getInstance()->getPackageRegistry()->getBillingPackage()
            ->getIdentifierComponent()->getCafFakerWorker();

        $this->expectException(CafLoaderException::class);
        $faker->create(new Emisor('76192083-9'), 999, 1, 5);
    }

    public function testCafThatExpiresButHasNoAuthorizationDateCannotTellItsExpirationDate(): void
    {
        $faker = Application::getInstance()->getPackageRegistry()->getBillingPackage()
            ->getIdentifierComponent()->getCafFakerWorker();
        $xml = $faker->create(new Emisor('76192083-9'), 33, 1, 5)->getXml();
        $withoutDate = preg_replace('#<FA>[^<]*</FA>#', '<FA></FA>', $xml);
        $this->assertIsString($withoutDate);
        $this->assertNotSame($xml, $withoutDate);

        $this->expectException(CafException::class);
        (new Caf($withoutDate))->getFechaVencimiento();
    }
}
