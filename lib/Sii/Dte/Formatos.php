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

namespace sasco\LibreDTE\Sii\Dte;

/**
 * Clase para convertir entre formatos soportados oficialmente por LibreDTE para
 * los DTE
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-09-12
 */
class Formatos
{

    private static $formatos = []; ///< Formatos oficialmente soportados (para los que existe un parser)

    /**
     * Método que convierte los datos en el formato de entrada a un arreglo PHP
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-09-12
     */
    public static function toArray($formato, $datos)
    {
        $class = '\sasco\LibreDTE\Sii\Dte\Formatos\\'.str_replace('.', '\\', $formato);
        if (!class_exists($class)) {
            $class = '\sasco\LibreDTE\Sii\Dte\Formatos\\'.str_replace('.', '\\', strtoupper($formato));
            if (!class_exists($class)) {
                $class = '\sasco\LibreDTE\Sii\Dte\Formatos\\'.str_replace('.', '\\', strtolower($formato));
                if (!class_exists($class)) {
                    throw new \Exception('Formato '.$formato.' no es válido como entrada para datos del DTE');
                }
            }
        }
        return $class::toArray($datos);
    }

    /**
     * Método que convierte los datos en el formato de entrada al formato
     * oficial en JSON
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-01-19
     */
    public static function toJSON($formato, $datos)
    {
        return json_encode(self::toArray($formato, $datos), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Método que obtiene el listado de formatos soportados (para los que existe
     * un parser)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-09-12
     */
    public static function getFormatos()
    {
        if (!self::$formatos) {
            $dir = dirname(__FILE__).'/Formatos';
            $formatos = scandir($dir);
            foreach($formatos as &$formato) {
                if ($formato[0]=='.')
                    continue;
                if (is_dir($dir.'/'.$formato)) {
                    $subformatos = scandir($dir.'/'.$formato);
                    foreach($subformatos as &$subformato) {
                        if ($subformato[0]=='.')
                            continue;
                        self::$formatos[] = $formato.'.'.substr($subformato, 0, -4);
                    }
                } else {
                    self::$formatos[] = substr($formato, 0, -4);
                }
            }
        }
        return self::$formatos;
    }

}
