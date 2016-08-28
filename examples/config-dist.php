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
 * @file config-dist.php
 * Archivo de configuración para los ejemplos
 * ESTE ARCHIVO DEBE SER RENOMBRADO A config.php Y SU CONFIGURACIÓN AJUSTADA
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-08-01
 */

// configuración de la firma
// se puede omitir/comentar el archivo (índice file) y entregar directamente el
// contenido del archivo de la firma (índice data)
$config = [
    'firma' => [
        'file' => '/ruta/al/certificado.p12',
        //'data' => '', // contenido del archivo certificado.p12
        'pass' => 'contraseña',
    ],
];

// trabajar en ambiente de certificación
\sasco\LibreDTE\Sii::setAmbiente(\sasco\LibreDTE\Sii::CERTIFICACION);

// trabajar con maullin para certificación
\sasco\LibreDTE\Sii::setServidor('maullin');
