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
 * @file 012-set_pruebas_ventas.php
 *
 * Ejemplo que genera y envía el archivo de Información Electrónica de Ventas
 * (IEV) para certificación ante el SII de los documentos. El IEV se genera con
 * los datos del set de prueba básico (ver ejemplo: 010-set_pruebas_basico.php)
 *
 * Para el ambiente de certificación:
 *  - Libro de ventas se envía sin firmar
 *  - Período tributario debe ser del año 1980
 *  - Fecha resolución debe ser 2006-01-20
 *  - Número resolución y folio notificación deben ser: 102006
 *
 * Adicionalmente el libro de ventas no debe incluir el detalle de los
 * documentos si son electrónicos, o sea en este caso (al ser del set de DTE) no
 * se incluyen los detalles. Los detalles sólo se usan para hacer el cálculo de
 * totales.
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// caratula del libro
$caratula = [
    'RutEmisorLibro' => '76192083-9',
    'RutEnvia' => '11222333-4',
    'PeriodoTributario' => '1980-03',
    'FchResol' => '2006-01-20',
    'NroResol' => 102006,
    'TipoOperacion' => 'VENTA',
    'TipoLibro' => 'ESPECIAL',
    'TipoEnvio' => 'TOTAL',
    'FolioNotificacion' => 102006,
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
// IMPORTANTE: folios deben coincidir con los de los DTEs que fueron aceptados
// en el proceso de certificación del set de pruebas básico
$set_pruebas = [
    // CASO 414175-1
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => 21,
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
            'FolioRef' => 21,
            'RazonRef' => 'CASO 414175-1',
        ],
    ],
    // CASO 414175-2
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => 22,
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
            'FolioRef' => 22,
            'RazonRef' => 'CASO 414175-2',
        ],
    ],
    // CASO 414175-3
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => 23,
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
            'FolioRef' => 23,
            'RazonRef' => 'CASO 414175-3',
        ],
    ],
    // CASO 414175-4
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => 24,
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
            'FolioRef' => 24,
            'RazonRef' => 'CASO 414175-4',
        ],
    ],
    // CASO 414175-5
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => 13,
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
                'FolioRef' => 13,
                'RazonRef' => 'CASO 414175-5',
            ],
            [
                'TpoDocRef' => 33,
                'FolioRef' => 21,
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
                'Folio' => 14,
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
                'FolioRef' => 14,
                'RazonRef' => 'CASO 414175-6',
            ],
            [
                'TpoDocRef' => 33,
                'FolioRef' => 22,
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
                'Folio' => 15,
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
                'FolioRef' => 15,
                'RazonRef' => 'CASO 414175-7',
            ],
            [
                'TpoDocRef' => 33,
                'FolioRef' => 23,
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
                'Folio' => 5,
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
                'FolioRef' => 5,
                'RazonRef' => 'CASO 414175-8',
            ],
            [
                'TpoDocRef' => 61,
                'FolioRef' => 13,
                'CodRef' => 1,
                'RazonRef' => 'ANULA NOTA DE CREDITO ELECTRONICA',
            ],
        ]
    ],
];

// Objetos de Firma y LibroCompraVenta
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$LibroCompraVenta = new \sasco\LibreDTE\Sii\LibroCompraVenta();

// generar cada DTE y agregar su resumen al detalle del libro
foreach ($set_pruebas as $documento) {
    $DTE = new \sasco\LibreDTE\Sii\Dte($documento);
    $LibroCompraVenta->agregar($DTE->getResumen(), false); // agregar detalle sin normalizar
}

// enviar libro de ventas y mostrar resultado del envío: track id o bien =false si hubo error
$LibroCompraVenta->setCaratula($caratula);
$LibroCompraVenta->generar(false); // generar XML sin firma y sin detalle
$LibroCompraVenta->setFirma($Firma);
$track_id = $LibroCompraVenta->enviar(); // enviar XML generado en línea anterior
var_dump($track_id);

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
