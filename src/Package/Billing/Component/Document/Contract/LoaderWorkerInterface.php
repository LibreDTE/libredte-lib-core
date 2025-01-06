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
use libredte\lib\Core\Package\Billing\Component\Document\Exception\LoaderException;

/**
 * Interfaz para los cargadores de documentos.
 */
interface LoaderWorkerInterface extends WorkerInterface
{
    /**
     * Realiza la carga del documento desde un string XML.
     *
     * Construye el documento tributario electrónico a partir de los datos de
     * un string XML.
     *
     * Este método es para cargar XML de documentos completos. O sea, con todos
     * sus datos (normalizados), el documento timbrado (con su CAF asociado) y
     * con su firma electrónica.
     *
     * @param string $xml Datos del documento tributario.
     * @return DocumentBagInterface Contenedor con los datos del documento.
     * @throws LoaderException
     */
    public function loadXml(string $xml): DocumentBagInterface;
}
