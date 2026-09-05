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

namespace libredte\lib\Core\Package\System;

use Derafu\Backbone\Abstract\AbstractPackage;
use Derafu\Backbone\Attribute\Package;
use libredte\lib\Core\Package\System\Component\Certificate\Contract\CertificateComponentInterface;
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
        private RepositoryComponentInterface $repositoryComponent
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
}
