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
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiCheckXmlDocumentSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRequestXmlDocumentSentStatusByEmailResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiValidateDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiValidateDocumentSignatureResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\CheckXmlDocumentSentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\RequestXmlDocumentSentStatusByEmailJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\SendXmlDocumentJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ValidateDocumentJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ValidateDocumentSignatureJob;

/**
 * Clase del lazy worker del SII.
 */
#[Worker(name: 'sii_lazy', component: 'integration', package: 'billing')]
class SiiLazyWorker extends AbstractWorker implements SiiLazyWorkerInterface
{
    public function __construct(
        private AuthenticateJob $authenticateJob,
        private CheckXmlDocumentSentStatusJob $checkXmlDocumentSentStatusJob,
        private ConsumeWebserviceJob $consumeWebserviceJob,
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
    ): int {
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
    ): SiiCheckXmlDocumentSentStatusResponse {
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
    ): SiiRequestXmlDocumentSentStatusByEmailResponse {
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
    ): SiiValidateDocumentResponse {
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
    ): SiiValidateDocumentSignatureResponse {
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

    /**
     * {@inheritDoc}
     */
    public function consumeWebservice(
        SiiRequestInterface $request,
        string $service,
        string $function,
        array|int $args = [],
        ?int $retry = null
    ): XmlDocumentInterface {
        return $this->consumeWebserviceJob->sendRequest(
            $request,
            $service,
            $function,
            $args,
            $retry
        );
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(SiiRequestInterface $request): string
    {
        return $this->authenticateJob->authenticate($request);
    }
}
