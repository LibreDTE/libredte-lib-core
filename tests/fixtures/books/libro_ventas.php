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
    'build_con_documentos_afectos' => [
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
                    'RznSoc'   => 'Empresa Compradora SpA',
                    'MntNeto'  => 100000,
                    'MntIVA'   => 19000,
                    'MntTotal' => 119000,
                ],
                [
                    'TpoDoc'   => 33,
                    'NroDoc'   => 2,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-20',
                    'RUTDoc'   => '98765432-1',
                    'RznSoc'   => 'Otro Cliente Ltda',
                    'MntNeto'  => 50000,
                    'MntIVA'   => 9500,
                    'MntTotal' => 59500,
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion'               => 'VENTA',
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TpoDoc' => 33,
        ],
    ],
    'calcula_totales_correctamente' => [
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
                [
                    'TpoDoc'   => 61,
                    'NroDoc'   => 1,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-15',
                    'RUTDoc'   => '12345678-9',
                    'MntNeto'  => -20000,
                    'MntIVA'   => -3800,
                    'MntTotal' => -23800,
                ],
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo[0].TpoDoc' => 33,
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo[1].TpoDoc' => 61,
        ],
    ],
    'sin_detalles' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion' => 'VENTA',
            'LibroCompraVenta.EnvioLibro.Detalle' => null,
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
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion'               => 'VENTA',
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TpoDoc' => 33,
        ],
    ],
];
