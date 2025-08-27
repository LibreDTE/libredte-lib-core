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
final class Application extends MicroKernel implements ApplicationInterface
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
     * @var array<string,string>
     */
    protected const CONFIG_FILES = [
        'services.yaml' => 'yaml',
    ];

    /**
     * Cargadores de archivos de configuración.
     *
     * @var array<class-string>
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
        // Cargar configuración.
        $configurator->import(__DIR__ . '/../config/services.yaml');

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
