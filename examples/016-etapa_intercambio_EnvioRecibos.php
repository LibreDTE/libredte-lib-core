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
 * @file 016-etapa_intercambio_EnvioRecibos.php
 *
 * Ejemplo que genera el XML de recibos para un proveedor, el XML generado
 * deberá ser subido "a mano" a https://www4.sii.cl/pfeInternet
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// datos
$archivo_recibido = 'xml/intercambio/ENVIO_DTE_420328.xml';
$RutResponde = '76192083-9';
$RutFirma = '';

// Cargar EnvioDTE y extraer arreglo con datos de carátula y DTEs
$EnvioDte = new \sasco\LibreDTE\Sii\EnvioDte();
$EnvioDte->loadXML(file_get_contents($archivo_recibido));
$Caratula = $EnvioDte->getCaratula();
$Documentos = $EnvioDte->getDocumentos();

// caratula
$caratula = [
    'RutResponde' => $RutResponde,
    'RutRecibe' => $Caratula['RutEmisor'],
    //'NmbContacto' => '',
    //'MailContacto' => '',
];

// objeto EnvioRecibo, asignar carátula y Firma
$EnvioRecibos = new \sasco\LibreDTE\Sii\EnvioRecibos();
$EnvioRecibos->setCaratula($caratula);
$EnvioRecibos->setFirma(new \sasco\LibreDTE\FirmaElectronica($config['firma']));

// procesar cada DTE
foreach ($Documentos as $DTE) {
    $EnvioRecibos->agregar([
        'TipoDoc' => $DTE->getTipo(),
        'Folio' => $DTE->getFolio(),
        'FchEmis' => $DTE->getFechaEmision(),
        'RUTEmisor' => $DTE->getEmisor(),
        'RUTRecep' => $DTE->getReceptor(),
        'MntTotal' => $DTE->getMontoTotal(),
        'Recinto' => 'Oficina central',
        'RutFirma' => $RutFirma,
    ]);
}

// generar XML
$xml = $EnvioRecibos->generar();

// validar schema del XML que se generó
if ($EnvioRecibos->schemaValidate()) {
    // mostrar XML al usuario, deberá ser guardado y subido al SII en:
    // https://www4.sii.cl/pfeInternet
    echo $xml;
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
