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
 * @file 004-envioDte.php
 * Ejemplo de envío de un XML de un DTE ya timbrado y firmado al SII
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// datos del envío
$xml = file_get_contents('dte_33.xml');
$RutEnvia = '99888777-6';
$RutEmisor = '55444333-2';

// solicitar token
$token = \sasco\LibreDTE\Sii\Autenticacion::getToken($config['firma']);
if (!$token) {
    foreach (\sasco\LibreDTE\Log::readAll() as $error)
        echo $error,"\n";
    exit;
}

// enviar DTE
$result = \sasco\LibreDTE\Sii::enviar($RutEnvia, $RutEmisor, $xml, $token);

// si hubo algún error al enviar al servidor mostrar
if ($result===false) {
    foreach (\sasco\LibreDTE\Log::readAll() as $error)
        echo $error,"\n";
    exit;
}

// Mostrar resultado del envío
if ($result->STATUS!='0') {
    foreach (\sasco\LibreDTE\Log::readAll() as $error)
        echo $error,"\n";
    exit;
}
echo 'DTE envíado. Track ID '.$result->TRACKID,"\n";
