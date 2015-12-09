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
 * @file 027-ventas_sin_movimientos.php
 *
 * Ejemplo que genera y envía el archivo de Información Electrónica de Ventas
 * (IEV) para un período sin movimientos
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-08
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// caratula del libro
$caratula = [
    'RutEmisorLibro' => '76192083-9',
    //'RutEnvia' => '11222333-4', // si se omite se obtiene de la Firma
    'PeriodoTributario' => '2015-10',
    'FchResol' => '2014-08-22',
    'NroResol' => 80,
    'TipoOperacion' => 'VENTA',
    'TipoLibro' => 'MENSUAL',
    'TipoEnvio' => 'TOTAL',
];

// datos del emisor
$Emisor = [
    'RUTEmisor' => '76192083-9',
    'RznSoc' => 'SASCO SpA',
    'GiroEmis' => 'Servicios integrales de informática',
    'Acteco' => 726000,
    'DirOrigen' => 'Santiago',
    'CmnaOrigen' => 'Santiago',
];

// Objetos de Firma y LibroCompraVenta
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$LibroCompraVenta = new \sasco\LibreDTE\Sii\LibroCompraVenta();

// enviar libro de ventas y mostrar resultado del envío: track id o bien =false si hubo error
$LibroCompraVenta->setFirma($Firma);
$LibroCompraVenta->setCaratula($caratula);
$LibroCompraVenta->generar();
$track_id = $LibroCompraVenta->enviar(); // enviar XML generado en línea anterior
var_dump($track_id);

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
