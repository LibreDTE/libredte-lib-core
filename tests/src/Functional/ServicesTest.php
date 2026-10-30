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

use Derafu\Kernel\Environment;
use Derafu\Renderer\Contract\FormatterInterface;
use Derafu\Renderer\Contract\RendererInterface;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaClausulaVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Provider\FormatterHandlerProvider;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Core\Provider\TemplatePathProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Integración del YAML publicado con proyectos consumidores y sus aportes.
 */
#[CoversClass(Application::class)]
#[CoversClass(FormatterHandlerProvider::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(TemplatePathProvider::class)]
#[UsesNamespace('libredte\lib\Core')]
final class ServicesTest extends TestCase
{
    protected function tearDown(): void
    {
        $directory = dirname(__DIR__, 2) . '/fixtures/services';
        (new Filesystem())->remove([
            $directory . '/var',
            $directory . '/consumer/var',
            $directory . '/override/var',
        ]);
    }

    public function testRepositoryUsesCoreDataWhenTheConsumerHasAnotherProjectDirectory(): void
    {
        $application = $this->application();
        $manager = $application->getService(RepositoryManagerInterface::class);
        self::assertInstanceOf(RepositoryManagerInterface::class, $manager);

        $saleClause = $manager->getRepository(AduanaClausulaVenta::class)->find(5);
        self::assertInstanceOf(AduanaClausulaVenta::class, $saleClause);
        self::assertSame('FOB', $saleClause->getAttribute('glosa'));
        self::assertSame(5, $saleClause->getAttribute('codigo'));
        $documentType = $manager->getRepository(TipoDocumentoInterface::class)->find(33);
        self::assertInstanceOf(TipoDocumento::class, $documentType);
        self::assertSame('Factura electrónica', (string) $documentType);
    }

    public function testCoreFormattingReachesDirectTwigAndPdfConsumers(): void
    {
        $application = $this->application();
        $formatter = $application->getService('test.formatter');
        self::assertInstanceOf(FormatterInterface::class, $formatter);
        self::assertSame('Factura electrónica', $formatter->format(33, 'billing_document.TipoDTE'));
        self::assertSame('76.192.083-9', $formatter->format('76192083-9', 'billing_document.RUTEmisor'));

        $renderer = $application->getService(RendererInterface::class);
        self::assertInstanceOf(RendererInterface::class, $renderer);
        $template = '<h1>{{ type|format_as("billing_document.TipoDTE") }}</h1>';
        self::assertSame('<h1>Factura electrónica</h1>', $renderer->renderFromString($template, ['type' => 33]));
        self::assertStringStartsWith('%PDF-', $renderer->renderFromString($template, ['type' => 33], ['engine' => 'pdf']));

        // La extensión de Twig debe observar cambios del formatter compartido.
        $formatter->registerHandler('consumer', 'Shared %s');
        self::assertSame(
            'Shared value',
            $renderer->renderFromString('{{ label|format_as("consumer") }}', ['label' => 'value'])
        );
    }

    public function testConsumerAddsTemplatesAndFormatsWithoutReplacingTheSharedRenderer(): void
    {
        $renderer = $this->application('/consumer')->getService(RendererInterface::class);
        self::assertInstanceOf(RendererInterface::class, $renderer);

        $html = $renderer->render('billing/document/renderer/estandar.html.twig', [
            'type' => 33,
            'label' => 'application',
        ]);
        self::assertStringContainsString('<h1>Factura electrónica</h1>', $html);
        self::assertStringContainsString('<p>Consumer application</p>', $html);
    }

    public function testConsumerCanReplaceOneFormattingHandlerByTagPriority(): void
    {
        $renderer = $this->application('/override')->getService(RendererInterface::class);
        self::assertInstanceOf(RendererInterface::class, $renderer);

        $html = $renderer->render('billing/document/renderer/estandar.html.twig', [
            'type' => 33,
            'label' => 'application',
        ]);
        self::assertStringContainsString('<h1>Replacement 33</h1>', $html);
        self::assertStringContainsString('<p>Consumer application</p>', $html);
    }

    public function testPublishedYamlLoadsFromAnInstalledLibraryWithoutExtraSearchDirectories(): void
    {
        $directory = sys_get_temp_dir() . '/libredte-core-services-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();
        $installedConfig = $directory . '/vendor/libredte/libredte-lib-core/resources/config';
        $filesystem->mkdir([$directory . '/config', $installedConfig]);

        try {
            // Copiar el YAML real reproduce su ubicación instalada sin otra
            // versión de la configuración ni una instalación Composer nueva.
            $filesystem->copy(
                Application::LIBRARY_DIR . '/resources/config/libredte-services.yaml',
                $installedConfig . '/libredte-services.yaml'
            );
            $filesystem->symlink(Application::LIBRARY_DIR . '/vendor/derafu', $directory . '/vendor/derafu');
            $filesystem->dumpFile(
                $directory . '/config/services.yaml',
                "imports:\n    - { resource: '../vendor/libredte/libredte-lib-core/resources/config/libredte-services.yaml' }\n"
            );

            // Solo el directorio de la aplicación: no usar el loader ampliado
            // de Core para ocultar imports relativos incorrectos del YAML.
            $container = new ContainerBuilder();
            (new YamlFileLoader($container, new FileLocator($directory . '/config')))
                ->load($directory . '/config/services.yaml');

            $application = new Application(new Environment('test', true, ['PROJECT_DIR' => $directory]));
            $manager = $application->getService(RepositoryManagerInterface::class);
            self::assertInstanceOf(RepositoryManagerInterface::class, $manager);
            $saleClause = $manager->getRepository(AduanaClausulaVenta::class)->find(5);
            self::assertInstanceOf(AduanaClausulaVenta::class, $saleClause);
            self::assertSame('FOB', $saleClause->getAttribute('glosa'));
        } finally {
            $filesystem->remove($directory);
        }
    }

    public function testConsumerCheckoutResolvesCoreWithoutASiblingRepository(): void
    {
        $sandbox = sys_get_temp_dir() . '/libredte-core-checkout-' . bin2hex(random_bytes(8));
        $directory = $sandbox . '/consumer';
        $filesystem = new Filesystem();
        $filesystem->mkdir([$directory . '/config', $directory . '/resources/config']);

        try {
            // Reproducir un checkout de consumidor: su YAML público conserva
            // el import relativo del layout vendor, pero Core no está al lado.
            $filesystem->dumpFile(
                $directory . '/config/services.yaml',
                "imports:\n    - { resource: '../resources/config/consumer-services.yaml' }\n"
            );
            $filesystem->dumpFile(
                $directory . '/resources/config/consumer-services.yaml',
                "imports:\n    - { resource: '../../../libredte-lib-core/resources/config/libredte-services.yaml' }\n"
            );
            self::assertDirectoryDoesNotExist($sandbox . '/libredte-lib-core');

            $application = new Application(new Environment('test', true, ['PROJECT_DIR' => $directory]));
            $manager = $application->getService(RepositoryManagerInterface::class);
            self::assertInstanceOf(RepositoryManagerInterface::class, $manager);
            $saleClause = $manager->getRepository(AduanaClausulaVenta::class)->find(5);
            self::assertInstanceOf(AduanaClausulaVenta::class, $saleClause);
            self::assertSame('FOB', $saleClause->getAttribute('glosa'));
        } finally {
            $filesystem->remove($sandbox);
        }
    }

    private function application(string $suffix = ''): Application
    {
        return new Application(new Environment('test', true, [
            'PROJECT_DIR' => dirname(__DIR__, 2) . '/fixtures/services' . $suffix,
        ]));
    }
}
