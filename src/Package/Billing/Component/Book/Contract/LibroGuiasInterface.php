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
