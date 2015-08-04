<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sasco\LibreDTE;

/**
 * Clase para cargar un archivo XML
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-08-03
 */
class XML
{

    /**
     * Método que carga y reempleza variables en un archivo XML
     * @param xml Nombre del XML que se desea obtener
     * @param vars Arreglo con variables que se desean pasar al archivo XML
     * @return Archivo XML con las variables reemplazadas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-03
     */
    public static function get($xml, $variables = [])
    {
        $file = dirname(dirname(__FILE__)).'/xml/'.$xml.'.xml';
        if (!is_readable($file))
            return false;
        $data = file_get_contents($file);
        foreach ($variables as $key => $valor) {
            $data = self::replace($key, $valor, $data);
        }
        return $data;
    }

    /**
     * Método que realiza el reemplazo de las variables en el XML, lo hace forma
     * recursiva en caso que existen arreglos como valores
     * @param key Clave que se desea reemplazar
     * @param valor Valor que se debe utilizar o arreglo para hacerlo de forma recursiva
     * @param data Datos del archivo XML que se desean reemplazar
     * @return Archivo XML con las variables reemplazadas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-04
     */
    private static function replace($key, $valor, $data)
    {
        if (is_array($valor)) {
            foreach ($valor as $key2 => $valor2) {
                $data = self::replace($key.'_'.$key2, $valor2, $data);
            }
            return $data;
        } else {
            return str_replace('{'.$key.'}', utf8_decode($valor), $data);
        }
    }

}
