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
 * @file inc.php
 * Archivo que incluye todos los archivo .php de la biblioteca para evitar
 * incluirlos manualmente. Esto es sólo válido en los ejemplos, en código real
 * usar la autocarga de composer
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-01-19
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
    die('Para probar los ejemplos debes ejecutar primero "composer install" en el directorio '.dirname(dirname(__FILE__))."\n");

// todos los ejemplos se ejecutan con backtrace activado, esto para ayudar al
// debug de los mismos
\sasco\LibreDTE\Log::setBacktrace(true);

// incluir configuración específica de los ejemplos
if (is_readable('config.php'))
    include 'config.php';
else
    die('Debes crear config.php a partir de config-dist.php'."\n");

