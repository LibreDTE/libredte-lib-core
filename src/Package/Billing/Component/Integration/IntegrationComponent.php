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

namespace libredte\lib\Core\Package\Billing\Component\Integration;

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\IntegrationComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDteWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRcvWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRtcWorkerInterface;

/**
 * Componente "billing.integration".
 *
 * Este componente se encarga de la gestión de integración con el SII.
 */
#[Component(name: 'integration', package: 'billing')]
class IntegrationComponent extends AbstractComponent implements IntegrationComponentInterface
{
    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private SiiDteWorkerInterface $siiDteWorker,
        private SiiRcvWorkerInterface $siiRcvWorker,
        private SiiRtcWorkerInterface $siiRtcWorker,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'sii_lazy' => $this->siiLazyWorker,
            'sii_dte' => $this->siiDteWorker,
            'sii_rcv' => $this->siiRcvWorker,
            'sii_rtc' => $this->siiRtcWorker,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSiiLazyWorker(): SiiLazyWorkerInterface
    {
        return $this->siiLazyWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getSiiDteWorker(): SiiDteWorkerInterface
    {
        return $this->siiDteWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getSiiRcvWorker(): SiiRcvWorkerInterface
    {
        return $this->siiRcvWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getSiiRtcWorker(): SiiRtcWorkerInterface
    {
        return $this->siiRtcWorker;
    }
}
