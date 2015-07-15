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
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-07-14
 */

// respuesta en texto plano
header('Content-type: text/plain');

// importar clases
include_once dirname(dirname(__FILE__)).'/lib/Sii/Autenticacion.php';
include_once dirname(dirname(__FILE__)).'/lib/Sii/Dte.php';

// configuración
include 'config.php';

// solicitar token
$token = \sasco\LibreDTE\Sii_Autenticacion::getToken($config['firma']);
if (!$token)
    die('No fue posible obtener token');

// consultar estado dte
$query = [
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
];
$estado = \sasco\LibreDTE\Sii_Dte::estado($query, $token);

// si el estado no se pudo recuperar error
if (!$estado)
    die('No fue posible obtener estado');

// imprimir objeto del estado
print_r($estado);

// mostrar glosa sólo si no hay error con el DTE
if (!$estado->error->codigo)
    echo $estado->error->glosa;
