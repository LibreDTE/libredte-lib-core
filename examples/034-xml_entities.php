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
 * @file 034-xml_entities.php
 *
 * Ejemplo de XML entities y su conversión con la clase XML
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-01-20
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// caso A: generar XML
$string1 = '< > & " \'';
$XML1 = (new \sasco\LibreDTE\XML())->generate([
    'nodo1' => [
        'nodo2'=>[
            '@attributes' => [
                'id' => 'ID123',
            ],
            'nodo3'=>$string1
        ],
    ],
]);
echo str_repeat('=', 50)."\n";
echo 'Caso A: generar XML'."\n\n";
echo '-- string1 --'."\n";
echo $string1."\n\n";
echo '-- textContent --'."\n";
echo $XML1->textContent."\n\n";
echo '-- saveXML() --'."\n";
echo $XML1->saveXML()."\n";
echo '-- C14N() --'."\n";
echo $XML1->C14N()."\n\n";
echo '-- getFlattened() --'."\n";
echo $XML1->getFlattened()."\n\n";
echo '-- getFlattened(\'/nodo1/nodo2\') --'."\n";
echo $XML1->getFlattened('/nodo1/nodo2')."\n\n";

// caso B: leer XML
$string2 = '&lt; &gt; &amp; &quot; &apos;';
$XML2 = new \sasco\LibreDTE\XML();
$XML2->loadXML('<?xml version="1.0" encoding="ISO-8859-1"?><nodo1><nodo2 id="ID123"><nodo3>'.$string2.'</nodo3></nodo2></nodo1>', LIBXML_NOENT);
echo str_repeat('=', 50)."\n";
echo 'Caso B: leer XML'."\n\n";
echo '-- string2 --'."\n";
echo $string2."\n\n";
echo '-- textContent --'."\n";
echo $XML2->textContent."\n\n";
echo '-- saveXML() --'."\n";
echo $XML2->saveXML()."\n";
echo '-- C14N() --'."\n";
echo $XML2->C14N()."\n\n";
echo '-- getFlattened() --'."\n";
echo $XML2->getFlattened()."\n\n";
echo '-- getFlattened(\'/nodo1/nodo2\') --'."\n";
echo $XML2->getFlattened('/nodo1/nodo2')."\n\n";

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error, "\n";
