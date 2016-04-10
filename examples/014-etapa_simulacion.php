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
 * @file 014-etapa_simulacion.php
 *
 * Ejemplo que genera los documentos para la etapa de simulación, se generarán
 * 21 documentos repartidos entre:
 *
 * - Factura electrónica (11)
 * - Factura exenta electrónica (6)
 * - Nota de crédito electrónica (3)
 * - Nota de débito electrónica (1)
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// primer folio a usar para envío de documentos de simulación
$folios = [
    33 => 47, // factura electrónica + 11 (folios disponibles hasta 100)
    34 => 39, // factura exenta electrónica + 6 (folios disponibles hasta 100)
    56 => 23, // nota de débito electrónica + 1 (folios disponibles hasta 28)
    61 => 45, // nota de crédito electrónica + 3 (folios disponibles hasta 62)
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

// datos del receptor
$Receptor = [
    'RUTRecep' => '55666777-8',
    'RznSocRecep' => 'Empresa S.A.',
    'GiroRecep' => 'Servicios jurídicos',
    'DirRecep' => 'Santiago',
    'CmnaRecep' => 'Santiago',
];

// datos de los DTE (cada elemento del arreglo $documentos es un DTE)
$documentos = [
    // 1 - Factura: 1 producto, sin descuentos
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33],
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Antena ranurada 16 dBi',
                'QtyItem' => 1,
                'PrcItem' => 70000,
            ],
        ],
    ],
    // 2 - Factura: 3 productos, sin descuentos
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+1,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Punto de acceso WAP54G',
                'QtyItem' => 1,
                'PrcItem' => 35000,
            ],
            [
                'NmbItem' => 'Pigtail LMR-195',
                'QtyItem' => 1,
                'PrcItem' => 10000,
            ],
            [
                'NmbItem' => 'Antena omnidireccional 14 dBi',
                'QtyItem' => 1,
                'PrcItem' => 25000,
            ],
        ],
    ],
    // 3 - Factura: 2 productos iguales, sin descuentos
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+2,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Ubiquiti Loco M5',
                'QtyItem' => 2,
                'PrcItem' => 35000,
            ],
        ],
    ],
    // 4 - Factura: 2 productos iguales, 10% de descuento c/u
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+3,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Ubiquiti Loco M2',
                'QtyItem' => 2,
                'PrcItem' => 35000,
                'DescuentoPct' => 10,
            ],
        ],
    ],
    // 5 - Factura: 2 productos iguales, 6% de descuento global
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+4,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Ubiquiti Loco M2',
                'QtyItem' => 2,
                'PrcItem' => 35000,
            ],
        ],
        'DscRcgGlobal' => [
            'TpoMov' => 'D',
            'TpoValor' => '%',
            'ValorDR' => 6,
        ]
    ],
    // 6 - Factura: 2 productos iguales, 5.000 de descuento global
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+5,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Ubiquiti Loco M2',
                'QtyItem' => 2,
                'PrcItem' => 35000,
            ],
        ],
        'DscRcgGlobal' => [
            'TpoMov' => 'D',
            'TpoValor' => '$',
            'ValorDR' => 5000,
        ]
    ],
    // 7 - Factura: 10 productos iguales, c/u 10% descuento, 5.000 descuento global
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+6,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Ubiquiti Loco M2',
                'QtyItem' => 10,
                'PrcItem' => 35000,
                'DescuentoPct' => 10,
            ],
        ],
        'DscRcgGlobal' => [
            'TpoMov' => 'D',
            'TpoValor' => '$',
            'ValorDR' => 5000,
        ]
    ],
    // 8 - Factura: 10 productos iguales, c/u 10% descuento, 7% descuento global
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+7,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Ubiquiti Loco M2',
                'QtyItem' => 10,
                'PrcItem' => 35000,
                'DescuentoPct' => 10,
            ],
        ],
        'DscRcgGlobal' => [
            'TpoMov' => 'D',
            'TpoValor' => '%',
            'ValorDR' => 7,
        ]
    ],
    // 9 - Factura: 3 productos, 6% descuento global
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+8,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Punto de acceso WAP54G',
                'QtyItem' => 1,
                'PrcItem' => 35000,
            ],
            [
                'NmbItem' => 'Pigtail LMR-195',
                'QtyItem' => 1,
                'PrcItem' => 10000,
            ],
            [
                'NmbItem' => 'Antena omnidireccional 14 dBi',
                'QtyItem' => 1,
                'PrcItem' => 25000,
            ],
        ],
        'DscRcgGlobal' => [
            'TpoMov' => 'D',
            'TpoValor' => '%',
            'ValorDR' => 6,
        ]
    ],
    // 10 - Factura: 3 productos, 6% descuento global, un producto con 50% descuento
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+9,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Punto de acceso WAP54G',
                'QtyItem' => 1,
                'PrcItem' => 35000,
            ],
            [
                'NmbItem' => 'Pigtail LMR-195',
                'QtyItem' => 1,
                'PrcItem' => 10000,
                'DescuentoPct' => 50,
            ],
            [
                'NmbItem' => 'Antena omnidireccional 14 dBi',
                'QtyItem' => 1,
                'PrcItem' => 25000,
            ],
        ],
        'DscRcgGlobal' => [
            'TpoMov' => 'D',
            'TpoValor' => '%',
            'ValorDR' => 6,
        ]
    ],
    // 11 - Nota de crédito: corrige dirección del receptor
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61],
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
            'Totales' => [
                'MntTotal' => 0,
            ]
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Antena ranurada 16 dBi',
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => 33,
            'FolioRef' => $folios[33],
            'CodRef' => 2,
            'RazonRef' => 'Corrige dirección del receptor',
        ]
    ],
    // 12 - Nota de crédito: anula factura
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61]+1,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
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
                'NmbItem' => 'Ubiquiti Loco M2',
                'QtyItem' => 2,
                'PrcItem' => 35000,
                'DescuentoPct' => 10,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => 33,
            'FolioRef' => $folios[33]+3,
            'CodRef' => 1,
            'RazonRef' => 'Anula factura',
        ]
    ],
    // 13 - Nota de crédito: devolución mercadería (1 producto)
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 61,
                'Folio' => $folios[61]+2,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
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
                'NmbItem' => 'Ubiquiti Loco M5',
                'QtyItem' => 1,
                'PrcItem' => 35000,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => 33,
            'FolioRef' => $folios[33]+2,
            'CodRef' => 3,
            'RazonRef' => 'Devolución mercadería',
        ]
    ],
    // 14 - Nota de débito: anula nota de crédito de devolución de mercadería
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 56,
                'Folio' => $folios[56],
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
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
                'NmbItem' => 'Ubiquiti Loco M2',
                'QtyItem' => 1,
                'PrcItem' => 35000,
            ],
        ],
        'Referencia' => [
            'TpoDocRef' => 61,
            'FolioRef' => $folios[61]+2,
            'CodRef' => 1,
            'RazonRef' => 'Anula nota de crédito electrónica',
        ]
    ],
    // 15 - Factura: 1 producto afecto y un servicio exento, sin descuentos
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 33,
                'Folio' => $folios[33]+10,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Punto de acceso WAP54G',
                'QtyItem' => 1,
                'PrcItem' => 35000,
            ],
            [
                'IndExe' => 1,
                'NmbItem' => 'Asesoría en instalación de AP',
                'QtyItem' => 1,
                'PrcItem' => 15000,
            ],
        ],
    ],
    // 16 - Factura exenta: 1 servicio
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 34,
                'Folio' => $folios[34],
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Desarrollo y mantención webapp agosto',
                'QtyItem' => 1,
                'PrcItem' => 950000,
            ],
        ],
    ],
    // 17 - Factura exenta: 2 servicios
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 34,
                'Folio' => $folios[34]+1,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Desarrollo y mantención webapp agosto',
                'QtyItem' => 1,
                'PrcItem' => 950000,
            ],
            [
                'NmbItem' => 'Configuración en terreno de servidor web',
                'QtyItem' => 1,
                'PrcItem' => 80000,
            ],
        ],
    ],
    // 18 - Factura exenta: 1 servicio de capacitación por horas
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 34,
                'Folio' => $folios[34]+2,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Capacitación aplicación web',
                'QtyItem' => 8,
                'UnmdItem' => 'Hora',
                'PrcItem' => 25000,
            ],
        ],
    ],
    // 19 - Factura exenta: 1 servicio de desarrollo por horas más capacitación
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 34,
                'Folio' => $folios[34]+3,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Desarrollo nueva funcionalidad',
                'QtyItem' => 16,
                'UnmdItem' => 'Hora',
                'PrcItem' => 14000,
            ],
            [
                'NmbItem' => 'Capacitación nueva funcionalidad',
                'QtyItem' => 2,
                'UnmdItem' => 'Hora',
                'PrcItem' => 25000,
            ],
        ],
    ],
    // 20 - Factura exenta: 1 servicio con descuento global del 50%
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 34,
                'Folio' => $folios[34]+4,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Certificación facturación electrónica',
                'QtyItem' => 1,
                'PrcItem' => 599000,
            ],
        ],
        'DscRcgGlobal' => [
            'TpoMov' => 'D',
            'TpoValor' => '%',
            'ValorDR' => 50,
            'IndExeDR' => 1,
        ]
    ],
    // 21 - Factura exenta: 2 servicios, uno con descuento del 50%
    [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 34,
                'Folio' => $folios[34]+5,
            ],
            'Emisor' => $Emisor,
            'Receptor' => $Receptor,
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Desarrolo interfaces para API LibreDTE',
                'QtyItem' => 40,
                'UnmdItem' => 'Hora',
                'PrcItem' => 14000,
            ],
            [
                'NmbItem' => 'Capacitación API facturación electrónica',
                'QtyItem' => 4,
                'UnmdItem' => 'Hora',
                'PrcItem' => 25000,
            ],
            [
                'NmbItem' => 'Certificación facturación electrónica',
                'QtyItem' => 1,
                'PrcItem' => 599000,
                'DescuentoPct' => 50,
            ],
        ],
    ],
];

// Objetos de Firma, Folios y EnvioDTE
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);
$Folios = [];
foreach ($folios as $tipo => $cantidad)
    $Folios[$tipo] = new \sasco\LibreDTE\Sii\Folios(file_get_contents('xml/folios/'.$tipo.'.xml'));
$EnvioDTE = new \sasco\LibreDTE\Sii\EnvioDte();

// generar cada DTE, timbrar, firmar y agregar al sobre de EnvioDTE
foreach ($documentos as $documento) {
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
$track_id = $EnvioDTE->enviar();
var_dump($track_id);

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
