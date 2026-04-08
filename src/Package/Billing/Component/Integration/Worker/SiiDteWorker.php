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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\ApiResource;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDteWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
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
    #[ApiResource(
        parametersExample: [
            'request' => [
                'certificate' => [
                    'data' => '',
                    'password' => '',
                ],
                'options' => [
                    'ambiente' => SiiAmbiente::PRODUCCION,
                ],
            ],
            'doc' => '',
            'company' => '12345678-5',
        ],
    )]
    public function sendXmlDocument(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        bool $compress = false,
        ?int $retry = null
    ): SendXmlDocumentResponse {
        return $this->sendXmlDocumentJob->send(
            $request,
            $doc,
            $company,
            $compress,
            $retry
        );
    }

    /**
     * {@inheritDoc}
     */
    #[ApiResource(
        parametersExample: [
            'request' => [
                'certificate' => [
                    'data' => '',
                    'password' => '',
                ],
                'options' => [
                    'ambiente' => SiiAmbiente::PRODUCCION,
                ],
            ],
            'trackId' => 123,
            'company' => '12345678-5',
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
    #[ApiResource(
        parametersExample: [
            'request' => [
                'certificate' => [
                    'data' => '',
                    'password' => '',
                ],
                'options' => [
                    'ambiente' => SiiAmbiente::PRODUCCION,
                ],
            ],
            'trackId' => 123,
            'company' => '12345678-5',
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
    #[ApiResource(
        parametersExample: [
            'request' => [
                'certificate' => [
                    'data' => '',
                    'password' => '',
                ],
                'options' => [
                    'ambiente' => SiiAmbiente::PRODUCCION,
                ],
            ],
            'company' => '12345678-5',
            'document' => 33,
            'number' => 1,
            'date' => '2025-01-01',
            'total' => 1000,
            'recipient' => '23456789-6',
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
    #[ApiResource(
        parametersExample: [
            'request' => [
                'certificate' => [
                    'data' => '',
                    'password' => '',
                ],
                'options' => [
                    'ambiente' => SiiAmbiente::PRODUCCION,
                ],
            ],
            'company' => '12345678-5',
            'document' => 33,
            'number' => 1,
            'date' => '2025-01-01',
            'total' => 1000,
            'recipient' => '23456789-6',
            'signature' => '',
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
