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

namespace libredte\lib\Core\Package\Billing\Component\Identifier;

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\IdentifierComponentInterface;

/**
 * Componente "billing.identifier".
 *
 * Este componente se encarga de la gestión de folios de documentos tributarios.
 */
#[Component(name: 'identifier', package: 'billing')]
class IdentifierComponent extends AbstractComponent implements IdentifierComponentInterface
{
    public function __construct(
        private CafFakerWorkerInterface $cafFaker,
        private CafLoaderWorkerInterface $cafLoader,
        private CafValidatorWorkerInterface $cafValidator
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'caf_faker' => $this->cafFaker,
            'caf_loader' => $this->cafLoader,
            'caf_validator' => $this->cafValidator,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCafFakerWorker(): CafFakerWorkerInterface
    {
        return $this->cafFaker;
    }

    /**
     * {@inheritDoc}
     */
    public function getCafLoaderWorker(): CafLoaderWorkerInterface
    {
        return $this->cafLoader;
    }

    /**
     * {@inheritDoc}
     */
    public function getCafValidatorWorker(): CafValidatorWorkerInterface
    {
        return $this->cafValidator;
    }
}
