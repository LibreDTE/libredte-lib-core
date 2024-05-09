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
 * @file 007-folios.php
 *
 * @version 2016-05-05
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// cargar folios
$Folios = new \libredte\lib\Sii\Folios(file_get_contents('xml/folios.xml'));

// ejemplos métodos
echo 'Folios son validos?: ',($Folios->check()?'si':'no'),"\n\n";
echo 'Rango de folios: ',$Folios->getDesde(),' al ',$Folios->getHasta(),"\n\n";
if ($Folios->getCaf())
    echo 'CAF: ',$Folios->getCaf()->C14N(),"\n\n";
echo $Folios->getPrivateKey(),"\n";
echo $Folios->getPublicKey();

// si hubo errores mostrar
foreach (\libredte\lib\Log::readAll() as $error)
    echo $error,"\n";
