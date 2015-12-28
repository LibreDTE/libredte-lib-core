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
 * @file 018-generar_pdf_num.php
 *
 * Ejemplo que prueba los formatos de los numeros
 *
 * @author David Mancilla (dmancilla[at]gmail.com)
 * @version 2015-12-28
 */

// respuesta en texto plano
header('Content-type: text/html; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// sin límite de tiempo para generar documentos
set_time_limit(0);

// procesar cada DTEs e ir agregándolo al PDF
$pdf = new \sasco\LibreDTE\Sii\PDF\Dte(false); // =false hoja carta, =true papel contínuo (false por defecto si no se pasa)

function assertIgual($i, $t1, $t2){
    if ($t1 === $t2) {
        echo "<p><span style='color:#009900;'>Iguales: </span><span>$t1 == $t2</span></p>";
    } else {
        echo "<span style='color:#990000;'>$i: Deberia ser: $t1 pero es $t2</span>";
    }
}

assertIgual( 1,         "0", $pdf->num("0"));
assertIgual( 2,         "1", $pdf->num("1"));
assertIgual( 3,        "10", $pdf->num("10"));
assertIgual( 4,       "100", $pdf->num("100"));
assertIgual( 5,     "1.000", $pdf->num("1000"));
assertIgual( 6,    "10.000", $pdf->num("10000"));
assertIgual( 7,   "100.000", $pdf->num("100000"));
assertIgual( 8, "1.000.000", $pdf->num("1000000"));

assertIgual( 9, "1.000.000", $pdf->num("1000000,0"));
assertIgual(10, "1.000.000", $pdf->num("1000000,00"));
assertIgual(11, "1.000.000", $pdf->num("1000000,000"));

assertIgual(12, "1.000.000,1", $pdf->num("1000000,1"));
assertIgual(13, "1.000.000,1", $pdf->num("1000000,10"));
assertIgual(14, "1.000.000,1", $pdf->num("1000000,100"));

assertIgual(15, "1.000.000,9", $pdf->num("1000000,9"));
assertIgual(16, "1.000.000,9", $pdf->num("1000000,90"));
assertIgual(17, "1.000.000,9", $pdf->num("1000000,900"));

assertIgual(18, "1.000.000,99", $pdf->num("1000000,99"));
assertIgual(19, "1.000.000,99", $pdf->num("1000000,990"));
assertIgual(20, "1.000.000,99", $pdf->num("1000000,9900"));

assertIgual(21, "1.000.000,999", $pdf->num("1000000,999"));
assertIgual(22, "1.000.000,999", $pdf->num("1000000,9990"));
assertIgual(23, "1.000.000,999", $pdf->num("1000000,99900"));

?>