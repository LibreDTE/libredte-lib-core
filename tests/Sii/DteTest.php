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

/**
 * Clase para tests de la clase \sasco\LibreDTE\Sii\Dte
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-05-26
 */
class Sii_DteTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test para verificar los ejemplos en JSON del directorio examples/json
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-05-26
     */
    public function testEjemplosJSON()
    {
        $dir = dirname(dirname(dirname(__FILE__))).'/examples/json';
        $casos =  json_decode(file_get_contents($dir.'/montos_esperados.json'), true);
        $this->assertNotNull($casos, 'No fue posible cargar el archivo montos_esperados.json');
        $dtes = scandir($dir);
        foreach ($dtes as $dte) {
            if (is_numeric($dte) and is_dir($dir.'/'.$dte)) {
                $jsons = scandir($dir.'/'.$dte);
                foreach ($jsons as $json) {
                    if (substr($json, -5)=='.json') {
                        $caso = substr($json, 0, -5);
                        $this->assertArrayHasKey($caso, $casos, 'No existen los valores esperados para el caso '.$caso);
                        $datos = json_decode(file_get_contents($dir.'/'.$dte.'/'.$json), true);
                        $this->assertNotNull($datos, 'No fue posible cargar los datos del caso '.$caso);
                        $Dte = new \sasco\LibreDTE\Sii\Dte($datos);
                        $resumen = $Dte->getResumen();
                        foreach ($casos[$caso] as $monto => $valor) {
                            $this->assertEquals($valor, $resumen[$monto], $monto.' no cuadra en el caso '.$caso);
                        }
                    }
                }
            }
        }
    }

}
