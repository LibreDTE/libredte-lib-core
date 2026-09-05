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

namespace libredte\lib\Core\Package\System\Component\Certificate\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\System\Component\Certificate\Contract\LoaderWorkerInterface;

/**
 * Clase para la carga genérica de certificados digitales.
 */
#[Worker(name: 'loader', component: 'certificate', package: 'system')]
class LoaderWorker extends AbstractWorker implements LoaderWorkerInterface
{
    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'certificate' => [
                'example' => [
                    'data' => '',
                    'password' => '',
                ],
            ],
        ],
    )]
    public function load(CertificateInterface $certificate): CertificateInterface
    {
        return $certificate;
    }
}
