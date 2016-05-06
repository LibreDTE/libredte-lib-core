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
 * @version 2016-05-05
 */
class Sii_DteTest extends PHPUnit_Framework_TestCase
{

    private $casos = [
        'BoletaAfecta_DescuentoGlobal' => [
            'MntNeto' => 900,
            'MntIVA' => 171,
            'MntTotal' => 1071,
        ],
        'BoletaExenta_DescuentoGlobal' => [
            'MntExe' => 900,
            'MntTotal' => 900,
        ],
    ]; ///< Casos con los documentos que se desean probar

    /**
     * Boleta con descuento global
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-05-05
     */
    public function testCasos()
    {
        foreach ($this->casos as $caso => $esperado) {
            $json = dirname(dirname(__FILE__)).'/json/'.$caso.'.json';
            $Dte = new \sasco\LibreDTE\Sii\Dte(json_decode(file_get_contents($json), true));
            $resumen = $Dte->getResumen();
            foreach ($esperado as $monto => $valor) {
                $this->assertEquals($valor, $resumen[$monto]);
            }
        }
    }

}
