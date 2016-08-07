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
 * @file 031-libro_boleta.php
 *
 * Ejemplo que genera el XML de LibroBoleta para boletas electrónicas
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-08-07
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// archivos
$boletas = 'xml/EnvioBOLETA.xml';

// cargar XML boletas y notas
$EnvioBOLETA = new \sasco\LibreDTE\Sii\EnvioDte();
$EnvioBOLETA->loadXML(file_get_contents($boletas));

// crear objeto para libro de boletas
$LibroBoleta = new \sasco\LibreDTE\Sii\LibroBoleta();
$LibroBoleta->setFirma(new \sasco\LibreDTE\FirmaElectronica($config['firma']));

// agregar detalle de boletas
foreach ($EnvioBOLETA->getDocumentos() as $Dte) {
    $r = $Dte->getResumen();
    $LibroBoleta->agregar([
        'TpoDoc' => $r['TpoDoc'],
        'FolioDoc' => $r['NroDoc'],
        //'Anulado' => in_array($r['NroDoc'], [1, 3, 5]) ? 'A' : false, // se anularon folios 1, 3 y 5
        'FchEmiDoc' => $r['FchDoc'],
        'RUTCliente' => $r['RUTDoc'],
        'MntExe' => $r['MntExe'] ? $r['MntExe'] : false,
        'MntTotal' => $r['MntTotal'],
    ]);
}

// crear carátula para el libro
$CaratulaEnvioBOLETA = $EnvioBOLETA->getCaratula();
$LibroBoleta->setCaratula([
    'RutEmisorLibro' => $CaratulaEnvioBOLETA['RutEmisor'],
    'FchResol' => $CaratulaEnvioBOLETA['FchResol'],
    'NroResol' => $CaratulaEnvioBOLETA['NroResol'],
    'FolioNotificacion' => 1,
]);

// generar, validar schema y mostrar XML
$LibroBoleta->generar();
if ($LibroBoleta->schemaValidate()) {
    echo $LibroBoleta->generar();
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
