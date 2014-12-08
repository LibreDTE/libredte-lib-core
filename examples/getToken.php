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
 * @file getToken.php
 * Ejemplo de obtención de token para autenticación automática en el SII
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2014-12-08
 */

// respuesta en texto plano
header('Content-type: text/plain');

// importar clases
include_once dirname(dirname(__FILE__)).'/lib/Sii/Autenticacion.php';

// configuración para la firma
$firma_config = [
    'file' => '/ruta/al/certificado.p12',
    'pass' => 'contraseña',
];

// solicitar token
$token = \sasco\LibreDTE\Sii_Autenticacion::getToken($firma_config);
var_dump($token);
