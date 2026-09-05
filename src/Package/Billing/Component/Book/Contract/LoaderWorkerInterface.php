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
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;

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
     * Carga y normaliza los datos de entrada de un libro tributario.
     *
     * Deja lista la carátula y el detalle del libro, en la forma exacta
     * que corresponde a su tipo, a partir de los datos de entrada
     * entregados en cualquiera de los formatos de origen soportados.
     *
     * Esta operación únicamente prepara esos datos: no construye el
     * libro ni genera su documento firmado, por lo que ese resultado
     * nunca forma parte de lo que esta operación entrega, sin importar
     * los datos de entrada recibidos. Para obtener el libro construido
     * y su documento hay que continuar con la operación de
     * construcción, usando esta misma carátula y detalle ya
     * normalizados.
     *
     * La autorización del emisor del libro (fecha y número de la
     * resolución que lo autoriza) solo aparece en el resultado cuando
     * esa autorización fue entregada junto con los datos del emisor; si
     * no se entregó, simplemente no va a estar presente.
     *
     * @param BookBagInterface $bag Datos de entrada del libro, junto con
     * su tipo y el emisor.
     * @return BookBagInterface Los mismos datos, con la carátula y el
     * detalle ya normalizados.
     * @throws BookException En caso de error al cargar o normalizar los
     * datos.
     */
    public function load(BookBagInterface $bag): BookBagInterface;
}
