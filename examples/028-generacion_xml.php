<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

/**
 * @file 028-generacion_xml.php
 *
 * Ejemplo que muestra lo simple que es generar un XML con la clase XML de
 * LibreDTE
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-08
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// xml como arreglo
$xml = [
    'familia' => [
        'papa' => [
            'nombre' => 'felipe',
            'edad' => 30,
        ],
        'mama' => [
            'nombre' => 'carolina',
            'edad' => 28,
        ],
        'hijos' => [
            'hijo' => [
                [
                    'nombre' => 'diego',
                    'edad' => 12,
                ],
                [
                    'nombre' => 'pedro',
                    'edad' => 14,
                ],
            ],
        ],
    ],
];

// generar XML
echo (new \sasco\LibreDTE\XML())->generate($xml)->saveXML();
