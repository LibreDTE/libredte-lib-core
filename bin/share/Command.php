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

/**
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-15
 */
abstract class Command
{

    protected $args; ///< Argumentos que se pasaron al comando

    /**
     * Constructor del comando: carga argumentos, muestra la ayuda si se
     * solicitó y valida los argumentos pasados
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-15
     */
    public function __construct()
    {
        $this->args_get();
        if (isset($this->args['h'])) {
            $this->usemode();
            exit;
        }
        $this->args_check();
    }

    /**
     * Método que recupera los argumentos pasados al comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-15
     */
    private function args_get()
    {
        $default = [];
        foreach ($this->config['args'] as $arg => $info) {
            if (isset($info['default']))
                $default[str_replace(':', '', $arg)] = $info['default'];
        }
        $this->args = array_merge(
            $default, getopt('h', array_keys($this->config['args']))
        );
    }

    /**
     * Método para imprimir dentro del comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-15
     */
    protected function out($msg, $n = 1)
    {
        echo $msg,str_repeat("\n", $n);
    }

    /**
     * Método para renderizar un error
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-15
     */
    protected function error($msg)
    {
        echo "\n",'[error] ',$msg,"\n\n";
        $this->usemode();
        exit;
    }

    /**
     * Método que muestra el modo de uso del comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-15
     */
    protected function usemode()
    {
        echo 'Opciones del comando:',"\n\n";
        foreach ($this->config['args'] as $arg => $info) {
            printf("\t--%s\n\t%s\n\n", str_replace(':', '', $arg), $info['description']);
        }
        echo "\n";
    }

    /**
     * Método que se debe implementar con las validaciones a los argumentos
     * que se pasan al comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-15
     */
    abstract function args_check();

}
