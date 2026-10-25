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

namespace libredte\lib\Core\Package\Billing\Contract;

use Derafu\Backbone\Contract\PackageInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\IdentifierComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\IntegrationComponentInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Contract\OwnershipTransferComponentInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\TradingPartiesComponentInterface;

/**
 * Interfaz para `BillingPackage`.
 */
interface BillingPackageInterface extends PackageInterface
{
    /**
     * Entrega el componente "billing.book".
     *
     * @return BookComponentInterface
     */
    public function getBookComponent(): BookComponentInterface;

    /**
     * Entrega el componente "billing.document".
     *
     * @return DocumentComponentInterface
     */
    public function getDocumentComponent(): DocumentComponentInterface;

    /**
     * Entrega el componente "billing.exchange".
     *
     * @return ExchangeComponentInterface
     */
    public function getExchangeComponent(): ExchangeComponentInterface;

    /**
     * Entrega el componente "billing.identifier".
     *
     * @return IdentifierComponentInterface
     */
    public function getIdentifierComponent(): IdentifierComponentInterface;

    /**
     * Entrega el componente "billing.integration".
     *
     * @return IntegrationComponentInterface
     */
    public function getIntegrationComponent(): IntegrationComponentInterface;

    /**
     * Entrega el componente "billing.ownership_transfer".
     *
     * @return OwnershipTransferComponentInterface
     */
    public function getOwnershipTransferComponent(): OwnershipTransferComponentInterface;

    /**
     * Entrega el componente "billing.trading_parties".
     *
     * @return TradingPartiesComponentInterface
     */
    public function getTradingPartiesComponent(): TradingPartiesComponentInterface;
}
