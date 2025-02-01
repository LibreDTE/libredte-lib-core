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

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
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
    public function sendXmlDocument(
        SiiRequestInterface $request,
        XmlInterface $doc,
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
    ): XmlInterface {
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
