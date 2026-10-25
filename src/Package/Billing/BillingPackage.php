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

namespace libredte\lib\Core\Package\Billing;

use Derafu\Backbone\Abstract\AbstractPackage;
use Derafu\Backbone\Attribute\Package;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\IdentifierComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\IntegrationComponentInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Contract\OwnershipTransferComponentInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\TradingPartiesComponentInterface;
use libredte\lib\Core\Package\Billing\Contract\BillingPackageInterface;

/**
 * Paquete de facturación: "billing".
 *
 * Este paquete contiene los siguientes componentes:
 *
 * - `book`: Componente de libros de ventas y compras.
 * - `document`: Componente de documentos tributarios.
 * - `exchange`: Componente de intercambio de documentos tributarios.
 * - `identifier`: Componente de folios de documentos tributarios.
 * - `integration`: Componente de integración con el SII.
 * - `ownership_transfer`: Componente de transferencia de propiedad.
 * - `trading_parties`: Componente de partes comerciales.
 *
 * Este paquete es la parte central de LibreDTE. Pues es la que provee las funcionalidades que permiten crear, enviar y consultar documentos tributarios.
 */
#[Package(name: 'billing')]
class BillingPackage extends AbstractPackage implements BillingPackageInterface
{
    public function __construct(
        private BookComponentInterface $bookComponent,
        private DocumentComponentInterface $documentComponent,
        private ExchangeComponentInterface $exchangeComponent,
        private IdentifierComponentInterface $identifierComponent,
        private IntegrationComponentInterface $integrationComponent,
        private OwnershipTransferComponentInterface $ownershipTransferComponent,
        private TradingPartiesComponentInterface $tradingPartiesComponent
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getComponents(): array
    {
        return [
            'book' => $this->bookComponent,
            'document' => $this->documentComponent,
            'exchange' => $this->exchangeComponent,
            'identifier' => $this->identifierComponent,
            'integration' => $this->integrationComponent,
            'ownership_transfer' => $this->ownershipTransferComponent,
            'trading_parties' => $this->tradingPartiesComponent,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBookComponent(): BookComponentInterface
    {
        return $this->bookComponent;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentComponent(): DocumentComponentInterface
    {
        return $this->documentComponent;
    }

    /**
     * {@inheritDoc}
     */
    public function getExchangeComponent(): ExchangeComponentInterface
    {
        return $this->exchangeComponent;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierComponent(): IdentifierComponentInterface
    {
        return $this->identifierComponent;
    }

    /**
     * {@inheritDoc}
     */
    public function getIntegrationComponent(): IntegrationComponentInterface
    {
        return $this->integrationComponent;
    }

    /**
     * {@inheritDoc}
     */
    public function getOwnershipTransferComponent(): OwnershipTransferComponentInterface
    {
        return $this->ownershipTransferComponent;
    }

    /**
     * {@inheritDoc}
     */
    public function getTradingPartiesComponent(): TradingPartiesComponentInterface
    {
        return $this->tradingPartiesComponent;
    }
}
