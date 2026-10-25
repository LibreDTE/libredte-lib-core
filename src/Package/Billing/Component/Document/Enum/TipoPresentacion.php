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

namespace libredte\lib\Core\Package\Billing\Component\Document\Enum;

/**
 * Presentación con la que se renderiza un documento.
 *
 * Un mismo documento (mismos datos) puede renderizarse con distintas
 * presentaciones: no cambia su contenido tributario, solo cómo se ve (ej.
 * leyendas, marcas de agua o bloques adicionales impresos). No debe
 * confundirse con la estrategia de renderizado (ej. plantilla vs. TCPDF),
 * que decide *cómo* se genera el archivo, no *qué* se le agrega visualmente.
 */
enum TipoPresentacion: string
{
    /**
     * Copia tributaria: sin leyenda "CEDIBLE" ni bloque de acuse de recibo.
     */
    case TRIBUTARIA = 'tributaria';

    /**
     * Copia cedible: incluye la leyenda "CEDIBLE" y el bloque de acuse de
     * recibo (Ley 19.983), cuando el tipo de documento lo admite.
     *
     * @see \libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface::requiresAcuseRecibo()
     */
    case CEDIBLE = 'cedible';
}
