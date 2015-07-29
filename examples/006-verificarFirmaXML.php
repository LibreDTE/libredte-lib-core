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
 * @file 006-verificarFirmaXML.php
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-07-29
 */

// respuesta en texto plano
header('Content-type: text/plain');

// importar clases
include_once dirname(dirname(__FILE__)).'/lib/FirmaElectronica.php';

// configuración
include 'config.php';

// verificar firma
$xml_data = file_get_contents('archivoFirmado.xml');
$FirmaElectronica = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
var_dump($FirmaElectronica->verifyXML($xml_data));
