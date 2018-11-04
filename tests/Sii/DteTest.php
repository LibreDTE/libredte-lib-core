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
 * @version 2017-08-16
 */
class Sii_DteTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Test para verificar los ejemplos en JSON del directorio examples/json
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-04-15
     */
    public function testEjemplosJSON()
    {
        $dir_json = dirname(dirname(dirname(__FILE__))).'/examples/json';
        // crear directorios para XML y PDF si no existen
        $dir_xml = dirname(dirname(dirname(__FILE__))).'/examples/xml';
        $dir_pdf = dirname(dirname(dirname(__FILE__))).'/examples/pdf';
        if (!file_exists($dir_xml))
            mkdir($dir_xml);
        if (!file_exists($dir_pdf))
            mkdir($dir_pdf);
        // cargar montos esperados
        $casos =  json_decode(file_get_contents($dir_json.'/montos_esperados.json'), true);
        $this->assertNotNull($casos, 'No fue posible cargar el archivo montos_esperados.json');
        // cargar y procesar cada caso en JSON
        $dtes = scandir($dir_json);
        foreach ($dtes as $dte) {
            if (is_numeric($dte) and is_dir($dir_json.'/'.$dte)) {
                $jsons = scandir($dir_json.'/'.$dte);
                foreach ($jsons as $json) {
                    if (substr($json, -5)=='.json') {
                        // cargar caso
                        $caso = substr($json, 0, -5);
                        $this->assertArrayHasKey($caso, $casos, 'No existen los valores esperados para el caso '.$caso);
                        $sin_normalizar = json_decode(file_get_contents($dir_json.'/'.$dte.'/'.$json), true);
                        $this->assertNotNull($sin_normalizar, 'No fue posible cargar los datos del caso '.$caso);
                        $Dte = new \sasco\LibreDTE\Sii\Dte($sin_normalizar);
                        // probar valores de totales del caso
                        $totales = $Dte->getDatos()['Encabezado']['Totales'];
                        foreach ($casos[$caso] as $monto => $valor) {
                            $this->assertArrayHasKey($monto, $totales, 'No existe el total para '.$monto.' en el caso '.$caso);
                            if (!is_array($valor)) {
                                $this->assertEquals($valor, $totales[$monto], $monto.' no cuadra en el caso '.$caso);
                            } else {
                                if (!isset($valor[0]))
                                    $valor = [$valor];
                                foreach ($valor as $valores) {
                                    $this->assertContains($valores, $totales[$monto], 'Datos de '.$monto.' no son los esperados para el caso '.$caso);
                                }
                            }
                        }
                        // guardar XML del caso
                        file_put_contents($dir_xml.'/'.$caso.'.xml', $Dte->saveXML());
                        // guardar PDF del caso
                        $pdf = new \sasco\LibreDTE\Sii\Dte\PDF\Dte();
                        $pdf->setResolucion(['FchResol'=>date('Y-m-d'), 'NroResol'=>0]);
                        $pdf->setFooterText();
                        $pdf->agregar($Dte->getDatos(), $Dte->getTED());
                        $pdf->Output($dir_pdf.'/'.$caso.'.pdf', 'F');
                    }
                }
            }
        }
    }

}
