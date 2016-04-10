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
 * @file 021-libro_compras_csv.php
 *
 * Ejemplo que muestra como crear el libro de compras a partir de un archivo CSV
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-17
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';


// caratula del libro
$caratula = [
    'RutEmisorLibro' => '76192083-9',
    'RutEnvia' => '11222333-4',
    'PeriodoTributario' => '2000-03',
    'FchResol' => '2006-01-20',
    'NroResol' => 102006,
    'TipoOperacion' => 'COMPRA',
    'TipoLibro' => 'ESPECIAL',
    'TipoEnvio' => 'TOTAL',
    'FolioNotificacion' => 102006,
];

// Objetos de Firma y LibroCompraVenta
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$LibroCompraVenta = new \sasco\LibreDTE\Sii\LibroCompraVenta(true); // se genera libro simplificado (solicitado así en certificación)

// agregar detalle desde un archivo CSV con ; como separador
$LibroCompraVenta->agregarComprasCSV('libros/libro_compras.csv');

// enviar libro de compras y mostrar resultado del envío: track id o bien =false si hubo error
$LibroCompraVenta->setCaratula($caratula);
$LibroCompraVenta->generar(); // generar XML sin firma
$LibroCompraVenta->setFirma($Firma);
$track_id = $LibroCompraVenta->enviar(); // enviar XML generado en línea anterior
var_dump($track_id);

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
