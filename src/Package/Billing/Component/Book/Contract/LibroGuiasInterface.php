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

namespace libredte\lib\Core\Package\Billing\Component\Book\Contract;

/**
 * Interfaz para el Libro de Guías de Despacho.
 *
 * Producido por `LibroGuiasWorker`. El resumen del período distingue entre
 * guías de venta (TpoOper=1) y guías de traslado (TpoOper>1).
 */
interface LibroGuiasInterface extends BookInterface
{
    /**
     * Entrega el resumen del período del libro de guías.
     *
     * Incluye contadores y montos de guías de venta, guías anuladas (folio
     * anulado y guía anulada) y guías de traslado agrupadas por `TpoOper`.
     *
     * @return array<string, mixed>
     */
    public function getResumen(): array;

    /**
     * Entrega el folio de notificación asignado por el SII al libro.
     *
     * Solo aplica cuando `TipoEnvio = 'ESPECIAL'`. En los demás casos
     * retorna `null`.
     *
     * @return int|null
     */
    public function getFolioNotificacion(): ?int;
}
