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
 * @file 001-wsdl.php
 * Ejemplo de obtención de WSDL según ambiente que se esté utilizando
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-08-31
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca
include 'inc.php';

// si esta definido el ambiente en config.php avisar
if (defined('_LibreDTE_CERTIFICACION_'))
    die('Comentar _LibreDTE_CERTIFICACION_ en config.php para probar este ejemplo');

// solicitar ambiente producción
echo \sasco\LibreDTE\Sii::wsdl('CrSeed'),"\n";

// solicitar ambiente desarrollo con parámetro
echo \sasco\LibreDTE\Sii::wsdl('CrSeed', \sasco\LibreDTE\Sii::CERTIFICACION),"\n";

// solicitar ambiente desarrollo con constante
define('_LibreDTE_CERTIFICACION_', true);
echo \sasco\LibreDTE\Sii::wsdl('CrSeed'),"\n";
echo \sasco\LibreDTE\Sii::wsdl('GetTokenFromSeed'),"\n";

// a pesar de estar en ambiente de desarrollo (por la constante antes definida)
// se puede forzar producción usando el segundo parámetro. Al estar definido el
// segundo parámetro no se considerará la existencia ni valor de la constante
// _LibreDTE_CERTIFICACION_
echo \sasco\LibreDTE\Sii::wsdl('CrSeed', \sasco\LibreDTE\Sii::PRODUCCION),"\n";
echo \sasco\LibreDTE\Sii::wsdl('GetTokenFromSeed', \sasco\LibreDTE\Sii::PRODUCCION),"\n";
