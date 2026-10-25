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
    'libro_boletas_base' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2021-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'    => 39,
                    'FolioDoc'  => 1,
                    'TpoServ'   => 3,
                    'FchEmiDoc' => '2021-01-10',
                    'MntTotal'  => 11900,
                ],
            ],
        ],
        'expected' => [
            'LibroBoleta.EnvioLibro.Caratula.RutEmisorLibro' => '76192083-9',
            'LibroBoleta.EnvioLibro.Caratula.RutEnvia' => '11222333-9',
            'LibroBoleta.EnvioLibro.Caratula.FchResol' => '2014-08-22',
            'LibroBoleta.EnvioLibro.Caratula.NroResol' => 80,
            'LibroBoleta.EnvioLibro.Caratula.TipoLibro' => 'ESPECIAL',
            'LibroBoleta.EnvioLibro.Caratula.TipoEnvio' => 'TOTAL',
            'LibroBoleta.EnvioLibro.Caratula.FolioNotificacion' => 1,
            'LibroBoleta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TpoDoc' => 39,
            'LibroBoleta.EnvioLibro.ResumenPeriodo.TotalesPeriodo.TotalesServicio.TpoServ' => 3,
        ],
    ],
    'con_boletas_afectas_y_exentas' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2021-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'    => 39,
                    'FolioDoc'  => 1,
                    'TpoServ'   => 3,
                    'FchEmiDoc' => '2021-01-10',
                    'MntTotal'  => 11900,
                ],
                [
                    'TpoDoc'    => 39,
                    'FolioDoc'  => 2,
                    'TpoServ'   => 3,
                    'FchEmiDoc' => '2021-01-11',
                    'MntTotal'  => 5950,
                ],
                [
                    'TpoDoc'    => 41,
                    'FolioDoc'  => 1,
                    'TpoServ'   => 3,
                    'FchEmiDoc' => '2021-01-12',
                    'MntExe'    => 3000,
                    'MntTotal'  => 3000,
                ],
            ],
        ],
        'expected' => [
            'LibroBoleta.EnvioLibro.ResumenPeriodo.TotalesPeriodo[0].TpoDoc' => 39,
            'LibroBoleta.EnvioLibro.ResumenPeriodo.TotalesPeriodo[1].TpoDoc' => 41,
        ],
    ],
    'agrupa_resumen_por_tpo_doc_y_tpo_serv' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2021-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'    => 39,
                    'FolioDoc'  => 1,
                    'TpoServ'   => 3,
                    'FchEmiDoc' => '2021-01-10',
                    'MntTotal'  => 11900,
                ],
                [
                    'TpoDoc'    => 39,
                    'FolioDoc'  => 2,
                    'TpoServ'   => 3,
                    'FchEmiDoc' => '2021-01-11',
                    'MntTotal'  => 5950,
                ],
            ],
        ],
        'expected' => [
            'LibroBoleta.EnvioLibro.ResumenPeriodo.TotalesPeriodo' => [
                'TpoDoc' => 39,
                'TotalesServicio' => [
                    'TpoServ' => 3,
                    'TotDoc' => 2,
                    'TotMntNeto' => 15000,
                    'TasaIVA' => 19,
                    'TotMntIVA' => 2850,
                    'TotMntTotal' => 17850,
                ],
            ],
        ],
    ],
    'libro_boletas_con_boletas_anuladas' => [
        'input' => [
            'caratula' => [
                'PeriodoTributario' => '2021-01',
            ],
            'detalle' => [
                [
                    'TpoDoc'    => 39,
                    'FolioDoc'  => 1,
                    'TpoServ'   => 3,
                    'FchEmiDoc' => '2021-01-10',
                    'MntTotal'  => 11900,
                ],
                [
                    'TpoDoc'    => 39,
                    'FolioDoc'  => 2,
                    'Anulado'   => 'A',
                    'TpoServ'   => 3,
                    'FchEmiDoc' => '2021-01-11',
                    'MntTotal'  => 0,
                ],
            ],
        ],
        'expected' => [
            'LibroBoleta.EnvioLibro.ResumenPeriodo.TotalesPeriodo' => [
                'TpoDoc' => 39,
                'TotAnulado' => 1,
                'TotalesServicio' => [
                    'TpoServ' => 3,
                    'TotDoc' => 1,
                    'TotMntNeto' => 10000,
                    'TasaIVA' => 19,
                    'TotMntIVA' => 1900,
                    'TotMntTotal' => 11900,
                ],
            ],
        ],
    ],
];
