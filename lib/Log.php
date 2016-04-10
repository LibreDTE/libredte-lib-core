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
 * @version 2015-09-15
 */
class Log
{

    private static $bitacora = []; ///< Bitácora con todos los tipos de tipos de mensajes, cada tipo es un arreglo de mensajes
    private static $backtrace = false; ///< Define si se usa o no backtrace

    /**
     * Método que permite activa/desactivar el backtrace para los mensajes que
     * se escribirán en la bitácora
     * Esto se permite ya que recuperar el backtrace consume memoria y dichos
     * detalles podrían no ser necesarios en el ambiente de producción de la
     * aplicación (por defecto el backtrace esta desactivado)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-15
     */
    public static function setBacktrace($backtrace = true)
    {
        self::$backtrace = $backtrace;
    }

    /**
     * Método que escribe un mensaje en la bitácora
     * @param code Código del mensaje que se desea escribir
     * @param msg Mensaje que se desea escribir
     * @param severity Gravedad del mensaje, por defecto LOG_ERR (puede ser cualquiera de las constantes PHP de syslog)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-16
     */
    public static function write($code, $msg = null, $severity = LOG_ERR)
    {
        // si no existe la bitácora para la gravedad se crea
        if (!isset(self::$bitacora[$severity]))
            self::$bitacora[$severity] = [];
        // si el código es un string se copia a msg
        if (is_string($code)) {
            $msg = $code;
            $code = -1; // código de error genérico
        }
        // crear mensaje
        $LogMsg = new LogMsg($code, $msg);
        // agregar datos de quien llamó al método
        if (self::$backtrace) {
            $trace = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT and !DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $LogMsg->file = $trace[0]['file'];
            $LogMsg->line = $trace[0]['line'];
            $LogMsg->function = $trace[1]['function'];
            $LogMsg->class = $trace[1]['class'];
            $LogMsg->type = $trace[1]['type'];
            $LogMsg->args = $trace[1]['args'];
        }
        // agregar mensaje a la bitácora
        array_push(self::$bitacora[$severity], $LogMsg);
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


/**
 * Clase que representa un mensaje del Log
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */
class LogMsg
{

    public $code; ///< Código del error
    public $msg; ///< Descripción o glosa del error
    public $file; ///< Archivo donde se llamó al log
    public $line; ///< Línea del archivo donde se llamó al log
    public $function; ///< Método que llamó al log
    public $class; ///< Clase del método que llamó al log
    public $type; ///< Tipo de llamada (estática o de objeto instanciado)
    public $args; ///< Argumntos que recibió el método que generó el log

    /**
     * Constructor del mensaje
     * @param code Código del error
     * @param msg Mensaje del error
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-16
     */
    public function __construct($code, $msg = null)
    {
        $this->code = (int)$code;
        $this->msg = $msg;
    }

    /**
     * Método mágico para obtener el mensaje como string a partir del objeto
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-16
     */
    public function __toString()
    {
        $msg = $this->msg ? $this->msg : 'Error código '.$this->code;
        if (!$this->file)
            return $msg;
        return $msg.' (by '.$this->class.$this->type.$this->function.'() in '.$this->file.' on line '.$this->line.')';
    }

}
