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
use Derafu\Backbone\Attribute\Worker;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRcvWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
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
