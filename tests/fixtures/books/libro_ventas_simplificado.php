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
                'TipoOperacion'     => 'VENTA',
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
            ],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion'               => 'VENTA',
            'LibroCompraVenta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TpoDoc' => 33,
        ],
    ],
    'build_simplificado_sin_documentos' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
                'TipoOperacion'     => 'VENTA',
            ],
            'detalle' => [],
        ],
        'expected' => [
            'LibroCompraVenta.EnvioLibro.Caratula.TipoOperacion' => 'VENTA',
            'LibroCompraVenta.EnvioLibro.Detalle'                => null,
        ],
    ],
];
