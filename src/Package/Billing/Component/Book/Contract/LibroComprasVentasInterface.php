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

namespace libredte\lib\Core\Package\Billing\Component\Book\Contract;

use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoOperacion;

/**
 * Interfaz para el Libro de Compras o Ventas (IECV).
 *
 * Producido tanto por `LibroComprasWorker` como por `LibroVentasWorker`. La
 * diferencia entre ambos libros reside exclusivamente en el `TipoOperacion`.
 */
interface LibroComprasVentasInterface extends BookInterface
{
    /**
     * Entrega el tipo de operación del libro: `TipoOperacion::COMPRA` o
     * `TipoOperacion::VENTA`.
     *
     * @return TipoOperacion
     */
    public function getTipoOperacion(): TipoOperacion;

    /**
     * Indica si el libro está en formato simplificado (`LibroCVS_v10.xsd`).
     *
     * El formato simplificado omite el detalle de documentos y solo incluye
     * el resumen del período.
     *
     * @return bool
     */
    public function isSimplificado(): bool;

    /**
     * Entrega los totales del período agrupados por tipo de documento.
     *
     * Cada elemento del arreglo corresponde a un tipo de documento distinto y
     * contiene los campos calculados (TotDoc, TotMntNeto, TotMntIVA, etc.).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTotalesPeriodo(): array;
}
