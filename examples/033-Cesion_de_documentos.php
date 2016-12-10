<?php

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');
// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';


// Objetos de Firma y Folios
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);

//Paso 1: Cedo el documento
$EnvioDTE = file_get_contents('xml'); // Archivo XML de envioDte.xml
$DteCedido = new \sasco\LibreDTE\Sii\DteCedido();
$DteCedido->loadEnvioDTE($EnvioDTE); // XML Envio DTE
$DteCedido->firmar($Firma);


//Paso 2: Completo la declaracion de Cesion y monto a cesionar
$DteCesion = new \sasco\LibreDTE\Sii\Cesion($DteCedido);
$DteCesion->setCesionario('76192083-9', 'SASCO SpA', 'Santiago', 'esteban@sasco.cl');
$DteCesion->setDeclaracion('55666777-8', 'Autor del documento', 'autor@sasco.cl');
$DteCesion->setMontos(10000);
$DteCesion->firmar($Firma);

//Paso 3: Creo AEC
$AECDoc = new \sasco\LibreDTE\Sii\DocumentoAEC();

$caratula = [
    'RutCedente' => '111-2',
    'RutCesionario' => '76192083-9',
    'MailContacto' => 'esteban@sasco.cl',
    'TmstFirmaEnvio' => date('Y-m-d\TH:i:s')
];

// Agrego DTE Cedido
$AECDoc->agregarDteCedido($DteCedido);

//Agrego declaracion de cesion
$AECDoc->agregarCesion($DteCesion);

$AECDoc->setFirma($Firma);
$AECDoc->setCaratula($caratula);

print_r($AECDoc->generar());


// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error, "\n";