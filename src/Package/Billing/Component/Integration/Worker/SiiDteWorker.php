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
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDteWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\CheckXmlDocumentSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\RequestXmlDocumentSentStatusByEmailResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\SendXmlDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentSignatureResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\CheckXmlDocumentSentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\RequestXmlDocumentSentStatusByEmailJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\SendXmlDocumentJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\ValidateDocumentJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\ValidateDocumentSignatureJob;

/**
 * Clase del worker de DTE del SII.
 */
#[Worker(name: 'sii_dte', component: 'integration', package: 'billing')]
class SiiDteWorker extends AbstractWorker implements SiiDteWorkerInterface
{
    public function __construct(
        private CheckXmlDocumentSentStatusJob $checkXmlDocumentSentStatusJob,
        private RequestXmlDocumentSentStatusByEmailJob $requestXmlDocumentSentStatusByEmailJob,
        private SendXmlDocumentJob $sendXmlDocumentJob,
        private ValidateDocumentJob $validateDocumentJob,
        private ValidateDocumentSignatureJob $validateDocumentSignatureJob
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
        ],
    )]
    public function sendXmlDocument(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        bool $compress = false,
        ?int $retries = null
    ): SendXmlDocumentResponse {
        return $this->sendXmlDocumentJob->send(
            $request,
            $doc,
            $company,
            $compress,
            $retries
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
            'trackId' => ['example' => 123],
            'company' => ['example' => '12345678-5'],
        ],
    )]
    public function checkXmlDocumentSentStatus(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): CheckXmlDocumentSentStatusResponse {
        return $this->checkXmlDocumentSentStatusJob->checkSentStatus(
            $request,
            $trackId,
            $company
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
            'trackId' => ['example' => 123],
            'company' => ['example' => '12345678-5'],
        ],
    )]
    public function requestXmlDocumentSentStatusByEmail(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): RequestXmlDocumentSentStatusByEmailResponse {
        return $this->requestXmlDocumentSentStatusByEmailJob->requestEmail(
            $request,
            $trackId,
            $company
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
            'date' => ['example' => '2025-01-01'],
            'total' => ['example' => 1000],
            'recipient' => ['example' => '23456789-6'],
        ],
    )]
    public function validateDocument(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient
    ): ValidateDocumentResponse {
        return $this->validateDocumentJob->validate(
            $request,
            $company,
            $document,
            $number,
            $date,
            $total,
            $recipient
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
            'date' => ['example' => '2025-01-01'],
            'total' => ['example' => 1000],
            'recipient' => ['example' => '23456789-6'],
            'signature' => ['example' => ''],
        ],
    )]
    public function validateDocumentSignature(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient,
        string $signature
    ): ValidateDocumentSignatureResponse {
        return $this->validateDocumentSignatureJob->validate(
            $request,
            $company,
            $document,
            $number,
            $date,
            $total,
            $recipient,
            $signature
        );
    }
}
