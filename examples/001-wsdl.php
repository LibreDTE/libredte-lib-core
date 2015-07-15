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
 * @file wsdl.php
 * Ejemplo de obtención de WSDL según ambiente que se esté utilizando
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-07-14
 */

// respuesta en texto plano
header('Content-type: text/plain');

// importar clases
include_once dirname(dirname(__FILE__)).'/lib/Sii/Wsdl.php';

// solicitar ambiente producción
echo \sasco\LibreDTE\Sii_Wsdl::get('CrSeed'),"\n";

// solicitar ambiente desarrollo con parámetro
echo \sasco\LibreDTE\Sii_Wsdl::get('CrSeed', \sasco\LibreDTE\Sii_Wsdl::CERTIFICACION),"\n";

// solicitar ambiente desarrollo con constante
define('_LibreDTE_CERTIFICACION_', true);
echo \sasco\LibreDTE\Sii_Wsdl::get('CrSeed'),"\n";
echo \sasco\LibreDTE\Sii_Wsdl::get('GetTokenFromSeed'),"\n";

// a pesar de estar en ambiente de desarrollo (por la constante antes definida)
// se puede forzar producción usando el segundo parámetro. Al estar definido el
// segundo parámetro no se considerará la existencia ni valor de la constante
// _LibreDTE_CERTIFICACION_
echo \sasco\LibreDTE\Sii_Wsdl::get('CrSeed', \sasco\LibreDTE\Sii_Wsdl::PRODUCCION),"\n";
echo \sasco\LibreDTE\Sii_Wsdl::get('GetTokenFromSeed', \sasco\LibreDTE\Sii_Wsdl::PRODUCCION),"\n";
