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
    'con_guias_venta_y_traslado' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'Folio'    => 1,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'RznSoc'   => 'Cliente Venta SA',
                    'TpoOper'  => 1,
                    'MntNeto'  => 100000,
                    'TasaImp'  => 19,
                    'IVA'      => 19000,
                    'MntTotal' => 119000,
                ],
                [
                    'Folio'    => 2,
                    'FchDoc'   => '2024-01-15',
                    'RUTDoc'   => '12345678-9',
                    'RznSoc'   => 'Bodega Propia',
                    'TpoOper'  => 5,
                    'MntNeto'  => 50000,
                    'TasaImp'  => 0,
                    'IVA'      => 0,
                    'MntTotal' => 50000,
                ],
            ],
        ],
        'expected' => [
            'LibroGuia.EnvioLibro.Caratula.TipoLibro'          => 'ESPECIAL',
            'LibroGuia.EnvioLibro.ResumenPeriodo.TotGuiaVenta'    => 1,
            'LibroGuia.EnvioLibro.ResumenPeriodo.TotTraslado'     => [
                'TpoTraslado' => 5,
                'CantGuia' => 1,
                'MntGuia' => 50000,
            ],
        ],
    ],
    'con_guias_anuladas' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'Folio'    => 1,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'TpoOper'  => 1,
                    'MntNeto'  => 100000,
                    'TasaImp'  => 19,
                    'IVA'      => 19000,
                    'MntTotal' => 119000,
                ],
                [
                    'Folio'    => 2,
                    'Anulado'  => 1,
                    'FchDoc'   => '2024-01-11',
                    'RUTDoc'   => '12345678-9',
                    'MntTotal' => 0,
                ],
            ],
        ],
        'expected' => [
            'LibroGuia.EnvioLibro.ResumenPeriodo.TotFolAnulado' => 1,
        ],
    ],
    'calcula_montos_venta_correctamente' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'Folio'    => 1,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'TpoOper'  => 1,
                    'MntNeto'  => 100000,
                    'TasaImp'  => 19,
                    'IVA'      => 19000,
                    'MntTotal' => 119000,
                ],
                [
                    'Folio'    => 2,
                    'FchDoc'   => '2024-01-20',
                    'RUTDoc'   => '98765432-1',
                    'TpoOper'  => 1,
                    'MntNeto'  => 80000,
                    'TasaImp'  => 19,
                    'IVA'      => 15200,
                    'MntTotal' => 95200,
                ],
            ],
        ],
        'expected' => [
            'LibroGuia.EnvioLibro.ResumenPeriodo.TotGuiaVenta'    => 2,
            'LibroGuia.EnvioLibro.ResumenPeriodo.TotMntGuiaVta'   => 214200,
        ],
    ],
    'validar_esquema_y_firma' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2024-01',
            ],
            'detalle' => [
                [
                    'Folio'    => 1,
                    'FchDoc'   => '2024-01-10',
                    'RUTDoc'   => '12345678-9',
                    'TpoOper'  => 1,
                    'MntNeto'  => 100000,
                    'TasaImp'  => 19,
                    'IVA'      => 19000,
                    'MntTotal' => 119000,
                ],
            ],
        ],
        'expected' => [
            'LibroGuia.EnvioLibro.Caratula.TipoLibro' => 'ESPECIAL',
        ],
    ],
];
