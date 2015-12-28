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
 * @file 010-set_pruebas_basico.php
 *
 * Ejemplo que genera y envía los documentos del set de pruebas básico para
 * certificación ante el SII de los documentos:
 *
 * - Factura electrónica
 * - Nota de crédito electrónica
 * - Nota de débito electrónica
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// primer folio a usar para envio de set de pruebas
$folios = [
    33 => 21, // factura electrónica
    56 => 5, // nota de débito electrónica
    61 => 13, // nota de crédito electrónicas
];

// caratula para el envío de los dte
$caratula = [
    'RutEnvia' => '11222333-4',
    'RutReceptor' => '60803000-K',
    'FchResol' => '2014-12-05',
    'NroResol' => 0,
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

// datos de los DTE (cada elemento del arreglo $set_pruebas es un DTE)
$set_pruebas = [
    // CASO 414175-1
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33],
            ],
            'Emisor' => $Emisor,
            'Receptor' => [
                'RUTRecep' => '55666777-8',
                'RznSocRecep' => 'Empresa S.A.',
                'GiroRecep' => 'Servicios jurídicos',
                'DirRecep' => 'Santiago',
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
        'Referencia' => [
            'TpoDocRef' => 'SET',
            'FolioRef' => $folios[33],
            'RazonRef' => 'CASO 414175-1',
        ],
    ],
    // CASO 414175-2
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+1,
            ],
            'Emisor' => $Emisor,
            'Receptor' => [
                'RUTRecep' => '55666777-8',
                'RznSocRecep' => 'Empresa S.A.',
                'GiroRecep' => 'Servicios jurídicos',
                'DirRecep' => 'Santiago',
                'CmnaRecep' => 'Santiago',
            ],
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Pañuelo AFECTO',
                'QtyItem' => 235,
                'PrcItem' => 1926,
                'DescuentoPct' => 4,
            ],
            [
                'NmbItem' => 'ITEM 2 AFECTO',
                'QtyItem' => 161,
                'PrcItem' => 990,
                'DescuentoPct' => 5,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => 'SET',
            'FolioRef' => $folios[33]+1,
            'RazonRef' => 'CASO 414175-2',
        ],
    ],
    // CASO 414175-3
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+2,
            ],
            'Emisor' => $Emisor,
            'Receptor' => [
                'RUTRecep' => '55666777-8',
                'RznSocRecep' => 'Empresa S.A.',
                'GiroRecep' => 'Servicios jurídicos',
                'DirRecep' => 'Santiago',
                'CmnaRecep' => 'Santiago',
            ],
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Pintura B&W AFECTO',
                'QtyItem' => 24,
                'PrcItem' => 1937,
            ],
            [
                'NmbItem' => 'ITEM 2 AFECTO',
                'QtyItem' => 149,
                'PrcItem' => 2975,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'ITEM 3 SERVICIO EXENTO',
                'QtyItem' => 1,
                'PrcItem' => 34705,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => 'SET',
            'FolioRef' => $folios[33]+2,
            'RazonRef' => 'CASO 414175-3',
        ],
    ],
    // CASO 414175-4
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+3,
            ],
            'Emisor' => $Emisor,
            'Receptor' => [
                'RUTRecep' => '55666777-8',
                'RznSocRecep' => 'Empresa S.A.',
                'GiroRecep' => 'Servicios jurídicos',
                'DirRecep' => 'Santiago',
                'CmnaRecep' => 'Santiago',
            ],
        ],
        'Detalle' => [
            [
                'NmbItem' => 'ITEM 1 AFECTO',
                'QtyItem' => 81,
                'PrcItem' => 1672,
            ],
            [
                'NmbItem' => 'ITEM 2 AFECTO',
                'QtyItem' => 35,
                'PrcItem' => 1405,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'ITEM 3 SERVICIO EXENTO',
                'QtyItem' => 2,
                'PrcItem' => 6767,
            ],
        ],
        'DscRcgGlobal' => [
            'TpoMov' => 'D',
            'TpoValor' => '%',
            'ValorDR' => 6,
        ],
        'Referencia' => [
            'TpoDocRef' => 'SET',
            'FolioRef' => $folios[33]+3,
            'RazonRef' => 'CASO 414175-4',
        ],
    ],
    // CASO 414175-5
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61],
            ],
            'Emisor' => $Emisor,
            'Receptor' => [
                'RUTRecep' => '55666777-8',
                'RznSocRecep' => 'Empresa S.A.',
                'GiroRecep' => 'Servicios jurídicos',
                'DirRecep' => 'Santiago',
                'CmnaRecep' => 'Santiago',
            ],
            'Totales' => [
                'MntTotal' => 0,
            ]
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Cajón AFECTO',
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[61],
                'RazonRef' => 'CASO 414175-5',
            ],
            [
                'TpoDocRef' => 33,
                'FolioRef' => $folios[33],
                'CodRef' => 2,
                'RazonRef' => 'CORRIGE GIRO DEL RECEPTOR',
            ],
        ]
    ],
    // CASO 414175-6
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61]+1,
            ],
            'Emisor' => $Emisor,
            'Receptor' => [
                'RUTRecep' => '55666777-8',
                'RznSocRecep' => 'Empresa S.A.',
                'GiroRecep' => 'Servicios jurídicos',
                'DirRecep' => 'Santiago',
                'CmnaRecep' => 'Santiago',
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
                'NmbItem' => 'Pañuelo AFECTO',
                'QtyItem' => 86,
                'PrcItem' => 1926,
                'DescuentoPct' => 4,
            ],
            [
                'NmbItem' => 'ITEM 2 AFECTO',
                'QtyItem' => 109,
                'PrcItem' => 990,
                'DescuentoPct' => 5,
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[61]+1,
                'RazonRef' => 'CASO 414175-6',
            ],
            [
                'TpoDocRef' => 33,
                'FolioRef' => $folios[33]+1,
                'CodRef' => 3,
                'RazonRef' => 'DEVOLUCION DE MERCADERIAS',
            ],
        ]
    ],
    // CASO 414175-7
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61]+2,
            ],
            'Emisor' => $Emisor,
            'Receptor' => [
                'RUTRecep' => '55666777-8',
                'RznSocRecep' => 'Empresa S.A.',
                'GiroRecep' => 'Servicios jurídicos',
                'DirRecep' => 'Santiago',
                'CmnaRecep' => 'Santiago',
            ],
            'Totales' => [
                // estos valores serán calculados automáticamente
                'MntNeto' => 0,
                'MntExe' => 0,
                'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                'IVA' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Pintura B&W AFECTO',
                'QtyItem' => 24,
                'PrcItem' => 1937,
            ],
            [
                'NmbItem' => 'ITEM 2 AFECTO',
                'QtyItem' => 149,
                'PrcItem' => 2975,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'ITEM 3 SERVICIO EXENTO',
                'QtyItem' => 1,
                'PrcItem' => 34705,
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[61]+2,
                'RazonRef' => 'CASO 414175-7',
            ],
            [
                'TpoDocRef' => 33,
                'FolioRef' => $folios[33]+2,
                'CodRef' => 1,
                'RazonRef' => 'ANULA FACTURA',
            ],
        ]
    ],
    // CASO 414175-8
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 56,
                'Folio' => $folios[56],
            ],
            'Emisor' => $Emisor,
            'Receptor' => [
                'RUTRecep' => '55666777-8',
                'RznSocRecep' => 'Empresa S.A.',
                'GiroRecep' => 'Servicios jurídicos',
                'DirRecep' => 'Santiago',
                'CmnaRecep' => 'Santiago',
            ],
            'Totales' => [
                'MntTotal' => 0,
            ],
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Cajón AFECTO',
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[56],
                'RazonRef' => 'CASO 414175-8',
            ],
            [
                'TpoDocRef' => 61,
                'FolioRef' => $folios[61],
                'CodRef' => 1,
                'RazonRef' => 'ANULA NOTA DE CREDITO ELECTRONICA',
            ],
        ]
    ],
];

// Objetos de Firma, Folios y EnvioDTE
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$Folios = [];
foreach ($folios as $tipo => $cantidad)
    $Folios[$tipo] = new \sasco\LibreDTE\Sii\Folios(file_get_contents('xml/folios/'.$tipo.'.xml'));
$EnvioDTE = new \sasco\LibreDTE\Sii\EnvioDte();

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
//file_put_contents('xml/EnvioDTE.xml', $EnvioDTE->generar()); // guardar XML en sistema de archivos
$track_id = $EnvioDTE->enviar();
var_dump($track_id);

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
