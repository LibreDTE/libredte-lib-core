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
 * @file 032-boleta_simulacion.php
 *
 * Ejemplo que genera el EnvioBOLETA para el set de simulación de boletas
 * electrónicas
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
                'NmbItem' => 'Punto de Acceso WAP54G',
                'QtyItem' => 4,
                'PrcItem' => 35000,
            ],
            [
                'NmbItem' => 'Router WRT54GL',
                'QtyItem' => 1,
                'PrcItem' => 55000,
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
                'NmbItem' => 'Cables de red UTP CAT6 1.5m',
                'QtyItem' => 20,
                'PrcItem' => 1500,
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
                'NmbItem' => 'Rack 45U',
                'QtyItem' => 1,
                'PrcItem' => 650000,
            ],
            [
                'NmbItem' => 'Bandejas 19"',
                'QtyItem' => 4,
                'PrcItem' => 27000,
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
                'NmbItem' => 'Servidor HP Proliant XYZ',
                'QtyItem' => 1,
                'PrcItem' => 1500000,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'Intalación sistema operativo',
                'QtyItem' => 1,
                'PrcItem' => 8000,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'Instalación servicios ambiente web',
                'QtyItem' => 1,
                'PrcItem' => 50000,
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
                'NmbItem' => 'Pendrive con Debian GNU/Linux',
                'QtyItem' => 1,
                'PrcItem' => 10000,
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
                'NmbItem' => 'Tarjeta de red 100/1000 Mbps',
                'QtyItem' => 2,
                'PrcItem' => 5000,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'Instalación tarjeta de red y puesta en marcha VLANs',
                'QtyItem' => 1,
                'PrcItem' => 200000,
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
                'NmbItem' => 'Antena yagi 13 dBi',
                'QtyItem' => 2,
                'PrcItem' => 34000,
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
                'NmbItem' => 'Antena yagi 11 dBi',
                'QtyItem' => 1,
                'PrcItem' => 30000,
            ],
            [
                'NmbItem' => 'Antena omnidirecional 16 dBi',
                'QtyItem' => 1,
                'PrcItem' => 70000,
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
                'NmbItem' => 'Cable de red UTP cat6 1.5 m',
                'QtyItem' => 10,
                'PrcItem' => 1500,
            ],
            [
                'NmbItem' => 'Teléfono VoIP',
                'QtyItem' => 10,
                'PrcItem' => 45000,
            ],
            [
                'NmbItem' => 'Adaptador enchufe',
                'QtyItem' => 10,
                'PrcItem' => 250,
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
                'NmbItem' => 'Cable de red sin conectores',
                'QtyItem' => 17,
                'UnmdItem' => 'm',
                'PrcItem' => 400,
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
    echo $EnvioDTE->generar();
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
