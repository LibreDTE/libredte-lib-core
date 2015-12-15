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
 * @file inc.php
 * Archivo que incluye todos los archivo .php de la biblioteca para evitar
 * incluirlos manualmente. Esto es sólo válido en los ejemplos, en código real
 * usar la autocarga de composer
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-15
 */

// activar todos los errores
ini_set('display_errors', true);
error_reporting(E_ALL);

// zona horaria
date_default_timezone_set('America/Santiago');

// incluir autocarga de composer
if (is_readable(dirname(dirname(__FILE__)).'/vendor/autoload.php'))
    include dirname(dirname(__FILE__)).'/vendor/autoload.php';
else
    die('Para probar los ejemplos debes ejecutar primero "composer install" en el directorio '.dirname(dirname(__FILE__)));

// incluir archivos de la biblioteca
/*$path = dirname(dirname(__FILE__)).'/lib';
$Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
$files = new RegexIterator($Iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
foreach ($files as $file => $object) {
    include $file;
}*/

// todos los ejemplos se ejecutan con backtrace activado, esto para ayudar al
// debug de los mismos
\sasco\LibreDTE\Log::setBacktrace(true);

// incluir configuración específica de los ejemplos
include 'config.php';
