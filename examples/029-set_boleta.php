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
 * @file 029-set_boleta.php
 *
 * Ejemplo que genera el EnvioBOLETA para el set de pruebas de boleta
 * electrónica
 *
 * Para obtener set seguir pasos de:
 *  <http://www.sii.cl/factura_electronica/guia_emitir_boleta_servicio.htm>
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-11
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// primer folio a usar para envio de set de pruebas
$folios = [
    39 => 1, // boleta electrónica
    //61 => 56, // nota de crédito electrónicas
];

// caratula para el envío de los dte
$caratula = [
    'RutEnvia' => '16261063-5',
    'RutReceptor' => '60803000-K',
    'FchResol' => '2014-12-05',
    'NroResol' => 0,
];

// datos del emisor
$Emisor = [
    'RUTEmisor' => '76192083-9',
    'RznSocEmisor' => 'SASCO SpA',
    'GiroEmisor' => 'Servicios integrales de informática',
    'DirOrigen' => 'Santiago',
    'CmnaOrigen' => 'Santiago',
];

// datos el recepor
$Receptor = [
    'RUTRecep' => '99511740-1',
    'RznSocRecep' => 'Colectron S.A.',
    'DirRecep' => 'Santiago',
    'CmnaRecep' => 'Santiago',
];

// datos de los DTE (cada elemento del arreglo $set_pruebas es un DTE)
$set_pruebas = [
    // CASO 1
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39],
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'koyak el chupete',
                'QtyItem' => 12,
                'PrcItem' => 170,
            ],
            [
                'NmbItem' => 'cuaderno pre U',
                'QtyItem' => 20,
                'PrcItem' => 1050,
            ],
        ],
    ],
    // CASO 2
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39]+1,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'pizza española el italiano',
                'QtyItem' => 29,
                'PrcItem' => 2990,
            ],
        ],
    ],
    // CASO 3
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39]+2,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'sorpresa de cumpleaño',
                'QtyItem' => 90,
                'PrcItem' => 300,
            ],
            [
                'NmbItem' => 'gorros superhéroes',
                'QtyItem' => 13,
                'PrcItem' => 840,
            ],
        ],
    ],
    // CASO 4
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39]+3,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'item afecto 1',
                'QtyItem' => 12,
                'PrcItem' => 1500,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'item exento 2',
                'QtyItem' => 2,
                'PrcItem' => 2590,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'item exento 3',
                'QtyItem' => 1,
                'PrcItem' => 5000,
            ],
        ],
    ],
    // CASO 5
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39]+4,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'combo Italiano + bebida',
                'QtyItem' => 12,
                'PrcItem' => 1690,
            ],
        ],
    ],
    // CASO 6
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39]+5,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'item afecto 1',
                'QtyItem' => 5,
                'PrcItem' => 25,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'item exento 2',
                'QtyItem' => 1,
                'PrcItem' => 20000,
            ],
        ],
    ],
    // CASO 7
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39]+6,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'goma de borrar school',
                'QtyItem' => 5,
                'PrcItem' => 340,
            ],
        ],
    ],
    // CASO 8
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39]+7,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Té ceylan',
                'QtyItem' => 5,
                'PrcItem' => 3178,
            ],
            [
                'NmbItem' => 'Jugo super natural de 3/4 lts',
                'QtyItem' => 38,
                'PrcItem' => 150,
            ],
        ],
    ],
    // CASO 9
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39]+8,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'lápiz tinta azul',
                'QtyItem' => 10,
                'PrcItem' => 290,
            ],
            [
                'NmbItem' => 'lápiz tinta rojo',
                'QtyItem' => 5,
                'PrcItem' => 250,
            ],
            [
                'NmbItem' => 'lápiz tinta mágica',
                'QtyItem' => 3,
                'PrcItem' => 790,
            ],
            [
                'NmbItem' => 'lápiz corrector',
                'QtyItem' => 2,
                'PrcItem' => 1190,
            ],
            [
                'NmbItem' => 'corchetera',
                'QtyItem' => 1,
                'PrcItem' => 3500,
            ],
        ],
    ],
    // CASO 10
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folios[39]+9,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Clavo Galvanizado 3/4"',
                'QtyItem' => 3.8,
                'UnmdItem' => 'Kg',
                'PrcItem' => 710,
            ],
        ],
    ],
];

// Objetos de Firma, Folios y EnvioDTE
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$Folios = [];
foreach ($folios as $tipo => $cantidad)
    $Folios[$tipo] = new \sasco\LibreDTE\Sii\Folios(file_get_contents('xml/folios/'.$tipo.'.xml'));
$EnvioDTE = new \sasco\LibreDTE\Sii\EnvioDTE();

// generar cada DTE, timbrar, firmar y agregar al sobre de EnvioDTE
foreach ($set_pruebas as $documento) {
    $DTE = new \sasco\LibreDTE\Sii\Dte($documento);
    if (!$DTE->timbrar($Folios[$DTE->getTipo()]))
        break;
    if (!$DTE->firmar($Firma))
        break;
    $EnvioDTE->agregar($DTE);
}

// enviar dtes y mostrar resultado del envío: track id o bien =false si hubo error
$EnvioDTE->setCaratula($caratula);
$EnvioDTE->setFirma($Firma);
$EnvioDTE->generar();
if ($EnvioDTE->schemaValidate()) {
    //file_put_contents('xml/EnvioBOLETA.xml', $EnvioDTE->generar()); // guardar XML en sistema de archivos
    echo $EnvioDTE->generar();
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
