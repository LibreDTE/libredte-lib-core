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

use Derafu\Backbone\Contract\WorkerInterface;

/**
 * Interfaz para el worker `billing.book.loader`.
 *
 * Responsable de cargar y normalizar los datos de entrada de cualquier tipo de
 * libro tributario desde cualquier formato de origen (array, CSV, XML, etc.).
 *
 * Selecciona la estrategia como `{tipo}.{formato}` donde:
 *   - `tipo` proviene de `BookBagInterface::getTipo()`.
 *   - `formato` proviene de `BookBagInterface::getLoaderOptions()['format']`
 *     (por defecto 'array').
 */
interface LoaderWorkerInterface extends WorkerInterface
{
    /**
     * Carga y normaliza los detalles del bag.
     *
     * @param BookBagInterface $bag Bag con detalles crudos.
     * @return BookBagInterface El mismo bag con detalles normalizados.
     */
    public function load(BookBagInterface $bag): BookBagInterface;
}
