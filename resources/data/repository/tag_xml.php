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

use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaClausulaVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaFormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaModalidadVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPais;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPuerto;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTipoBulto;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTransporte;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaUnidad;

/**
 * Traducción de nombres de Tag XML a glosa de las etiquetas de un XML de DTE.
 *
 * Existen etiquetas que tienen asociada una entidad para sus valores.
 * Diferentes etiquetas pueden compartir la misma entidad.
 *
 * Además existen unos códigos al final que no son etiquetas y funcionan como
 * "helpers" para acceder con un nombre fácil a esos datos.
 *
 * Nota: La referencia a "Aduana" es porque cada entidad utiliza los códigos que
 * Aduana de Chile a definido para el conjunto de dichos datos.
 */
return [

    // Etiquetas de Aduana que tienen datos asociados a sus valores.
    'FmaPagExp' => [
        'glosa' => 'Forma pago exp.',
        'entity' => AduanaFormaPago::class,
    ],
    'CodModVenta' => [
        'glosa' => 'Mod. venta',
        'entity' => AduanaModalidadVenta::class,
    ],
    'CodClauVenta' => [
        'glosa' => 'Claú. venta',
        'entity' => AduanaClausulaVenta::class,
    ],
    'CodViaTransp' => [
        'glosa' => 'Transporte',
        'entity' => AduanaTransporte::class,
    ],
    'TipoBultos' => [
        'glosa' => 'Bultos',
        'entity' => AduanaTipoBulto::class,
    ],
    'CodPaisRecep' => [
        'glosa' => 'P. receptor',
        'entity' => AduanaPais::class,
    ],
    'CodPaisDestin' => [
        'glosa' => 'P. destino',
        'entity' => AduanaPais::class,
    ],
    'CodPtoEmbarque' => [
        'glosa' => 'Embarque',
        'entity' => AduanaPuerto::class,
    ],
    'CodPtoDesemb' => [
        'glosa' => 'Desembarq.',
        'entity' => AduanaPuerto::class,
    ],
    'CodUnidMedTara' => [
        'glosa' => 'U. tara',
        'entity' => AduanaUnidad::class,
    ],
    'CodUnidPesoBruto' => [
        'glosa' => 'U. p. bruto',
        'entity' => AduanaUnidad::class,
    ],
    'CodUnidPesoNeto' => [
        'glosa' => 'U. p. neto',
        'entity' => AduanaUnidad::class,
    ],
    'Nacionalidad' => [
        'glosa' => 'Nacionalidad',
        'entity' => AduanaPais::class,
    ],

    // Códigos que no tienen un grupo de datos asociados, valores "libres".
    // Por lo que solo está el código con su correspondiente glosa.
    'TotClauVenta' => [
        'glosa' => 'Total claú.',
    ],
    'NombreTransp' => [
        'glosa' => 'Nomb. trans.',
    ],
    'RUTCiaTransp' => [
        'glosa' => 'RUT trans.',
    ],
    'NomCiaTransp' => [
        'glosa' => 'Comp. trans.',
    ],
    'IdAdicTransp' => [
        'glosa' => 'Trans. Ad.',
    ],
    'Booking' => [
        'glosa' => 'Booking',
    ],
    'Operador' => [
        'glosa' => 'Operador',
    ],
    'IdAdicPtoEmb' => [
        'glosa' => 'Embarq. Ad.',
    ],
    'IdAdicPtoDesemb' => [
        'glosa' => 'Desemb. Ad.',
    ],
    'Tara' => [
        'glosa' => 'Tara',
    ],
    'PesoBruto' => [
        'glosa' => 'Peso bruto',
    ],
    'PesoNeto' => [
        'glosa' => 'Peso neto',
    ],
    'TotItems' => [
        'glosa' => 'Items',
    ],
    'TotBultos' => [
        'glosa' => 'Total bultos',
    ],
    'MntFlete' => [
        'glosa' => 'Flete',
    ],
    'MntSeguro' => [
        'glosa' => 'Seguro',
    ],

    // Estos grupos de códigos no son oficiales del SII (ni Aduana), pero son útiles
    // para acceder a los datos asociados donde se necesiten.
    'pais' => [
        'glosa' => 'País',
        'entity' => AduanaPais::class,
    ],
    'puerto' => [
        'glosa' => 'Puerto',
        'entity' => AduanaPuerto::class,
    ],
    'unidad' => [
        'glosa' => 'Unidad',
        'entity' => AduanaUnidad::class,
    ],

];
