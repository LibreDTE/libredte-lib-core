<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

/**
 * @file 018-generar_pdf.php
 *
 * Ejemplo que genera los documentos PDF de los DTE a partir del XML de EnvioDTE
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-11-28
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// sin límite de tiempo para generar documentos
set_time_limit(0);

// archivo XML de EnvioDTE que se generará
$archivo = 'xml/certificado/set_pruebas/set_pruebas_basico.xml';
//$archivo = 'xml/certificado/set_pruebas/set_pruebas_factura_exenta.xml';
//$archivo = 'xml/certificado/etapa_simulacion.xml';

// Cargar EnvioDTE y extraer arreglo con datos de carátula y DTEs
$EnvioDte = new \sasco\LibreDTE\Sii\EnvioDte();
$EnvioDte->loadXML(file_get_contents($archivo));
$Caratula = $EnvioDte->getCaratula();
$Documentos = $EnvioDte->getDocumentos();

// directorio temporal para guardar los PDF
$dir = sys_get_temp_dir().'/dte_'.$Caratula['RutEmisor'].'_'.$Caratula['RutReceptor'].'_'.str_replace(['-', ':', 'T'], '', $Caratula['TmstFirmaEnv']);
if (is_dir($dir))
    \sasco\LibreDTE\File::rmdir($dir);
if (!mkdir($dir))
    die('No fue posible crear directorio temporal para DTEs');

// procesar cada DTEs e ir agregándolo al PDF
foreach ($Documentos as $DTE) {
    if (!$DTE->getDatos())
        die('No se pudieron obtener los datos del DTE');
    $pdf = new \sasco\LibreDTE\Sii\PDF\Dte(false); // =false hoja carta, =true papel contínuo (false por defecto si no se pasa)
    $pdf->setFooterText();
    $pdf->setLogo('/home/delaf/www/localhost/dev/pages/sasco/website/webroot/img/logo_mini.png'); // debe ser PNG!
    $pdf->setResolucion(['FchResol'=>$Caratula['FchResol'], 'NroResol'=>$Caratula['NroResol']]);
    //$pdf->setCedible(true);
    $pdf->agregar($DTE->getDatos(), $DTE->getTED());
    $pdf->Output($dir.'/dte_'.$Caratula['RutEmisor'].'_'.$DTE->getID().'.pdf', 'F');
}

// entregar archivo comprimido que incluirá cada uno de los DTEs
\sasco\LibreDTE\File::compress($dir, ['format'=>'zip', 'delete'=>true]);
