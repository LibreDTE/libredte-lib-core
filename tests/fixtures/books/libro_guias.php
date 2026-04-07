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
