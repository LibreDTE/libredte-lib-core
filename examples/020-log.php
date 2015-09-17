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
 * @file 020-log.php
 *
 * Ejemplo que muestra como usar la clase para Logs
 *
 * IMPORTANTE cada llamada a:
 *  - \sasco\LibreDTE\Log::read() recupera y borra el último log
 *  - \sasco\LibreDTE\Log::readAll() recupera y borra todos los logs
 *
 * Por defecto los logs se guardan y leen como LOG_ERR (código de syslog), sin
 * embargo podrían haber otros mensajes en otras niveles que indiquen alguna
 * otra cosa.
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-17
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// generar mensajes en el log:
// El método \sasco\LibreDTE\Log::write() debe ser usado sólo por
// desarrolladores de la biblioteca, desarrolladores que sólo usen la biblioteca
// no crearán mensajes en la bitácora, sólo la leerán. Si un desarrollador
// quiere usar un sistema de logs para su proyecto debe tener su sistema propio.
// La clase \sasco\LibreDTE\Log es exclusiva para LibreDTE (al menos para eso
// fue diseñada)
class GeneradorErrores {
    public function caso1()
    {
        \sasco\LibreDTE\Log::write('Hola, esto es un error');
        $this->caso1a();
        \sasco\LibreDTE\Log::write('Chao, no hay más errores, soy el último');
    }
    public function caso1a()
    {
        \sasco\LibreDTE\Log::write('Hola de nuevo, esto es otro error');
        \sasco\LibreDTE\Log::write('Penúltimo error');
    }
    public function caso2()
    {
        for ($i=0; $i<5; $i++)
            \sasco\LibreDTE\Log::write('Soy el error '.($i+1));
    }
    public function caso3()
    {
        \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::ENVIO_USUARIO_INCORRECTO, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIO_USUARIO_INCORRECTO));
        \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::ENVIO_ERROR_XML, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIO_ERROR_XML, 'No hay XML'));
        \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::REQUEST_ERROR_BODY, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::REQUEST_ERROR_BODY, 'getToken', 10));
        \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::ENVIO_NO_AUTENTICADO, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIO_NO_AUTENTICADO));
    }
}
$GeneradorErrores = new GeneradorErrores();

// ejecutar caso 1
$GeneradorErrores->caso1();

// obtener el error más reciente y usar como string
echo \sasco\LibreDTE\Log::read(),"\n\n";

// mostrar el resto de errores y usar como string
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
echo "\n\n\n";

// ejecutar caso 2
$GeneradorErrores->caso2();

// obtener el error más reciente y usar como objeto
$error = \sasco\LibreDTE\Log::read();
echo 'error código: ',$error->code,' y mensaje ',$error->msg,"\n\n";

// mostrar el resto de errores y usar como objeto
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo 'error código: ',$error->code,' y mensaje ',$error->msg,"\n";
echo "\n\n\n";

// ejecutar caso 3 y mostrar en español todos los mensajes
$GeneradorErrores->caso3();
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
echo "\n\n\n";

// ejecutar caso 3 y mostrar en español todos los mensajes pero como objetos
$GeneradorErrores->caso3();
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo 'error código: ',$error->code,' y mensaje ',$error->msg,"\n";
echo "\n\n\n";

// ejecutar caso 3 y mostrar en inglés todos los mensajes
\sasco\LibreDTE\I18n::setIdioma('en'); // idioma se debe asignar antes que se registre cualquier mensaje en el Log
$GeneradorErrores->caso3();
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";
