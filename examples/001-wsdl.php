<?php

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
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
 * @file 001-wsdl.php
 * Ejemplo de obtención de WSDL según ambiente que se esté utilizando
 * @version 2016-08-28
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca
include 'inc.php';

// reset configuración (valor original biblioteca: producción)
\libredte\lib\Sii::setAmbiente();

// solicitar ambiente producción
echo \libredte\lib\Sii::wsdl('CrSeed'),"\n";

// solicitar ambiente desarrollo con parámetro
echo \libredte\lib\Sii::wsdl('CrSeed', \libredte\lib\Sii::CERTIFICACION),"\n";

// solicitar ambiente desarrollo con configuración
\libredte\lib\Sii::setAmbiente(\libredte\lib\Sii::CERTIFICACION);
echo \libredte\lib\Sii::wsdl('CrSeed'),"\n";
echo \libredte\lib\Sii::wsdl('GetTokenFromSeed'),"\n";

// a pesar de estar en ambiente de desarrollo (por la configuración antes
// definida) se puede forzar producción usando el segundo parámetro. Al estar
// definido el segundo parámetro no se considerará la existencia ni valor de la
// configuración
echo \libredte\lib\Sii::wsdl('CrSeed', \libredte\lib\Sii::PRODUCCION),"\n";
echo \libredte\lib\Sii::wsdl('GetTokenFromSeed', \libredte\lib\Sii::PRODUCCION),"\n";
