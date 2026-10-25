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
use Derafu\Backbone\Attribute\Worker;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\AuthenticateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;

/**
 * Clase del lazy worker del SII.
 */
#[Worker(name: 'sii_lazy', component: 'integration', package: 'billing')]
class SiiLazyWorker extends AbstractWorker implements SiiLazyWorkerInterface
{
    public function __construct(
        private AuthenticateJob $authenticateJob,
        private ConsumeWebserviceJob $consumeWebserviceJob,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function consumeWebservice(
        SiiRequestInterface $request,
        string $service,
        string $function,
        array|int $args = [],
        ?int $retries = null,
        ?string $token = null
    ): XmlDocumentInterface {
        return $this->consumeWebserviceJob->sendRequest(
            $request,
            $service,
            $function,
            $args,
            $retries,
            $token
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
