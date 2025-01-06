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
