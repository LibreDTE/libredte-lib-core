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
 * @file 003-estadoDte.php
 * Ejemplo de consulta del estado de un DTE
 * Referencia: http://www.sii.cl/factura_electronica/factura_mercado/estado_dte.pdf
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// solicitar token
$token = \sasco\LibreDTE\Sii\Autenticacion::getToken($config['firma']);
if (!$token) {
    foreach (\sasco\LibreDTE\Log::readAll() as $error)
        echo $error,"\n";
    exit;
}

// consultar estado dte
$xml = \sasco\LibreDTE\Sii::request('QueryEstDte', 'getEstDte', [
    'RutConsultante'    => '',
    'DvConsultante'     => '',
    'RutCompania'       => '',
    'DvCompania'        => '',
    'RutReceptor'       => '',
    'DvReceptor'        => '',
    'TipoDte'           => '',
    'FolioDte'          => '',
    'FechaEmisionDte'   => '',
    'MontoDte'          => '',
    'token'             => $token,
]);

// si el estado se pudo recuperar se muestra
if ($xml!==false) {
    print_r((array)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR')[0]);
}

// si hubo errores se muestran
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
