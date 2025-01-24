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

namespace libredte\lib\Core\Package\Billing;

use Derafu\Lib\Core\Foundation\Abstract\AbstractPackage;
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
 */
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
