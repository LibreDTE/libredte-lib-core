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

namespace libredte\lib\Core\Package\System\Component\Repository;

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use libredte\lib\Core\Package\System\Component\Repository\Contract\CatalogWorkerInterface;
use libredte\lib\Core\Package\System\Component\Repository\Contract\RepositoryComponentInterface;

/**
 * Componente "system.repository".
 *
 * Este componente se encarga de la exploración genérica de los repositorios
 * de datos registrados en la aplicación, sin depender de ningún paquete de
 * negocio en particular.
 */
#[Component(name: 'repository', package: 'system')]
class RepositoryComponent extends AbstractComponent implements RepositoryComponentInterface
{
    public function __construct(
        private CatalogWorkerInterface $catalogWorker
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'catalog' => $this->catalogWorker,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCatalogWorker(): CatalogWorkerInterface
    {
        return $this->catalogWorker;
    }
}
