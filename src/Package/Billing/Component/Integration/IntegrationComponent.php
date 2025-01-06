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

namespace libredte\lib\Core\Package\Billing\Component\Integration;

use Derafu\Lib\Core\Foundation\Abstract\AbstractComponent;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\IntegrationComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDeliveryCheckerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDocumentSenderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDocumentValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiTokenManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiWsdlConsumerWorkerInterface;

/**
 * Componente "billing.integration".
 */
class IntegrationComponent extends AbstractComponent implements IntegrationComponentInterface
{
    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private SiiWsdlConsumerWorkerInterface $siiWsdlConsumerWorker,
        private SiiTokenManagerWorkerInterface $siiTokenManagerWorker,
        private SiiDocumentSenderWorkerInterface $siiDocumentSenderWorker,
        private SiiDeliveryCheckerWorkerInterface $siiDeliveryCheckerWorker,
        private SiiDocumentValidatorWorkerInterface $siiDocumentValidatorWorker
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkers(): array
    {
        return [
            'sii_lazy' => $this->siiLazyWorker,
            'sii_wsdl_consumer' => $this->siiWsdlConsumerWorker,
            'sii_token_manager' => $this->siiTokenManagerWorker,
            'sii_document_sender' => $this->siiDocumentSenderWorker,
            'sii_delivery_checker' => $this->siiDeliveryCheckerWorker,
            'sii_document_validator' => $this->siiDocumentValidatorWorker
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSiiLazyWorker(): SiiLazyWorkerInterface
    {
        return $this->siiLazyWorker;
    }

    /**
     * {@inheritdoc}
     */
    public function getSiiWsdlConsumerWorker(): SiiWsdlConsumerWorkerInterface
    {
        return $this->siiWsdlConsumerWorker;
    }

    /**
     * {@inheritdoc}
     */
    public function getSiiTokenManagerWorker(): SiiTokenManagerWorkerInterface
    {
        return $this->siiTokenManagerWorker;
    }

    /**
     * {@inheritdoc}
     */
    public function getSiiDocumentSenderWorker(): SiiDocumentSenderWorkerInterface
    {
        return $this->siiDocumentSenderWorker;
    }

    /**
     * {@inheritdoc}
     */
    public function getSiiDeliveryCheckerWorker(): SiiDeliveryCheckerWorkerInterface
    {
        return $this->siiDeliveryCheckerWorker;
    }

    /**
     * {@inheritdoc}
     */
    public function getSiiDocumentValidatorWorker(): SiiDocumentValidatorWorkerInterface
    {
        return $this->siiDocumentValidatorWorker;
    }
}
