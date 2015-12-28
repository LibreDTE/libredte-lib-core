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
 * @file 030-boleta_consumo_folio.php
 *
 * Ejemplo que genera el XML de ConsumoFolio para el reporte de las boletas
 * electrónicas y notas de crédito electrónicas del set de prueba de boletas
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-14
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// archivos
$boletas = 'xml/EnvioBOLETA.xml';
$notas_credito = 'xml/EnvioDTE.xml';

// cargar XML boletas y notas
$EnvioBOLETA = new \sasco\LibreDTE\Sii\EnvioDte();
$EnvioBOLETA->loadXML(file_get_contents($boletas));
$EnvioDTE = new \sasco\LibreDTE\Sii\EnvioDte();
$EnvioDTE->loadXML(file_get_contents($notas_credito));

// crear objeto para consumo de folios
$ConsumoFolio = new \sasco\LibreDTE\Sii\ConsumoFolio();
$ConsumoFolio->setFirma(new \sasco\LibreDTE\FirmaElectronica($config['firma']));

// agregar detalle de boletas
foreach ($EnvioBOLETA->getDocumentos() as $Dte) {
    $ConsumoFolio->agregar($Dte->getResumen());
}

// agregar detalle de notas de crédito
foreach ($EnvioDTE->getDocumentos() as $Dte) {
    $ConsumoFolio->agregar($Dte->getResumen());
}

// crear carátula para el envío (se hace después de agregar los detalles ya que
// así se obtiene automáticamente la fecha inicial y final de los documentos)
$CaratulaEnvioBOLETA = $EnvioBOLETA->getCaratula();
$ConsumoFolio->setCaratula([
    'RutEmisor' => $CaratulaEnvioBOLETA['RutEmisor'],
    'FchResol' => $CaratulaEnvioBOLETA['FchResol'],
    'NroResol' => $CaratulaEnvioBOLETA['NroResol'],
]);

// generar, validar schema y mostrar XML
$ConsumoFolio->generar();
if ($ConsumoFolio->schemaValidate()) {
    //echo $ConsumoFolio->generar();
    $track_id = $ConsumoFolio->enviar();
    var_dump($track_id);
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
