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

/**
 * Códigos de datos principales asociados a aduanas.
 *
 * Existen códigos que tienen asociado un grupo de datos para sus valores, los
 * que están en su propio archivo de datos.
 */
return [
    // Estos grupos de códigos no son oficiales de aduanas, pero son útiles
    // para acceder a los datos asociados donde se necesiten.
    'paises' => [
        'glosa' => 'Países',
        'datos' => 'aduana_paises',
    ],
    'puertos' => [
        'glosa' => 'Puertos',
        'datos' => 'aduana_puertos',
    ],
    'unidades' => [
        'glosa' => 'Unidades',
        'datos' => 'aduana_unidades',
    ],
    // Códigos de aduana que tienen datos asociados a sus valores.
    'FmaPagExp' => [
        'glosa' => 'Forma pago exp.',
        'datos' => 'aduana_formas_de_pago',
    ],
    'CodModVenta' => [
        'glosa' => 'Mod. venta',
        'datos' => 'aduana_modalidades_de_venta',
    ],
    'CodClauVenta' => [
        'glosa' => 'Claú. venta',
        'datos' => 'aduana_clausulas_de_venta',
    ],
    'CodViaTransp' => [
        'glosa' => 'Transporte',
        'datos' => 'aduana_transportes',
    ],
    'TipoBultos' => [
        'glosa' => 'Bultos',
        'datos' => 'aduana_tipos_de_bulto',
    ],
    'CodPaisRecep' => [
        'glosa' => 'P. receptor',
        'datos' => 'aduana_paises',
    ],
    'CodPaisDestin' => [
        'glosa' => 'P. destino',
        'datos' => 'aduana_paises',
    ],
    'CodPtoEmbarque' => [
        'glosa' => 'Embarque',
        'datos' => 'aduana_puertos',
    ],
    'CodPtoDesemb' => [
        'glosa' => 'Desembarq.',
        'datos' => 'aduana_puertos',
    ],
    'CodUnidMedTara' => [
        'glosa' => 'U. tara',
        'datos' => 'aduana_unidades',
    ],
    'CodUnidPesoBruto' => [
        'glosa' => 'U. p. bruto',
        'datos' => 'aduana_unidades',
    ],
    'CodUnidPesoNeto' => [
        'glosa' => 'U. p. neto',
        'datos' => 'aduana_unidades',
    ],
    'Nacionalidad' => [
        'glosa' => 'Nacionalidad',
        'datos' => 'aduana_paises',
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
];
