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

namespace libredte\lib\Core\Package\System;

use Derafu\Backbone\Abstract\AbstractPackage;
use Derafu\Backbone\Attribute\Package;
use libredte\lib\Core\Package\System\Component\Certificate\Contract\CertificateComponentInterface;
use libredte\lib\Core\Package\System\Component\Rendering\Contract\RenderingComponentInterface;
use libredte\lib\Core\Package\System\Component\Repository\Contract\RepositoryComponentInterface;
use libredte\lib\Core\Package\System\Contract\SystemPackageInterface;

/**
 * Paquete "system".
 *
 * Este paquete contiene los siguientes componentes:
 *
 * - `certificate`: Componente de carga genérica de certificados digitales.
 * - `repository`: Componente de exploración genérica de repositorios de
 *   datos.
 * - `rendering`: Componente con los tipos compartidos del resultado de un
 *   renderizado.
 *
 * A diferencia de `billing`, este paquete no provee funcionalidades de
 * negocio: agrupa capacidades transversales de la aplicación que no
 * dependen de ningún paquete de negocio en particular.
 */
#[Package(name: 'system')]
class SystemPackage extends AbstractPackage implements SystemPackageInterface
{
    public function __construct(
        private CertificateComponentInterface $certificateComponent,
        private RepositoryComponentInterface $repositoryComponent,
        private RenderingComponentInterface $renderingComponent
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getComponents(): array
    {
        return [
            'certificate' => $this->certificateComponent,
            'repository' => $this->repositoryComponent,
            'rendering' => $this->renderingComponent,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCertificateComponent(): CertificateComponentInterface
    {
        return $this->certificateComponent;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryComponent(): RepositoryComponentInterface
    {
        return $this->repositoryComponent;
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderingComponent(): RenderingComponentInterface
    {
        return $this->renderingComponent;
    }
}
