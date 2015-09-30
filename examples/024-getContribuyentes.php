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
 * @file 024-getContribuyentes.php
 * Ejemplo de obtención de datos archivo CSV con getContribuyentes autorizados
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-30
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// solicitar datos
$datos = \sasco\LibreDTE\Sii::getContribuyentes(
    new \sasco\LibreDTE\FirmaElectronica($config['firma']),
    \sasco\LibreDTE\Sii::PRODUCCION
);

// si hubo errores se muestran
if (!$datos) {
    foreach (\sasco\LibreDTE\Log::readAll() as $error) {
        echo $error,"\n";
    }
    exit;
}

// descargar archivo como CSV
array_unshift($datos, ['RUT', 'Razón social', 'Número resolución', 'Fecha resolución', 'Email intercambio', 'URL']);
\sasco\LibreDTE\CSV::generate($datos, 'contribuyentes', ';', '');
