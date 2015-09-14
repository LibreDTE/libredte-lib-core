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
 * Clase para manejar mensajes generados en la aplicación de forma "silenciosa"
 * y luego poder recuperarlos para procesar en la aplicación.
 *
 * Los mensajes estarán disponibles sólo durante la ejecución del script PHP,
 * una vez termina los mensajes se pierden, por eso es importante recuperarlos
 * antes que termine la ejecución de la página si se desea hacer algo con ellos.
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-14
 */
class Log
{

    private static $bitacora = []; ///< Bitácora con todos los tipos de tipos de mensajes, cada tipo es un arreglo de mensajes

    /**
     * Método que escribe un mensaje en la bitácora
     * @param msg Mensaje que se desea escribir
     * @param severity Gravedad del mensaje, por defecto LOG_ERR (puede ser cualquiera de las constantes PHP de syslog)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-14
     */
    public static function write($msg, $severity = LOG_ERR)
    {
        if (!isset(self::$bitacora[$severity]))
            self::$bitacora[$severity] = [];
        array_push(self::$bitacora[$severity], $msg);
    }

    /**
     * Método que recupera un mensaje de la bitácora y lo borra de la misma
     * @param severity Gravedad del mensaje, por defecto LOG_ERR (puede ser cualquiera de las constantes PHP de syslog)
     * @return Mensaje de la bitácora
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-14
     */
    public static function read($severity = LOG_ERR)
    {
        if (!isset(self::$bitacora[$severity]))
            return false;
        return array_pop(self::$bitacora[$severity]);
    }

    /**
     * Método que recupera todos los mensajes de la bitácora y los borra de la misma
     * @param severity Gravedad del mensaje, por defecto LOG_ERR (puede ser cualquiera de las constantes PHP de syslog)
     * @param new_first =true ordenará los mensajes de la bitácora en orden descendente
     * @return Arreglo con toos los mensaje de la bitácora
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-14
     */
    public static function readAll($severity = LOG_ERR, $new_first = true)
    {
        if (!isset(self::$bitacora[$severity]))
            return [];
        $bitacora = self::$bitacora[$severity];
        if ($new_first)
            krsort($bitacora);
        self::$bitacora[$severity] = [];
        return $bitacora;
    }

}
