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

namespace libredte\lib\Core\Package\Billing\Component\OwnershipTransfer;

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Contract\AecWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Contract\OwnershipTransferComponentInterface;

/**
 * Componente "billing.ownership_transfer".
 *
 * Este componente se encarga de la gestión de transferencia de propiedad de
 * documentos tributarios (cesión electrónica / factoring).
 */
#[Component(name: 'ownership_transfer', package: 'billing')]
class OwnershipTransferComponent extends AbstractComponent implements OwnershipTransferComponentInterface
{
    public function __construct(
        private AecWorkerInterface $aecWorker,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'aec' => $this->aecWorker,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getAecWorker(): AecWorkerInterface
    {
        return $this->aecWorker;
    }
}
