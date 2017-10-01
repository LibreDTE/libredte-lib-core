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
 * @file 036-getDTERecibidos.php
 * Ejemplo de obtención de datos archivo CSV con getDTERecibidos en SII segun rango de fechas
 * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
 * @version 2017-09-30
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// Rangos de fechas
$desde = '01-08-2017';
$hasta = '30-09-2017';

// solicitar datos
$datos = \sasco\LibreDTE\Sii::getDTERecibidos(
    new \sasco\LibreDTE\FirmaElectronica($config['firma']),
    \sasco\LibreDTE\Sii::PRODUCCION,
    '76397-9',   // Rut Contribuyente
    $desde,  // Fecha desde
    $hasta //Fecha Hasta
);

// si hubo errores se muestran
if (!$datos) {
    foreach (\sasco\LibreDTE\Log::readAll() as $error) {
        echo $error, "\n";
    }
    exit;
}

// descargar archivo como CSV
array_unshift($datos, ['Lin', 'Rut del Emisor', 'Razón Social Emisor', 'Tipo Dte', 'Folio Dte', 'Fecha Emisión', 'Monto Total', 'Fecha Recepción', 'Número Envío']);
\sasco\LibreDTE\CSV::generate($datos, 'dte_recibidos_' . str_replace('-', '', $desde) . '_' . str_replace('-', '', $hasta), ';', '');
