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
 * Interfaz para el Resumen de Ventas Diarias (RVD).
 *
 * Anteriormente denominado Consumo de Folios (RCOF). Producido por
 * `ResumenVentasDiariasWorker`.
 *
 * El XML resultante usa el tag raíz `<ConsumoFolios>` por compatibilidad con
 * el esquema `ConsumoFolio_v10.xsd` del SII.
 */
interface ResumenVentasDiariasInterface extends BookInterface
{
    /**
     * Entrega la fecha del primer documento incluido en el registro.
     *
     * Se calcula automáticamente a partir del campo `FchDoc` de los detalles.
     *
     * @return string Fecha en formato `YYYY-MM-DD`.
     */
    public function getFechaInicial(): string;

    /**
     * Entrega la fecha del último documento incluido en el registro.
     *
     * Se calcula automáticamente a partir del campo `FchDoc` de los detalles.
     *
     * @return string Fecha en formato `YYYY-MM-DD`.
     */
    public function getFechaFinal(): string;

    /**
     * Entrega el número de secuencia del envío.
     *
     * Corresponde al campo `SecEnvio` de la carátula.
     *
     * @return int
     */
    public function getSecuencia(): int;
}
