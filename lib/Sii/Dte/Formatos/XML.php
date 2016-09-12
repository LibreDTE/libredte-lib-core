<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sasco\LibreDTE\Sii\Dte\Formatos;

/**
 * Clase que permite cargar los datos de un DTE desde un archivo en formato XML
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-09-12
 */
class XML
{

    /**
     * Método que recibe los datos y los entrega como un arreglo PHP en el
     * formato del DTE que usa LibreDTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-09-12
     */
    public static function toArray($data)
    {
        $XML = new \sasco\LibreDTE\XML();
        if (!$XML->loadXML($data)) {
            throw new \Exception('Ocurrió un problema al cargar el XML');
        }
        $datos = $XML->toArray();
        if (!isset($datos['DTE'])) {
            throw new \Exception('El nodo raíz del string XML debe ser el tag DTE');
        }
        if (isset($datos['DTE']['Documento']))
            $dte = $datos['DTE']['Documento'];
        else if (isset($datos['DTE']['Exportaciones']))
            $dte = $datos['DTE']['Exportaciones'];
        else if (isset($datos['DTE']['Liquidacion']))
            $dte = $datos['DTE']['Liquidacion'];
        unset($dte['@attributes']);
        return $dte;
    }

}
