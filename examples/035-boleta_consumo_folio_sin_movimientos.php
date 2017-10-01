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


// crear objeto para consumo de folios
$ConsumoFolio = new \sasco\LibreDTE\Sii\ConsumoFolio();
$ConsumoFolio->setFirma(new \sasco\LibreDTE\FirmaElectronica($config['firma']));

//Genero Caratula
$ConsumoFolio->setCaratula([
    'RutEmisor' => '76192083-9',
    'FchResol' => '2014-12-05',
    'NroResol' => 0
]);

// Resumen sin movimiento
$Resumen = [
    'TipoDocumento' => 39,
    'MntTotal' => 0,
    'FoliosEmitidos' => 0,
    'FoliosAnulados' => 0,
    'FoliosUtilizados' => 0
];

// Agrego resumen manual
$ConsumoFolio->setResumen($Resumen);


// generar, validar schema y mostrar XML
$ConsumoFolio->generar();
if ($ConsumoFolio->schemaValidate()) {
    //echo $ConsumoFolio->generar();
    $track_id = $ConsumoFolio->enviar();
    var_dump($track_id);
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error, "\n";
