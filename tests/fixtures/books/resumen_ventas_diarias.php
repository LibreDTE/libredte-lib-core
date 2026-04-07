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
    'con_un_solo_folio' => [
        'input' => [
            'caratula' => [
                'FchInicio' => '2024-01-10',
                'FchFinal' => '2024-01-10',
            ],
            'detalle' => [
                [
                    'TpoDoc'   => 39,
                    'NroDoc'   => 1,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'MntTotal' => 11900,
                ],
            ],
        ],
        'expected' => [
            'ConsumoFolios.DocumentoConsumoFolios.Caratula.RutEmisor' => '76192083-9',
            'ConsumoFolios.DocumentoConsumoFolios.Caratula.FchInicio' => '2024-01-10',
            'ConsumoFolios.DocumentoConsumoFolios.Caratula.FchFinal'  => '2024-01-10',
            'ConsumoFolios.DocumentoConsumoFolios.Resumen.TipoDocumento' => 39,
            'ConsumoFolios.DocumentoConsumoFolios.Resumen.FoliosEmitidos' => 1,
        ],
    ],
    'con_boletas_de_un_dia' => [
        'input' => [
            'caratula' => [
                'FchInicio' => '2024-01-10',
                'FchFinal' => '2024-01-10',
            ],
            'detalle' => [
                [
                    'TpoDoc'   => 39,
                    'NroDoc'   => 1,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'MntNeto'  => 10000,
                    'MntIVA'   => 1900,
                    'MntTotal' => 11900,
                ],
                [
                    'TpoDoc'   => 39,
                    'NroDoc'   => 2,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-10',
                    'MntNeto'  => 5000,
                    'MntIVA'   => 950,
                    'MntTotal' => 5950,
                ],
                [
                    'TpoDoc'   => 41,
                    'NroDoc'   => 1,
                    'TasaImp'  => 0,
                    'FchDoc'   => '2024-01-10',
                    'MntExe'   => 2000,
                    'MntTotal' => 2000,
                ],
            ],
        ],
        'expected' => [
            'ConsumoFolios.DocumentoConsumoFolios.Caratula.FchInicio' => '2024-01-10',
            'ConsumoFolios.DocumentoConsumoFolios.Caratula.FchFinal'  => '2024-01-10',
            'ConsumoFolios.DocumentoConsumoFolios.Resumen[0].TipoDocumento' => 39,
            'ConsumoFolios.DocumentoConsumoFolios.Resumen[0].FoliosEmitidos' => 2,
            'ConsumoFolios.DocumentoConsumoFolios.Resumen[1].TipoDocumento' => 41,
            'ConsumoFolios.DocumentoConsumoFolios.Resumen[1].FoliosEmitidos' => 1,
        ],
    ],
    'calcula_fechas_inicio_y_fin' => [
        'input' => [
            'detalle' => [
                [
                    'TpoDoc'   => 39,
                    'NroDoc'   => 5,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-15',
                    'MntTotal' => 11900,
                ],
                [
                    'TpoDoc'   => 39,
                    'NroDoc'   => 6,
                    'TasaImp'  => 19,
                    'FchDoc'   => '2024-01-20',
                    'MntTotal' => 5950,
                ],
            ],
        ],
        'expected' => [
            'ConsumoFolios.DocumentoConsumoFolios.Caratula.FchInicio' => '2024-01-15',
            'ConsumoFolios.DocumentoConsumoFolios.Caratula.FchFinal'  => '2024-01-20',
        ],
    ],
    'agrupa_folios_en_rangos_continuos' => [
        'input' => [
            'caratula' => [
                'FchInicio' => '2024-01-10',
                'FchFinal' => '2024-01-10',
            ],
            'detalle' => [
                ['TpoDoc' => 39, 'NroDoc' => 1, 'FchDoc' => '2024-01-10', 'MntTotal' => 1000],
                ['TpoDoc' => 39, 'NroDoc' => 2, 'FchDoc' => '2024-01-10', 'MntTotal' => 1000],
                ['TpoDoc' => 39, 'NroDoc' => 3, 'FchDoc' => '2024-01-10', 'MntTotal' => 1000],
                // Folio 5 rompe el rango continuo (falta el 4).
                ['TpoDoc' => 39, 'NroDoc' => 5, 'FchDoc' => '2024-01-10', 'MntTotal' => 1000],
            ],
        ],
        'expected' => [
            'ConsumoFolios.DocumentoConsumoFolios.Resumen.TipoDocumento' => 39,
            'ConsumoFolios.DocumentoConsumoFolios.Resumen.FoliosEmitidos' => 4,
            'ConsumoFolios.DocumentoConsumoFolios.Resumen.RangoUtilizados[0].Inicial' => 1,
            'ConsumoFolios.DocumentoConsumoFolios.Resumen.RangoUtilizados[0].Final' => 3,
            'ConsumoFolios.DocumentoConsumoFolios.Resumen.RangoUtilizados[1].Inicial' => 5,
            'ConsumoFolios.DocumentoConsumoFolios.Resumen.RangoUtilizados[1].Final' => 5,
        ],
    ],
];
