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
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRtcWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\CheckAecSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\GetAssignmentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\CheckAecSentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\GetAssignmentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\GetRelatedAssignmentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\SendAecJob;

/**
 * Clase del worker del RTC del SII.
 *
 * Gestiona el envío del Archivo Electrónico de Cesión (AEC) al Registro de
 * Transferencias de Créditos (RTC) del SII y las consultas del estado de los
 * envíos y de las cesiones.
 */
#[Worker(name: 'sii_rtc', component: 'integration', package: 'billing')]
class SiiRtcWorker extends AbstractWorker implements SiiRtcWorkerInterface
{
    public function __construct(
        private SendAecJob $sendAecJob,
        private CheckAecSentStatusJob $checkAecSentStatusJob,
        private GetAssignmentStatusJob $getAssignmentStatusJob,
        private GetRelatedAssignmentStatusJob $getRelatedAssignmentStatusJob,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'send_aec',
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
            'doc' => ['example' => ''],
            'company' => ['example' => '12345678-5'],
            'emailNotif' => ['example' => 'cedente@example.com'],
        ],
    )]
    public function sendAec(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        string $emailNotif,
        ?int $retries = null
    ): SendAecResponse {
        return $this->sendAecJob->send(
            $request,
            $doc,
            $company,
            $emailNotif,
            $retries
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'check_aec_sent_status',
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
            'trackId' => ['example' => 1234567890],
        ],
    )]
    public function checkAecSentStatus(
        SiiRequestInterface $request,
        int $trackId
    ): CheckAecSentStatusResponse {
        return $this->checkAecSentStatusJob->check($request, $trackId);
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'get_assignment_status',
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
            'number' => ['example' => 1234],
            'assignmentId' => ['example' => ''],
        ],
    )]
    public function getAssignmentStatus(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        ?string $assignmentId = null
    ): GetAssignmentStatusResponse {
        return $this->getAssignmentStatusJob->get(
            $request,
            $company,
            $document,
            $number,
            $assignmentId
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'get_related_assignment_status',
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
            'number' => ['example' => 1234],
            'relatedCompany' => ['example' => '76192083-9'],
        ],
    )]
    public function getRelatedAssignmentStatus(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $relatedCompany
    ): GetAssignmentStatusResponse {
        return $this->getRelatedAssignmentStatusJob->get(
            $request,
            $company,
            $document,
            $number,
            $relatedCompany
        );
    }
}
