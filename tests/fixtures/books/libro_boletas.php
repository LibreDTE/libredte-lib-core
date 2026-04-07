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
