<?php

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
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
 * @file 025-guia_despacho.php
 *
 * Ejemplo que genera el JSON del set de pruebas guía de despacho.
 * Este JSON se utiliza luego para generar el EnvioDTE utilizando un ejemplo
 * similar al 010-set_pruebas_basico
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// folios a usar para el set de pruebas
$folios = [
    52 => 4,
];

// obtener JSON del set de pruebas
echo \libredte\lib\Sii\Certificacion\SetPruebas::getJSON(
    file_get_contents('set_pruebas/005-guia_despacho.txt'), $folios
);
