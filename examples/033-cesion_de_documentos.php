<?php

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
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
 * @file 033-cesion_de_documentos.php
 *
 * Ejemplo para cesión de documentos electrónicos (factoring).
 * @version 2016-12-10
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// archivo XML de EnvioDTE con el DTE que se cederá
$archivo = 'xml/factura.xml';

// objeto de firma electrónica
$Firma = new \libredte\lib\FirmaElectronica($config['firma']);

// cargar EnvioDTE y extraer DTE a ceder
$EnvioDte = new \libredte\lib\Sii\EnvioDte();
$EnvioDte->loadXML(file_get_contents($archivo));
$Dte = $EnvioDte->getDocumentos()[0];

// armar el DTE cedido
$DteCedido = new \libredte\lib\Sii\Factoring\DteCedido($Dte);
$DteCedido->firmar($Firma);

// crear declaración de cesión y monto a cesionar
$Cesion = new \libredte\lib\Sii\Factoring\Cesion($DteCedido);
$Cesion->setCesionario([
    'RUT' => '55666777-8',
    'RazonSocial' => 'Empresa de Factoring SpA',
    'Direccion' => 'Santiago',
    'eMail' => 'cesionario@example.com',
]);
$Cesion->setCedente([
    'eMail' => 'cedente@example.com',
    'RUTAutorizado' => [
        'RUT' => $Firma->getID(),
        'Nombre' => $Firma->getName(),
    ],
]);
$Cesion->firmar($Firma);

// crear AEC
$AEC = new \libredte\lib\Sii\Factoring\Aec();
$AEC->setFirma($Firma);
$AEC->agregarDteCedido($DteCedido);
$AEC->agregarCesion($Cesion);

// generar XML del archivo electrónico de cesión
echo $AEC->generar();

// enviar archivo electrónico de cesión al SII
//echo $AEC->enviar();

// si hubo errores mostrar
foreach (\libredte\lib\Log::readAll() as $error)
    echo $error, "\n";
