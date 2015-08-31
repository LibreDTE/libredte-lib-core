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
 * @file 005-estado_envio_dte.php
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-08-19
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// solicitar token
$token = \sasco\LibreDTE\Sii\Autenticacion::getToken($config['firma']);
if (!$token)
    die('No fue posible obtener token');

// consultar estado enviado
$rut = '';
$dv = '';
$trackID = '';
$estado = \sasco\LibreDTE\Sii::request('QueryEstUp', 'getEstUp', [$rut, $dv, $trackID, $token]);

// si el estado no se pudo recuperar error
if ($estado===false)
    die('No fue posible obtener estado del DTE envíado');

// mostrar estado y glosa
print_r([
    'codigo' => (string)$estado->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0],
    'glosa' => (string)$estado->xpath('/SII:RESPUESTA/SII:RESP_HDR/GLOSA')[0],
]);
