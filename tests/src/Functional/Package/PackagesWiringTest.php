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

namespace libredte\lib\Tests\Functional\Package;

use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Signature\Service\SignatureGenerator;
use Derafu\Signature\Service\SignatureService;
use Derafu\Signature\Service\SignatureValidator;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildEnvioRecibosJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildRespuestaEnvioJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponseWorker;
use libredte\lib\Core\Package\System\Component\Certificate\CertificateComponent;
use libredte\lib\Core\Package\System\Component\Certificate\Worker\LoaderWorker;
use libredte\lib\Core\Package\System\Component\Rendering\RenderingComponent;
use libredte\lib\Core\Package\System\SystemPackage;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(SystemPackage::class)]
#[CoversClass(CertificateComponent::class)]
#[CoversClass(LoaderWorker::class)]
#[CoversClass(RenderingComponent::class)]
#[CoversClass(BuildEnvioRecibosJob::class)]
#[CoversClass(BuildRespuestaEnvioJob::class)]
#[CoversClass(DocumentResponseWorker::class)]
#[UsesNamespace('libredte\lib\Core')]
class PackagesWiringTest extends TestCase
{
    public function testBillingPackageGroupsItsComponentsByName(): void
    {
        $package = Application::getInstance()->getPackageRegistry()->getBillingPackage();

        $components = $package->getComponents();

        $this->assertSame(
            ['book', 'document', 'exchange', 'identifier', 'integration', 'ownership_transfer', 'trading_parties'],
            array_keys($components)
        );
        $this->assertSame($package->getBookComponent(), $components['book']);
        $this->assertSame($package->getDocumentComponent(), $components['document']);
        $this->assertSame($package->getExchangeComponent(), $components['exchange']);
        $this->assertSame($package->getIdentifierComponent(), $components['identifier']);
        $this->assertSame($package->getIntegrationComponent(), $components['integration']);
        $this->assertSame($package->getOwnershipTransferComponent(), $components['ownership_transfer']);
        $this->assertSame($package->getTradingPartiesComponent(), $components['trading_parties']);
    }

    public function testSystemPackageGroupsItsComponentsByName(): void
    {
        $package = Application::getInstance()->getPackageRegistry()->getSystemPackage();

        $components = $package->getComponents();

        $this->assertSame(['certificate', 'repository', 'rendering'], array_keys($components));
        $this->assertSame($package->getCertificateComponent(), $components['certificate']);
        $this->assertSame($package->getRepositoryComponent(), $components['repository']);
        $this->assertSame($package->getRenderingComponent(), $components['rendering']);
    }

    public function testCertificateComponentExposesItsLoaderWhichReturnsTheSameCertificate(): void
    {
        $component = Application::getInstance()->getPackageRegistry()->getSystemPackage()->getCertificateComponent();
        $loader = $component->getLoaderWorker();

        $this->assertSame(['loader' => $loader], $component->getWorkers());

        $certificate = (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9');
        $this->assertSame($certificate, $loader->load($certificate));
        $this->assertSame($certificate, (new LoaderWorker())->load($certificate));
        $this->assertSame(['loader' => $loader], (new CertificateComponent($loader))->getWorkers());
        $this->assertSame($loader, (new CertificateComponent($loader))->getLoaderWorker());
    }

    public function testRenderingComponentHasNoWorkers(): void
    {
        $component = Application::getInstance()->getPackageRegistry()->getSystemPackage()->getRenderingComponent();

        $this->assertSame([], $component->getWorkers());
        $this->assertSame([], (new RenderingComponent())->getWorkers());
    }

    public function testResponseWorkerCanBeBuiltFromItsDependencies(): void
    {
        $xmlService = new XmlService(new XmlEncoder(), new XmlDecoder(), new XmlValidator());
        $generator = new SignatureGenerator($xmlService);
        $signature = new SignatureService($generator, new SignatureValidator($generator, $xmlService));

        $worker = new DocumentResponseWorker(
            new BuildEnvioRecibosJob(new XmlEncoder(), $signature),
            new BuildRespuestaEnvioJob(new XmlEncoder(), $signature),
            $xmlService,
            $signature
        );

        $this->assertInstanceOf(DocumentResponseWorker::class, $worker);
    }
}
