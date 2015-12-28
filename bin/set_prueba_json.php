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

// inicializar comando
include 'share/bootstrap.php';

/**
 * Comando para generar el JSON de un set de prueba
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-18
 */
class set_prueba_json extends Command
{

    protected $config = [
        'args' => [
            'set:' => [
                'description' => 'Archivo TXT con el set de pruebas (codificado en ISO8859-1)',
                'default' => false,
            ],
            'json:' => [
                'description' => 'Archivo JSON que se generará con el set de pruebas',
                'default' => false,
            ],
        ],
    ]; ///< Configuración del comando

    /**
     * Método que valida los argumentos pasados al comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-18
     */
    public function args_check()
    {
        // archivo
        if (!$this->args['set'] or !is_readable($this->args['set'])) {
            $this->error('Debe especificar archivo TXT de entrada válido');
        }
    }

    /**
     * Método principal del comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-18
     */
    public function main()
    {
        $json = \sasco\LibreDTE\Sii\Certificacion\SetPruebas::getJSON(
            file_get_contents($this->args['set'])
        );
        if (!empty($this->args['json']))
            file_put_contents($this->args['json'], $json);
        else
            echo $json;
        return 0;
    }

}

// lanzar comando
exit((new set_prueba_json())->main());
