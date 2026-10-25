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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Certificate\Contract\CertificateFakerInterface;
use Derafu\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioManagerWorkerInterface;

/**
 * Clase para el worker que administra los mandatarios.
 */
#[Worker(name: 'mandatario_manager', component: 'trading_parties', package: 'billing')]
class MandatarioManagerWorker extends AbstractWorker implements MandatarioManagerWorkerInterface
{
    public function __construct(
        private MandatarioFactoryInterface $mandatarioFactory,
        private CertificateFakerInterface $certificateFaker
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'certificate' => [
                'example' => [
                    'certificate' => '',
                    'privateKey' => '',
                ],
            ],
        ],
    )]
    public function createFromCertificate(
        CertificateInterface $certificate
    ): MandatarioInterface {
        return $this->mandatarioFactory->create([
            'run' => $certificate->getId(),
            'nombre' => $certificate->getName(),
            'email' => $certificate->getEmail(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'mandatario' => [
                'example' => [
                    'run' => '12345678-9',
                    'nombre' => 'Juan Pérez',
                    'email' => 'juan.perez@example.com',
                ],
            ],
        ],
    )]
    public function createFakeCertificate(
        MandatarioInterface $mandatario
    ): CertificateInterface {
        return $this->certificateFaker->createFake(
            id: $mandatario->getRun(),
            name: $mandatario->getNombre(),
            email: $mandatario->getEmail()
        );
    }
}
