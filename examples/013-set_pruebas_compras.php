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
 * @file 013-set_pruebas_compras.php
 *
 * Ejemplo que genera y envía el archivo de Información Electrónica de Compras
 * (IEC) para certificación ante el SII de los documentos. El IEC se genera con
 * los datos del set de prueba de compras entregado por el SII.
 *
 * Para el ambiente de certificación:
 *  - Libro de ventas se envía sin firmar
 *  - Período tributario debe ser del año 2000
 *  - Fecha resolución debe ser 2006-01-20
 *  - Número resolución y folio notificación deben ser: 102006
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
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

// EN FACTURA CON IVA USO COMUN CONSIDERE QUE EL FACTOR DE PROPORCIONALIDAD
// DEL IVA ES DE 0.60
$factor_proporcionalidad_iva = 60; // se divide por 100 al agregar al resumen del período

// set de pruebas compras - número de atención 414177
$detalles = [
    // FACTURA DEL GIRO CON DERECHO A CREDITO
    [
        'TpoDoc' => 30,
        'NroDoc' => 234,
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
        'FchDoc' => $caratula['PeriodoTributario'].'-01',
        'RUTDoc' => '78885550-8',
        'MntNeto' => 53253,
    ],
    // FACTURA DEL GIRO CON DERECHO A CREDITO
    [
        'TpoDoc' => 33,
        'NroDoc' => 32,
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
        'FchDoc' => $caratula['PeriodoTributario'].'-01',
        'RUTDoc' => '78885550-8',
        'MntExe' => 10633,
        'MntNeto' => 11473,
    ],
    // FACTURA CON IVA USO COMUN
    [
        'TpoDoc' => 30,
        'NroDoc' => 781,
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
        'FchDoc' => $caratula['PeriodoTributario'].'-02',
        'RUTDoc' => '78885550-8',
        'MntNeto' => 30171,
        // Al existir factor de proporcionalidad se calculará el IVAUsoComun.
        // Se calculará como MntNeto * (TasaImp/100) y se añadirá a MntIVA.
        // Se quitará del detalle al armar los totales, ya que no es nodo del detalle en el XML.
        'FctProp' => $factor_proporcionalidad_iva,
    ],
    // NOTA DE CREDITO POR DESCUENTO A FACTURA 234
    [
        'TpoDoc' => 60,
        'NroDoc' => 451,
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
        'FchDoc' => $caratula['PeriodoTributario'].'-03',
        'RUTDoc' => '78885550-8',
        'MntNeto' => 2928,
    ],
    // ENTREGA GRATUITA DEL PROVEEDOR
    [
        'TpoDoc' => 33,
        'NroDoc' => 67,
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
        'FchDoc' => $caratula['PeriodoTributario'].'-04',
        'RUTDoc' => '78885550-8',
        'MntNeto' => 12135,
        'IVANoRec' => [
            'CodIVANoRec' => 4,
            'MntIVANoRec' => round(12135 * (\sasco\LibreDTE\Sii::getIVA()/100)),
        ],
    ],
    // COMPRA CON RETENCION TOTAL DEL IVA
    [
        'TpoDoc' => 46,
        'NroDoc' => 9,
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
        'FchDoc' => $caratula['PeriodoTributario'].'-05',
        'RUTDoc' => '78885550-8',
        'MntNeto' => 10632,
        'OtrosImp' => [
            'CodImp' => 15,
            'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
            'MntImp' => round(10632 * (\sasco\LibreDTE\Sii::getIVA()/100)),
        ],
    ],
    // NOTA DE CREDITO POR DESCUENTO FACTURA ELECTRONICA 32
    [
        'TpoDoc' => 60,
        'NroDoc' => 211,
        'TasaImp' => \sasco\LibreDTE\Sii::getIVA(),
        'FchDoc' => $caratula['PeriodoTributario'].'-06',
        'RUTDoc' => '78885550-8',
        'MntNeto' => 9053,
    ],
];

// Objetos de Firma y LibroCompraVenta
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$LibroCompraVenta = new \sasco\LibreDTE\Sii\LibroCompraVenta();

// agregar cada uno de los detalles al libro
foreach ($detalles as $detalle) {
    $LibroCompraVenta->agregar($detalle);
}

// enviar libro de compras y mostrar resultado del envío: track id o bien =false si hubo error
$LibroCompraVenta->setCaratula($caratula);
$LibroCompraVenta->generar(); // generar XML sin firma
$LibroCompraVenta->setFirma($Firma);
$track_id = $LibroCompraVenta->enviar(); // enviar XML generado en línea anterior
var_dump($track_id);

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
