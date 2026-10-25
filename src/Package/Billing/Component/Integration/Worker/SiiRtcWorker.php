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
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\SendAecJob;

/**
 * Clase del worker del RTC del SII.
 *
 * Gestiona el envío del Archivo Electrónico de Cesión (AEC) al Registro de
 * Transferencias de Créditos (RTC) del SII.
 */
#[Worker(name: 'sii_rtc', component: 'integration', package: 'billing')]
class SiiRtcWorker extends AbstractWorker implements SiiRtcWorkerInterface
{
    public function __construct(
        private SendAecJob $sendAecJob,
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
}
