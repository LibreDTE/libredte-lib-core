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

namespace libredte\lib\Core;

use Derafu\Backbone\Contract\PackageRegistryInterface;
use Derafu\Backbone\DependencyInjection\ServiceConfigurationCompilerPass;
use Derafu\Backbone\DependencyInjection\ServiceProcessingCompilerPass;
use Derafu\Kernel\Contract\EnvironmentInterface;
use Derafu\Kernel\MicroKernel;
use libredte\lib\Core\Contract\ApplicationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Clase principal de la aplicación.
 */
class Application extends MicroKernel implements ApplicationInterface
{
    /**
     * Instancia de la aplicación.
     *
     * @var self
     */
    private static self $instance;

    /**
     * Archivos de configuración.
     *
     * NOTE: LibreDTE no debe cargar archivos de configuración del proyecto que
     * lo usa. Los archivos de configuración de LibreDTE se cargan de manera
     * explícita en el método `Application::configure()`.
     *
     * @var array<string,string>
     */
    protected const CONFIG_FILES = [
        'services.yaml' => 'yaml',
    ];

    /**
     * Cargadores de archivos de configuración.
     *
     * @var array<class-string<\Symfony\Component\Config\Loader\LoaderInterface>>
     */
    protected const CONFIG_LOADERS = [
        PhpFileLoader::class,
        YamlFileLoader::class,
    ];

    /**
     * Configura el contenedor de dependencias.
     *
     * @param ContainerConfigurator $configurator
     * @param ContainerBuilder $container
     */
    protected function configure(
        ContainerConfigurator $configurator,
        ContainerBuilder $container
    ): void {
        // Agregar compiler passes.
        $container->addCompilerPass(
            new ServiceProcessingCompilerPass('libredte.lib.')
        );
        $container->addCompilerPass(
            new ServiceConfigurationCompilerPass('libredte.lib.')
        );
    }

    /**
     * Entrega el registro de paquetes de la aplicación.
     *
     * @return PackageRegistry
     */
    public function getPackageRegistry(): PackageRegistry
    {
        return $this->getContainer()->get(PackageRegistryInterface::class);
    }

    /**
     * Entrega un servicio de la aplicación.
     *
     * @param string $id
     * @return mixed
     */
    public function getService(string $id): mixed
    {
        return $this->getContainer()->get($id);
    }

    /**
     * Entrega la instancia de la aplicación.
     *
     * Este método se asegura de entregar una única instancia de la aplicación
     * mediante el patrón singleton.
     *
     * Al utilizar inyección de dependencias y registrar la aplicación de
     * LibreDTE en un contenedor de dependencias no será necesario, ni
     * recomendado, utilizar este método. En ese caso se debe utilizar solo el
     * contenedor de dependencias para obtener la aplicación de LibreDTE.
     *
     * @param string|EnvironmentInterface $environment
     * @param bool $debug
     * @return self
     */
    public static function getInstance(
        string|EnvironmentInterface $environment = 'dev',
        bool $debug = true
    ): self {
        if (!isset(self::$instance)) {
            self::$instance = new self($environment, $debug);
        }

        return self::$instance;
    }
}
