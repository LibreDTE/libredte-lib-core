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
 * Interfaz para el worker `billing.book.builder`.
 *
 * Responsable de construir el XML y la entidad resultante de cualquier tipo de
 * libro tributario a partir del bag con detalles normalizados.
 *
 * Selecciona la estrategia usando `BookBagInterface::getTipo()` directamente,
 * ya que no depende del formato de origen (eso es responsabilidad del loader).
 */
interface BuilderWorkerInterface extends WorkerInterface
{
    /**
     * Construye la entidad libro a partir del bag normalizado.
     *
     * @param BookBagInterface $bag Bag con detalles ya normalizados.
     * @return BookInterface Entidad libro resultante con su XML.
     */
    public function build(BookBagInterface $bag): BookInterface;
}
