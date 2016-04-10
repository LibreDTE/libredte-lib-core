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
 * @file 009-dte_33.php
 *
 * CASO 1
 * DOCUMENTO    FACTURA ELECTRONICA
 *
 * ITEM                    CANTIDAD        PRECIO UNITARIO
 * Cajón AFECTO               123             923
 * Relleno AFECTO               53            1473
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-15
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// datos
$factura = [
    'Encabezado' => [
        'IdDoc' => [
            'TipoDTE' => 33,
            'Folio' => 1,
        ],
        'Emisor' => [
            'RUTEmisor' => '76192083-9',
            'RznSoc' => 'SASCO SpA',
            'GiroEmis' => 'Servicios integrales de informática',
            'Acteco' => 726000,
            'DirOrigen' => 'Santiago',
            'CmnaOrigen' => 'Santiago',
        ],
        'Receptor' => [
            'RUTRecep' => '60803000-K',
            'RznSocRecep' => 'Servicio de Impuestos Internos',
            'GiroRecep' => 'Gobierno',
            'DirRecep' => 'Alonso Ovalle 680',
            'CmnaRecep' => 'Santiago',
        ],
    ],
    'Detalle' => [
        [
            'NmbItem' => 'Cajón AFECTO',
            'QtyItem' => 123,
            'PrcItem' => 923,
        ],
        [
            'NmbItem' => 'Relleno AFECTO',
            'QtyItem' => 53,
            'PrcItem' => 1473,
        ],
    ],
];
$caratula = [
    //'RutEnvia' => '11222333-4', // se obtiene de la firma
    'RutReceptor' => '60803000-K',
    'FchResol' => '2014-12-05',
    'NroResol' => 0,
];

// Objetos de Firma y Folios
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$Folios = new \sasco\LibreDTE\Sii\Folios(file_get_contents('xml/folios/33.xml'));

// generar XML del DTE timbrado y firmado
$DTE = new \sasco\LibreDTE\Sii\Dte($factura);
$DTE->timbrar($Folios);
$DTE->firmar($Firma);

// generar sobre con el envío del DTE y enviar al SII
$EnvioDTE = new \sasco\LibreDTE\Sii\EnvioDte();
$EnvioDTE->agregar($DTE);
$EnvioDTE->setFirma($Firma);
$EnvioDTE->setCaratula($caratula);
$EnvioDTE->generar();
if ($EnvioDTE->schemaValidate()) {
    echo $EnvioDTE->generar();
    //$track_id = $EnvioDTE->enviar();
    //var_dump($track_id);
}

// si hubo algún error se muestra
foreach (\sasco\LibreDTE\Log::readAll() as $log)
    echo $log,"\n";
