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
 * @file 026-libro_guia.php
 *
 * Ejemplo que genera y envía el libro electrónico de guías de despachos.
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-14
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// caratula del libro
$caratula = [
    'RutEmisorLibro' => '76192083-9',
    'FchResol' => '2014-12-05',
    'NroResol' => 0,
    'FolioNotificacion' => 1,
];

// receptor de las guías
$receptor = '55666777-8';

// set de pruebas guías - número de atención 414177
$detalles = [
    // CASO 1
    [
        'Folio' => 4,
        'TpoOper' => 5,
        'RUTDoc' => $caratula['RutEmisorLibro'],
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
    ],
    // CASO 2 CORRESPONDE A UNA GUIA QUE SE FACTURO EN EL PERIODO
    [
        'Folio' => 5,
        'TpoOper' => 1,
        'RUTDoc' => $receptor,
        'MntNeto' => 1375761,
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
        'TpoDocRef' => 33,
        'FolioDocRef' => 69,
        'FchDocRef' => date('Y-m-d'),
    ],
    // CASO 3 CORRESPONDE A UNA GUIA ANULADA
    [
        'Folio' => 6,
        'Anulado' => 2,
        'TpoOper' => 1,
        'RUTDoc' => $receptor,
        'MntNeto' => 1050032,
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
    ],
];

// Objetos de Firma y LibroGuia
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$LibroGuia = new \sasco\LibreDTE\Sii\LibroGuia();

// agregar cada uno de los detalles al libro
foreach ($detalles as $detalle) {
    $LibroGuia->agregar($detalle);
}

// enviar libro de guías y mostrar resultado del envío: track id o bien =false si hubo error
$LibroGuia->setFirma($Firma);
$LibroGuia->setCaratula($caratula);
$LibroGuia->generar();
if ($LibroGuia->schemaValidate()) {
    //echo $LibroGuia->generar();
    $track_id = $LibroGuia->enviar();
    var_dump($track_id);
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
