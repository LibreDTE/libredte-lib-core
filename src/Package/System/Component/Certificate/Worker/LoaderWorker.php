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
