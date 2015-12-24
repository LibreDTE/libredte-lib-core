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
 * @version 2015-12-14
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// primer folio a usar para envio de set de pruebas
$folios = [
    39 => 1, // boleta electrónica
    61 => 56, // nota de crédito electrónicas
];

// caratula para el envío de los dte
$caratula = [
    //'RutEnvia' => '11222333-4', // se obtiene automáticamente de la firma
    'RutReceptor' => '60803000-K',
    'FchResol' => '2014-12-05',
    'NroResol' => 0,
];

// datos del emisor
$Emisor = [
    'RUTEmisor' => '76192083-9',
    'RznSoc' => 'SASCO SpA', // tag verdadero es RznSocEmisor, pero se permite usar el de DTE
    'GiroEmis' => 'Servicios integrales de informática', // tag verdadero es GiroEmisor, pero se permite usar el de DTE
    'Acteco' => 726000, // en boleta este tag no va y se quita al normalizar (se deja para nota de crédito)
    'DirOrigen' => 'Santiago',
    'CmnaOrigen' => 'Santiago',
];

// datos el recepor
$Receptor = [
    'RUTRecep' => '55666777-8',
    'RznSocRecep' => 'Cliente S.A.',
    'DirRecep' => 'Santiago',
    'CmnaRecep' => 'Santiago',
];

// datos de las boletas (cada elemento del arreglo $set_pruebas es una boleta)
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

// Objetos de Firma y Folios
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$Folios = [];
foreach ($folios as $tipo => $cantidad)
    $Folios[$tipo] = new \sasco\LibreDTE\Sii\Folios(file_get_contents('xml/folios/'.$tipo.'.xml'));

// generar cada DTE, timbrar, firmar y agregar al sobre de EnvioBOLETA
$EnvioDTE = new \sasco\LibreDTE\Sii\EnvioDte();
foreach ($set_pruebas as $documento) {
    $DTE = new \sasco\LibreDTE\Sii\Dte($documento);
    if (!$DTE->timbrar($Folios[$DTE->getTipo()]))
        break;
    if (!$DTE->firmar($Firma))
        break;
    $EnvioDTE->agregar($DTE);
}
$EnvioDTE->setFirma($Firma);
$EnvioDTE->setCaratula($caratula);
$EnvioDTE->generar();
if ($EnvioDTE->schemaValidate()) {
    if (is_writable('xml/EnvioBOLETA.xml'))
        file_put_contents('xml/EnvioBOLETA.xml', $EnvioDTE->generar()); // guardar XML en sistema de archivos
    echo $EnvioDTE->generar();
}

// crear notas de crédito para el set de prueba
$notas_credito = [
    \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct($set_pruebas[0], [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61],
                'MntBruto' => 1,
            ],
            'Totales' => [
                // estos valores serán calculados automáticamente
                'MntNeto' => 0,
                'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                'IVA' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => $set_pruebas[0]['Encabezado']['IdDoc']['TipoDTE'],
            'FolioRef' => $set_pruebas[0]['Encabezado']['IdDoc']['Folio'],
            'CodRef' => 1,
            'RazonRef' => 'ANULA BOLETA',
        ],
    ]),
    \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct($set_pruebas[2], [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61]+1,
                'MntBruto' => 1,
            ],
            'Totales' => [
                // estos valores serán calculados automáticamente
                'MntNeto' => 0,
                'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                'IVA' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => $set_pruebas[2]['Encabezado']['IdDoc']['TipoDTE'],
            'FolioRef' => $set_pruebas[2]['Encabezado']['IdDoc']['Folio'],
            'CodRef' => 1,
            'RazonRef' => 'ANULA BOLETA',
        ],
    ]),
    \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct($set_pruebas[4], [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61]+2,
                'MntBruto' => 1,
            ],
            'Totales' => [
                // estos valores serán calculados automáticamente
                'MntNeto' => 0,
                'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                'IVA' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => $set_pruebas[4]['Encabezado']['IdDoc']['TipoDTE'],
            'FolioRef' => $set_pruebas[4]['Encabezado']['IdDoc']['Folio'],
            'CodRef' => 1,
            'RazonRef' => 'ANULA BOLETA',
        ],
    ]),
    \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct($set_pruebas[6], [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61]+3,
                'MntBruto' => 1,
            ],
            'Totales' => [
                // estos valores serán calculados automáticamente
                'MntNeto' => 0,
                'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                'IVA' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Detalle' => [
            [
                'QtyItem' => $set_pruebas[6]['Detalle'][0]['QtyItem']*0.4,
            ]
        ],
        'Referencia' => [
            'TpoDocRef' => $set_pruebas[6]['Encabezado']['IdDoc']['TipoDTE'],
            'FolioRef' => $set_pruebas[6]['Encabezado']['IdDoc']['Folio'],
            'CodRef' => 3,
            'RazonRef' => 'SE REBAJA EN UN 40%',
        ],
    ]),
    \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct($set_pruebas[9], [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61]+4,
                'MntBruto' => 1,
            ],
            'Totales' => [
                // estos valores serán calculados automáticamente
                'MntNeto' => 0,
                'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                'IVA' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Detalle' => [
            [
                'QtyItem' => $set_pruebas[9]['Detalle'][0]['QtyItem']*0.4,
            ]
        ],
        'Referencia' => [
            'TpoDocRef' => $set_pruebas[9]['Encabezado']['IdDoc']['TipoDTE'],
            'FolioRef' => $set_pruebas[9]['Encabezado']['IdDoc']['Folio'],
            'CodRef' => 3,
            'RazonRef' => 'SE REBAJA EN UN 40%',
        ],
    ]),
];

// generar cada DTE, timbrar, firmar y agregar al sobre de EnvioDTE
$EnvioDTE = new \sasco\LibreDTE\Sii\EnvioDte();
foreach ($notas_credito as $documento) {
    $DTE = new \sasco\LibreDTE\Sii\Dte($documento);
    if (!$DTE->timbrar($Folios[$DTE->getTipo()]))
        break;
    if (!$DTE->firmar($Firma))
        break;
    $EnvioDTE->agregar($DTE);
}
$EnvioDTE->setFirma($Firma);
$EnvioDTE->setCaratula($caratula);
$EnvioDTE->generar();
if ($EnvioDTE->schemaValidate()) {
    if (is_writable('xml/EnvioDTE.xml'))
        file_put_contents('xml/EnvioDTE.xml', $EnvioDTE->generar()); // guardar XML en sistema de archivos
    echo $EnvioDTE->generar();
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
