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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Contract;

use Derafu\Backbone\Contract\ComponentInterface;

/**
 * Interfaz para `IntegrationComponent`.
 */
interface IntegrationComponentInterface extends ComponentInterface
{
    /**
     * Entrega el lazy worker del SII que maneja la autenticación y el consumo
     * de servicios web.
     *
     * @return SiiLazyWorkerInterface
     */
    public function getSiiLazyWorker(): SiiLazyWorkerInterface;

    /**
     * Entrega el worker del SII para realizar las acciones de DTE.
     *
     * @return SiiDteWorkerInterface
     */
    public function getSiiDteWorker(): SiiDteWorkerInterface;

    /**
     * Entrega el worker del SII para realizar las acciones del RCV.
     *
     * @return SiiRcvWorkerInterface
     */
    public function getSiiRcvWorker(): SiiRcvWorkerInterface;

    /**
     * Entrega el worker del SII para realizar las acciones del RTC.
     *
     * @return SiiRtcWorkerInterface
     */
    public function getSiiRtcWorker(): SiiRtcWorkerInterface;
}
