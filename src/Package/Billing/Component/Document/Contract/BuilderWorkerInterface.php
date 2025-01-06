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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Lib\Core\Foundation\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BuilderException;

/**
 * Interfaz para los constructores de documentos.
 */
interface BuilderWorkerInterface extends WorkerInterface
{
    /**
     * Construye el documento tributario con los datos pasados.
     *
     * El documento generado dependerá de lo que se haya pasado:
     *
     *   - Borrador: Solo se pasaron datos de entrada.
     *   - Documento timbrado: Se incluyó folio real y CAF.
     *   - Documento timbrado y firmado: Se incluyó CAF y certificado digital.
     *
     * @param DocumentBagInterface $bag Contenedor con los datos del documento a
     * construir.
     * @return DocumentInterface
     * @throws BuilderException
     */
    public function build(DocumentBagInterface $bag): DocumentInterface;

    /**
     * Crea la instancia del DTE a partir del XmlDocument contenido en la bolsa.
     *
     * @param DocumentBagInterface $bag
     * @return DocumentInterface
     */
    public function create(DocumentBagInterface $bag): DocumentInterface;
}
