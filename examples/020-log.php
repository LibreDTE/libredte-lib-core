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
 * @file 020-log.php
 *
 * Ejemplo que muestra como usar la clase para Logs
 *
 * IMPORTANTE cada llamada a:
 *  - \libredte\lib\Log::read() recupera y borra el último log
 *  - \libredte\lib\Log::readAll() recupera y borra todos los logs
 *
 * Por defecto los logs se guardan y leen como LOG_ERR (código de syslog), sin
 * embargo podrían haber otros mensajes en otras niveles que indiquen alguna
 * otra cosa.
 *
 * @version 2015-09-17
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// generar mensajes en el log:
// El método \libredte\lib\Log::write() debe ser usado solo por
// desarrolladores de la biblioteca, desarrolladores que solo usen la biblioteca
// no crearán mensajes en la bitácora, solo la leerán. Si un desarrollador
// quiere usar un sistema de logs para su proyecto debe tener su sistema propio.
// La clase \libredte\lib\Log es exclusiva para LibreDTE (al menos para eso
// fue diseñada)
class GeneradorErrores {
    public function caso1()
    {
        \libredte\lib\Log::write('Hola, esto es un error');
        $this->caso1a();
        \libredte\lib\Log::write('Chao, no hay más errores, soy el último');
    }
    public function caso1a()
    {
        \libredte\lib\Log::write('Hola de nuevo, esto es otro error');
        \libredte\lib\Log::write('Penúltimo error');
    }
    public function caso2()
    {
        for ($i=0; $i<5; $i++)
            \libredte\lib\Log::write('Soy el error '.($i+1));
    }
    public function caso3()
    {
        \libredte\lib\Log::write(\libredte\lib\Estado::ENVIO_USUARIO_INCORRECTO, \libredte\lib\Estado::get(\libredte\lib\Estado::ENVIO_USUARIO_INCORRECTO));
        \libredte\lib\Log::write(\libredte\lib\Estado::ENVIO_ERROR_XML, \libredte\lib\Estado::get(\libredte\lib\Estado::ENVIO_ERROR_XML, 'No hay XML'));
        \libredte\lib\Log::write(\libredte\lib\Estado::REQUEST_ERROR_BODY, \libredte\lib\Estado::get(\libredte\lib\Estado::REQUEST_ERROR_BODY, 'getToken', 10));
        \libredte\lib\Log::write(\libredte\lib\Estado::ENVIO_NO_AUTENTICADO, \libredte\lib\Estado::get(\libredte\lib\Estado::ENVIO_NO_AUTENTICADO));
    }
}
$GeneradorErrores = new GeneradorErrores();

// ejecutar caso 1
$GeneradorErrores->caso1();

// obtener el error más reciente y usar como string
echo \libredte\lib\Log::read(),"\n\n";

// mostrar el resto de errores y usar como string
foreach (\libredte\lib\Log::readAll() as $error)
    echo $error,"\n";
echo "\n\n\n";

// ejecutar caso 2
$GeneradorErrores->caso2();

// obtener el error más reciente y usar como objeto
$error = \libredte\lib\Log::read();
echo 'error código: ',$error->code,' y mensaje ',$error->msg,"\n\n";

// mostrar el resto de errores y usar como objeto
foreach (\libredte\lib\Log::readAll() as $error)
    echo 'error código: ',$error->code,' y mensaje ',$error->msg,"\n";
echo "\n\n\n";

// ejecutar caso 3 y mostrar en español todos los mensajes
$GeneradorErrores->caso3();
foreach (\libredte\lib\Log::readAll() as $error)
    echo $error,"\n";
echo "\n\n\n";

// ejecutar caso 3 y mostrar en español todos los mensajes pero como objetos
$GeneradorErrores->caso3();
foreach (\libredte\lib\Log::readAll() as $error)
    echo 'error código: ',$error->code,' y mensaje ',$error->msg,"\n";
echo "\n\n\n";

// ejecutar caso 3 y mostrar en inglés todos los mensajes
\libredte\lib\I18n::setIdioma('en'); // idioma se debe asignar antes que se registre cualquier mensaje en el Log
$GeneradorErrores->caso3();
foreach (\libredte\lib\Log::readAll() as $error)
    echo $error,"\n";
