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
 * @file 015-etapa_intercambio_RecepcionDTE.php
 *
 * Ejemplo que genera el XML de respuesta a la recepción de un DTE, el XML
 * generado deberá ser subido "a mano" a https://www4.sii.cl/pfeInternet
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// datos para validar
$archivo_recibido = 'xml/intercambio/ENVIO_DTE_420328.xml';
$RutReceptor_esperado = '76192083-9';
$RutEmisor_esperado = '88888888-8';

// Cargar EnvioDTE y extraer arreglo con datos de carátula y DTEs
$EnvioDte = new \sasco\LibreDTE\Sii\EnvioDte();
$EnvioDte->loadXML(file_get_contents($archivo_recibido));
$Caratula = $EnvioDte->getCaratula();
$Documentos = $EnvioDte->getDocumentos();

// caratula
$caratula = [
    'RutResponde' => $RutReceptor_esperado,
    'RutRecibe' => $Caratula['RutEmisor'],
    'IdRespuesta' => 1,
    //'NmbContacto' => '',
    //'MailContacto' => '',
];

// procesar cada DTE
$RecepcionDTE = [];
foreach ($Documentos as $DTE) {
    $estado = $DTE->getEstadoValidacion(['RUTEmisor'=>$RutEmisor_esperado, 'RUTRecep'=>$RutReceptor_esperado]);
    $RecepcionDTE[] = [
        'TipoDTE' => $DTE->getTipo(),
        'Folio' => $DTE->getFolio(),
        'FchEmis' => $DTE->getFechaEmision(),
        'RUTEmisor' => $DTE->getEmisor(),
        'RUTRecep' => $DTE->getReceptor(),
        'MntTotal' => $DTE->getMontoTotal(),
        'EstadoRecepDTE' => $estado,
        'RecepDTEGlosa' => \sasco\LibreDTE\Sii\RespuestaEnvio::$estados['documento'][$estado],
    ];
}

// armar respuesta de envío
$estado = $EnvioDte->getEstadoValidacion(['RutReceptor'=>$RutReceptor_esperado]);
$RespuestaEnvio = new \sasco\LibreDTE\Sii\RespuestaEnvio();
$RespuestaEnvio->agregarRespuestaEnvio([
    'NmbEnvio' => basename($archivo_recibido),
    'CodEnvio' => 1,
    'EnvioDTEID' => $EnvioDte->getID(),
    'Digest' => $EnvioDte->getDigest(),
    'RutEmisor' => $EnvioDte->getEmisor(),
    'RutReceptor' => $EnvioDte->getReceptor(),
    'EstadoRecepEnv' => $estado,
    'RecepEnvGlosa' => \sasco\LibreDTE\Sii\RespuestaEnvio::$estados['envio'][$estado],
    'NroDTE' => count($RecepcionDTE),
    'RecepcionDTE' => $RecepcionDTE,
]);

// asignar carátula y Firma
$RespuestaEnvio->setCaratula($caratula);
$RespuestaEnvio->setFirma(new \sasco\LibreDTE\FirmaElectronica($config['firma']));

// generar XML
$xml = $RespuestaEnvio->generar();

// validar schema del XML que se generó
if ($RespuestaEnvio->schemaValidate()) {
    // mostrar XML al usuario, deberá ser guardado y subido al SII en:
    // https://www4.sii.cl/pfeInternet
    echo $xml;
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
