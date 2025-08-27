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

use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BuilderException;

/**
 * Interfaz base de las estrategias de construcción ("build") de documentos
 * tributarios.
 */
interface BuilderStrategyInterface extends StrategyInterface
{
    /**
     * Construye el documento tributario con los datos pasados.
     *
     * @param DocumentBagInterface $bag Contenedor con los datos del documento a
     * construir.
     * @return DocumentInterface
     * @throws BuilderException
     */
    public function build(DocumentBagInterface $bag): DocumentInterface;

    /**
     * Crea la instancia del DTE a partir del XmlDocument.
     *
     * @param XmlDocumentInterface $xmlDocument
     * @return DocumentInterface
     */
    public function create(XmlDocumentInterface $xmlDocument): DocumentInterface;
}
