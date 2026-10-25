<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
