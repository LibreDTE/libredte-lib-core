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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafLoaderException;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Interfaz para el worker que crea archivos CAF falsos (de prueba).
 */
interface CafFakerWorkerInterface extends WorkerInterface
{
    /**
     * Genera y devuelve un CAF (Código de Autorización de Folios) ficticio para
     * el emisor (contribuyente).
     *
     * @param EmisorInterface $emisor Emisor al que se creará el CAF falso.
     * @param int $codigoDocumento Código del tipo de documento.
     * @param int|null $folioDesde Número de folio inicial.
     * @param int|null $folioHasta Número de folio final. Si es `null`, se usa
     * el mismo valor de $folioDesde.
     * @return CafInterface CAF ficticio generado para el emisor.
     * @throws CafLoaderException Si el CAF ficticio generado no puede ser
     * cargado (delega en `billing.identifier.caf_loader::load()`).
     */
    public function create(
        EmisorInterface $emisor,
        int $codigoDocumento,
        ?int $folioDesde = 1,
        ?int $folioHasta = null
    ): CafInterface;
}
