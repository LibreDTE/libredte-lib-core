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
 * Clase para tests de la clase \sasco\LibreDTE\Log
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-08-16
 */
class LogTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Se verifica que lo que se escriba al log se pueda leer todo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-29
     */
    public function testWriteReadAll()
    {
        // log que se probará
        $logs = [
            LOG_ERR => [
                'Error N° 1',
                'Ejemplo error dos',
                'Este es el tercer error',
            ],
            LOG_WARNING => [
                'Este es el primer warning',
                'Un segundo warning',
                'El penúltimo warning',
                'El warning final (4to)'
            ],
        ];
        // se verificará leyendo el log en ambos ordenes (mas nuevo a mas viejo
        // y más viejo a más nuevo)
        foreach ([true, false] as $new_first) {
            // escribir al log
            foreach ($logs as $severity => $mensajes) {
                foreach ($mensajes as $codigo => $mensaje) {
                    \sasco\LibreDTE\Log::write($codigo, $mensaje, $severity);
                }
            }
            // revisar lo que se escribió al log
            foreach ($logs as $severity => $mensajes) {
                $registros = \sasco\LibreDTE\Log::readAll($severity, $new_first);
                $this->assertNotEmpty($registros);
                $this->assertCount(count($logs[$severity]), $registros);
                if ($new_first)
                    krsort($mensajes);
                foreach ($mensajes as $codigo => $mensaje) {
                    $Log = array_shift($registros);
                    $this->assertEquals($codigo, $Log->code);
                    $this->assertEquals($mensaje, $Log->msg);
                }
            }
        }
    }

}
