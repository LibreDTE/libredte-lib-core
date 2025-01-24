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

namespace libredte\lib\Core\Package\Billing\Contract;

use Derafu\Lib\Core\Foundation\Contract\PackageInterface;
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
