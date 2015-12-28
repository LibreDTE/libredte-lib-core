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
 * @file 011-set_pruebas_factura_exenta.php
 *
 * Ejemplo que genera y envía los documentos del set de pruebas de factura
 * exenta para certificación ante el SII de los documentos:
 *
 * - Factura exenta electrónica
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
    34 => 24, // factura exenta electrónica + 3 (folios disponibles hasta 100)
    56 => 19, // nota de débito electrónica + 2 (folios disponibles hasta 20)
    61 => 34, // nota de crédito electrónica + 3 (folios disponibles hasta 44)
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
    // CASO 414178-1
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 34,
                'Folio' => $folios[34],
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
                'NmbItem' => 'HORAS PROGRAMADOR',
                'QtyItem' => 12,
                'UnmdItem' => 'Hora',
                'PrcItem' => 6991,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => 'SET',
            'FolioRef' => $folios[34],
            'RazonRef' => 'CASO 414178-1',
        ],
    ],
    // CASO 414178-2
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
                // estos valores serán calculados automáticamente
                'MntExe' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Detalle' => [
            [
                'NmbItem' => 'HORAS PROGRAMADOR',
                'IndExe' => 1,
                'QtyItem' => 12,
                'UnmdItem' => 'Hora',
                'PrcItem' => 874,
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[61],
                'RazonRef' => 'CASO 414178-2',
            ],
            [
                'TpoDocRef' => 34,
                'FolioRef' => $folios[34],
                'CodRef' => 3,
                'RazonRef' => 'MODIFICA MONTO',
            ],
        ],
    ],
    // CASO 414178-3
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 34,
                'Folio' => $folios[34]+1,
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
                'NmbItem' => 'SERV CONSULTORIA FACT ELECTRONICA',
                'QtyItem' => 1,
                'PrcItem' => 360368,
            ],
            [
                'NmbItem' => 'SERV CONSULTORIA GUIA DESPACHO ELECT',
                'QtyItem' => 1,
                'PrcItem' => 262001,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => 'SET',
            'FolioRef' => $folios[34]+1,
            'RazonRef' => 'CASO 414178-3',
        ],
    ],
    // CASO 414178-4
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
                'MntExe' => 0,
                'MntTotal' => 0,
            ]
        ],
        'Detalle' => [
            [
                'NmbItem' => 'SERV CONSULTORIA FACT ELECTRONICA',
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[61]+1,
                'RazonRef' => 'CASO 414178-4',
            ],
            [
                'TpoDocRef' => 34,
                'FolioRef' => $folios[34]+1,
                'CodRef' => 2,
                'RazonRef' => 'CORRIGE GIRO',
            ],
        ]
    ],
    // CASO 414178-5
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
                'MntExe' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Detalle' => [
            [
                'NmbItem' => 'SERV CONSULTORIA FACT ELECTRONICA',
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[56],
                'RazonRef' => 'CASO 414178-5',
            ],
            [
                'TpoDocRef' => 61,
                'FolioRef' => $folios[61]+1,
                'CodRef' => 1,
                'RazonRef' => 'ANULA NOTA DE CREDITO ELECTRONICA',
            ],
        ]
    ],
    // CASO 414178-6
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 34,
                'Folio' => $folios[34]+2,
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
                'NmbItem' => 'CAPACITACION USO CIGUEÑALES',
                'QtyItem' => 1,
                'PrcItem' => 350414,
            ],
            [
                'NmbItem' => 'CAPACITACION USO PLC\'s CNC',
                'QtyItem' => 1,
                'PrcItem' => 239579,
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[34]+2,
                'RazonRef' => 'CASO 414178-6',
            ],
        ]
    ],
    // CASO 414178-7
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
                'MntExe' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Detalle' => [
            [
                'NmbItem' => 'CAPACITACION USO CIGUEÑALES',
                'IndExe' => 1,
                'QtyItem' => 1,
                'PrcItem' => 175207,
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[61]+2,
                'RazonRef' => 'CASO 414178-7',
            ],
            [
                'TpoDocRef' => 34,
                'FolioRef' => $folios[34]+2,
                'CodRef' => 3,
                'RazonRef' => 'MODIFICA MONTO',
            ],
        ]
    ],
    // CASO 414178-8
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 56,
                'Folio' => $folios[56]+1,
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
                'MntExe' => 0,
                'MntTotal' => 0,
            ],
        ],
        'Detalle' => [
            [
                'NmbItem' => 'CAPACITACION USO PLC\'s CNC',
                'IndExe' => 1,
                'QtyItem' => 1,
                'PrcItem' => 47916,
            ],
        ],
        'Referencia' => [
            [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[56]+1,
                'RazonRef' => 'CASO 414178-8',
            ],
            [
                'TpoDocRef' => 34,
                'FolioRef' => $folios[34]+2,
                'CodRef' => 3,
                'RazonRef' => 'MODIFICA MONTO',
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
