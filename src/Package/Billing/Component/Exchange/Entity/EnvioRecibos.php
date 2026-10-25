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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Entity;

use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeDocument;

/**
 * Entidad que representa el XML `EnvioRecibos`.
 *
 * Contiene los recibos de mercaderías o servicios prestados por un proveedor.
 * Cada `Recibo` está firmado individualmente con el ID `LibreDTE_T{tipo}F{folio}`,
 * y el `SetRecibos` completo está firmado con ID `LibreDTE_SetDteRecibidos`.
 */
class EnvioRecibos extends AbstractExchangeDocument
{
    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return (string) ($this->getXmlDocument()->query('//SetRecibos/@ID') ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getSchema(): string
    {
        return 'EnvioRecibos_v10.xsd';
    }
}
