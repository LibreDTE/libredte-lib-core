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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlParseException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafLoaderException;

/**
 * Interfaz para el worker que permite cargar un archivo XML con el CAF.
 */
interface CafLoaderWorkerInterface extends WorkerInterface
{
    /**
     * Carga el XML de un CAF y lo entrega como entidad CAF.
     *
     * @param string|XmlDocumentInterface $xml XML del CAF a cargar, como
     * string o como documento ya construido.
     * @return CafInterface El CAF cargado, con sus datos y folios
     * disponibles.
     * @throws XmlParseException Si el XML del CAF está vacío o mal formado.
     * @throws CafLoaderException Si el código de tipo de documento del CAF
     * no existe en el repositorio.
     */
    public function load(string|XmlDocumentInterface $xml): CafInterface;
}
