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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRcvWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\CheckDocumentAssignabilityResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\GetDocumentSiiReceptionDateResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\ListDocumentEventsResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\SubmitDocumentAcceptanceResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job\CheckDocumentAssignabilityJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job\GetDocumentSiiReceptionDateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job\ListDocumentEventsJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job\SubmitDocumentAcceptanceJob;

/**
 * Clase del worker del Registro de Compra y Venta (RCV) del SII.
 */
#[Worker(name: 'sii_rcv', component: 'integration', package: 'billing')]
class SiiRcvWorker extends AbstractWorker implements SiiRcvWorkerInterface
{
    public function __construct(
        private SubmitDocumentAcceptanceJob $submitDocumentAcceptanceJob,
        private ListDocumentEventsJob $listDocumentEventsJob,
        private CheckDocumentAssignabilityJob $checkDocumentAssignabilityJob,
        private GetDocumentSiiReceptionDateJob $getDocumentSiiReceptionDateJob
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'request' => [
                'example' => [
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                    'options' => [
                        'environment' => SiiEnvironment::PRODUCTION,
                    ],
                ],
            ],
            'company' => ['example' => '12345678-5'],
            'document' => ['example' => 33],
            'number' => ['example' => 1],
            'action' => ['example' => 'ACD'],
        ],
    )]
    public function submitDocumentAcceptance(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $action
    ): SubmitDocumentAcceptanceResponse {
        return $this->submitDocumentAcceptanceJob->submit(
            $request,
            $company,
            $document,
            $number,
            $action
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'request' => [
                'example' => [
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                    'options' => [
                        'environment' => SiiEnvironment::PRODUCTION,
                    ],
                ],
            ],
            'company' => ['example' => '12345678-5'],
            'document' => ['example' => 33],
            'number' => ['example' => 1],
        ],
    )]
    public function listDocumentEvents(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): ListDocumentEventsResponse {
        return $this->listDocumentEventsJob->list(
            $request,
            $company,
            $document,
            $number
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'request' => [
                'example' => [
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                    'options' => [
                        'environment' => SiiEnvironment::PRODUCTION,
                    ],
                ],
            ],
            'company' => ['example' => '12345678-5'],
            'document' => ['example' => 33],
            'number' => ['example' => 1],
        ],
    )]
    public function checkDocumentAssignability(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): CheckDocumentAssignabilityResponse {
        return $this->checkDocumentAssignabilityJob->check(
            $request,
            $company,
            $document,
            $number
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'request' => [
                'example' => [
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                    'options' => [
                        'environment' => SiiEnvironment::PRODUCTION,
                    ],
                ],
            ],
            'company' => ['example' => '12345678-5'],
            'document' => ['example' => 33],
            'number' => ['example' => 1],
        ],
    )]
    public function getDocumentSiiReceptionDate(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): GetDocumentSiiReceptionDateResponse {
        return $this->getDocumentSiiReceptionDateJob->get(
            $request,
            $company,
            $document,
            $number
        );
    }
}
