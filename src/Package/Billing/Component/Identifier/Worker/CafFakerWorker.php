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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Worker que permite crear CAF falsos para pruebas.
 */
#[Worker(name: 'caf_faker', component: 'identifier', package: 'billing')]
class CafFakerWorker extends AbstractWorker implements CafFakerWorkerInterface
{
    public function __construct(
        private CreateXmlJob $createXmlJob,
        private CafLoaderWorkerInterface $cafLoader
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'emisor' => [
                'example' => [
                    'rut' => '12345678-9',
                    'razon_social' => 'Empresa de Prueba SpA',
                ],
            ],
            'codigoDocumento' => ['example' => 33],
            'folioDesde' => ['example' => 1],
            'folioHasta' => ['example' => 100],
        ],
    )]
    public function create(
        EmisorInterface $emisor,
        int $codigoDocumento,
        int $folioDesde = 1,
        ?int $folioHasta = null
    ): CafInterface {
        $xml = $this->createXmlJob->create(
            $emisor,
            $codigoDocumento,
            $folioDesde,
            $folioHasta
        );

        return $this->cafLoader->load($xml);
    }
}
