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

/**
 * Datos de impuestos adicionales (A) y retenciones (R).
 */
return [
    15 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido',
        'tasa' => 19,
    ],
    17 => [
        'tipo' => 'A',
        'glosa' => 'IVA anticipado faenamiento carne',
        'tasa' => 5,
    ],
    18 => [
        'tipo' => 'A',
        'glosa' => 'IVA anticipado carne',
        'tasa' => 5,
    ],
    19 => [
        'tipo' => 'A',
        'glosa' => 'IVA anticipado harina',
        'tasa' => 12,
    ],
    23 => [
        'tipo' => 'A',
        'glosa' => 'Art 37 letras A, B, C',
        'tasa' => 15,
    ],
    24 => [
        'tipo' => 'A',
        'glosa' => 'Licores, Piscos, Whisky', // Art 42 letra B
        'tasa' => 31.5,
    ],
    25 => [
        'tipo' => 'A',
        'glosa' => 'Vinos', // Art 42 letra C
        'tasa' => 20.5,
    ],
    26 => [
        'tipo' => 'A',
        'glosa' => 'Cervezas y Bebidas Alcoh.', // Art 42 letra C
        'tasa' => 20.5,
    ],
    27 => [
        'tipo' => 'A',
        'glosa' => 'Bebida Analcoh. y Mineral', // Art 42 letra A
        'tasa' => 10,
    ],
    271 => [
        'tipo' => 'A',
        'glosa' => 'Bebidas Azucaradas', // Art 42 letra A par. 2do
        'tasa' => 18,
    ],
    30 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido legumbres',
        'tasa' => 10,
    ],
    31 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido silvestres',
        'tasa' => 19,
    ],
    32 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido ganado',
        'tasa' => 8,
    ],
    33 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido madera',
        'tasa' => 8,
    ],
    34 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido trigo',
        'tasa' => 4,
    ],
    36 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido arroz',
        'tasa' => 10,
    ],
    37 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido hidrobiológicas',
        'tasa' => 10,
    ],
    38 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido chatarra',
        'tasa' => 19,
    ],
    39 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido PPA',
        'tasa' => 19,
    ],
    41 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido construcción',
        'tasa' => 19,
    ],
    44 => [
        'tipo' => 'A',
        'glosa' => 'Art 37 letras E, H, I, L',
        'tasa' => 15,
    ],
    45 => [
        'tipo' => 'A',
        'glosa' => 'Art 37 letra J',
        'tasa' => 50,
    ],
    47 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido cartones',
        'tasa' => 19,
    ],
    48 => [
        'tipo' => 'R',
        'glosa' => 'IVA retenido frambuesas y pasas',
        'tasa' => 14,
    ],
];
