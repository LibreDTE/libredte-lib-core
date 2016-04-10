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
 * @file 019-firma_datos.php
 *
 * Ejemplo que muestra como obtener los datos de la persona dueña de la firma
 * electrónica
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-22
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// objeto de la firma
$Firma = new \sasco\LibreDTE\FirmaElectronica($config['firma']);

// mostrar datos de la persona dueña de la firma
echo 'RUN    : ',$Firma->getID(),"\n";
echo 'Nombre : ',$Firma->getName(),"\n";
echo 'Email  : ',$Firma->getEmail(),"\n";
echo 'Desde  : ',$Firma->getFrom(),"\n";
echo 'Hasta  : ',$Firma->getTo(),"\n";
echo 'Emisor : ',$Firma->getIssuer(),"\n\n\n";
print_r($Firma->getData());

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
