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

use Derafu\Backbone\Contract\StrategyInterface;

/**
 * Interfaz para las estrategias del `BuilderWorker`.
 *
 * Cada estrategia construye el XML y la entidad de un tipo de libro específico
 * a partir de los detalles ya normalizados por el `LoaderWorker`.
 *
 * Los nombres de estrategia son simplemente el tipo de libro:
 *   - `libro_ventas`
 *   - `libro_compras`
 *   - `libro_boletas`
 *   - `libro_guias`
 *   - `resumen_ventas_diarias`
 */
interface BuilderStrategyInterface extends StrategyInterface
{
    /**
     * Construye la entidad libro a partir del bag normalizado.
     *
     * @param BookBagInterface $bag Bag con detalles ya normalizados.
     * @return BookInterface Entidad libro resultante con su XML.
     */
    public function build(BookBagInterface $bag): BookInterface;
}
