<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

return [
    'build_simplificado_con_documentos' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 1001,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-05',
                    'RUTDoc'   => '87654321-0',
                    'RznSoc'   => 'Proveedor Nacional SA',
                    'MntNeto'  => 200000,
                    'MntIVA'   => 38000,
                    // 'MntTotal' => 238000, // Omitido a propósito para testear el cálculo del total.
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion'               => 'COMPRA',
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TpoDoc' => 33,
        ],
    ],
    'build_simplificado_con_iva_no_recuperable_y_mnt_iva_cero' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 2002,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '11111111-1',
                    'MntNeto'  => 50000,
                    'MntIVA'   => 0,
                    'IVANoRec' => [
                        ['CodIVANoRec' => 1, 'MntIVANoRec' => 9500],
                    ],
                    // 'MntTotal' => 59500, // Omitido a propósito para testear el cálculo del total.
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Detalle.MntIVA'                                        => 0,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotIVANoRec.CodIVANoRec' => 1,
        ],
    ],
    'build_simplificado_con_iva_uso_comun_y_mnt_iva_cero' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'      => 33,
                    'NroDoc'      => 2003,
                    'TasaImp'     => 19,
                    'FchDoc'      => '2024-01-10',
                    'RUTDoc'      => '11111111-1',
                    'MntNeto'     => 50000,
                    'MntIVA'      => 0,
                    'IVAUsoComun' => 9500,
                    // 'MntTotal' => 59500, // Omitido a propósito para testear el cálculo del total.
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Detalle.MntIVA'      => 0,
            'LibroCompraVenta.EnvioLibro.Detalle.IVAUsoComun' => 9500,
        ],
    ],
    'build_simplificado_sin_documentos' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion' => 'COMPRA',
            'LibroCompraVenta.EnvioLibro.Detalle'                => null,
        ],
    ],
];
