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
