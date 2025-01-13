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
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentBagManagerException;

/**
 * Interfaz para que administra una bolsa con los datos de un documento
 * tributario.
 */
interface DocumentBagManagerWorkerInterface extends WorkerInterface
{
    /**
     * Crea la bolsa con los datos del documento a partir de datos de origen.
     *
     * Se pueden pasar diferentes tipos de datos de origen:
     *
     *   - `string`: Datos de entrada para ser parseados y normalizados. Debe
     *     empezar con el prefijo `parser.strategy.xyz:` para que los datos sean
     *     parseados.
     *   - `array`: Datos ya normalizados.
     *   - `XmlInterface`: Una instancia del documento XML con sus datos
     *     normalizado.
     *   - `DocumentInterface`: Una instancia del documento tributario con sus
     *     datos normalizados.
     *
     * @param string|array|XmlInterface|DocumentInterface $source Datos de origen.
     * @param bool $normalizeAll Indica si se deben normalizar todos los datos
     * de la bolsa al crearla o solo los mínimos.
     * @return DocumentBagInterface
     * @throws DocumentBagManagerException
     */
    public function create(
        string|array|XmlInterface|DocumentInterface $source,
        bool $normalizeAll = true
    ): DocumentBagInterface;

    /**
     * Normaliza una bolsa con datos de un documento tributario.
     *
     * Se completará el contenido que falte con lo que se pueda completar según
     * sea el contenido de la bolsa.
     *
     * @param DocumentBagInterface $bag
     * @param bool $all Indica si se deben aplicar todas las normalizaciones o
     * solo la de la asignación del tipo de documento.
     * @return DocumentBagInterface
     */
    public function normalize(
        DocumentBagInterface $bag,
        bool $all = false
    ): DocumentBagInterface;
}
