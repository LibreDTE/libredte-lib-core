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
    'build_con_documentos' => [
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
                    'MntTotal' => 238000,
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion'               => 'COMPRA',
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TpoDoc' => 33,
        ],
    ],
    'con_iva_no_recuperable' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 2001,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '11111111-1',
                    'MntNeto'  => 50000,
                    'IVANoRec' => [
                        ['CodIVANoRec' => 1, 'MntIVANoRec' => 9500],
                    ],
                    'MntTotal' => 59500,
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotIVANoRec.CodIVANoRec' => 1,
        ],
    ],
    'compras_y_ventas_misma_entidad' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion' => 'COMPRA',
            'LibroCompraVenta.EnvioLibro.Detalle' => null,
        ],
    ],
    'con_iva_retenido_total' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'      => 46,
                    'NroDoc'      => 3001,
                    'TasaImp'     => 19,
                    'FchDoc'      => '2024-01-15',
                    'RUTDoc'      => '76192083-9',
                    'MntNeto'     => 100000,
                    'MntIVA'      => 19000,
                    'OtrosImp'    => [
                        ['CodImp' => 15, 'TasaImp' => 19, 'MntImp' => 19000],
                    ],
                    'IVARetTotal' => 19000,
                    'MntTotal'    => 100000,
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion'                                    => 'COMPRA',
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TpoDoc'                      => 46,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotDoc'                      => 1,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotMntNeto'                  => 100000,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotMntIVA'                   => 19000,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotOtrosImp.CodImp'          => 15,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotOtrosImp.TotMntImp'       => 19000,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotIVARetTotal'              => 19000,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotMntTotal'                 => 100000,
        ],
    ],
    'con_iva_retenido_parcial' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'        => 46,
                    'NroDoc'        => 3002,
                    'TasaImp'       => 19,
                    'FchDoc'        => '2024-01-15',
                    'RUTDoc'        => '76192083-9',
                    'MntNeto'       => 100000,
                    'MntIVA'        => 19000,
                    'OtrosImp'      => [
                        ['CodImp' => 16, 'TasaImp' => 19, 'MntImp' => 9500],
                    ],
                    'IVARetParcial' => 9500,
                    'MntTotal'      => 109500,
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion'                                    => 'COMPRA',
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TpoDoc'                      => 46,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotDoc'                      => 1,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotMntNeto'                  => 100000,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotMntIVA'                   => 19000,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotOtrosImp.CodImp'          => 16,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotOtrosImp.TotMntImp'       => 9500,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotIVARetParcial'            => 9500,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotMntTotal'                 => 109500,
        ],
    ],
    'validar_esquema_y_firma' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 1,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'MntNeto'  => 100000,
                    'MntIVA'   => 19000,
                    'MntTotal' => 119000,
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion'               => 'COMPRA',
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TpoDoc' => 33,
        ],
    ],
];
