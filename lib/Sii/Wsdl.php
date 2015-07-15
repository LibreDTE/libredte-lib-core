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

namespace sasco\LibreDTE;

/**
 * Clase para obtener el WSDL correcto según ambiente, producción o
 * certificación, que se esté utilizando.
 *
 * Provee sólo el método estático get(). Modo de uso:
 *
 * \code{.php}
 *   include_once 'sasco/libredte/lib/Sii/WSDL.php';
 *   $wsdl = \sasco\LibreDTE\Sii_WSDL::get('CrSeed'); // WSDL para pedir semilla
 * \endcode
 *
 * Para forzar el uso del WSDL de certificación hay dos maneras, una es pasando
 * un segundo parámetro al método get con valor Sii_WSDL::CERTIFICACION:
 *
 * \code{.php}
 *   $wsdl = \sasco\LibreDTE\Sii_WSDL::get('CrSeed', Sii_WSDL::CERTIFICACION);
 * \endcode
 *
 * La otra, para evitar este segundo parámetro, es crear la constante
 * _LibreDTE_CERTIFICACION_ con valor true antes de ejecutar cualquier llamada a
 * la biblioteca:
 *
 * \code{.php}
 *   define('_LibreDTE_CERTIFICACION_', true);
 * \endcode
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2014-12-18
 */
class Sii_Wsdl
{

    private static $wsdl = [
        'CrSeed' => 'https://{server}.sii.cl/DTEWS/CrSeed.jws?WSDL',
        'GetTokenFromSeed' => 'https://{server}.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL',
    ]; ///< WSDLs con el servidor como la variable {server}
    private static $server = ['palena', 'maullin']; ///< servidores 0: producción, 1: certificación
    const PRODUCCION = 0; ///< Constante para indicar ambiente de producción
    const CERTIFICACION = 1; ///< Constante para indicar ambiente de desarrollo

    /**
     * Método para obtener el WSDL
     * @param servicio Servicio por el cual se está solicitando su WSDL
     * @param ambiente Ambiente a usar: Sii_WSDL::PRODUCCION o Sii_WSDL::CERTIFICACION
     * @return URL del WSDL del servicio según ambiente solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-18
     */
    public static function get($servicio, $ambiente = null)
    {
        // determinar ambiente que se debe usar
        if ($ambiente===null) {
            if (defined('_LibreDTE_CERTIFICACION_'))
                $ambiente = (int)_LibreDTE_CERTIFICACION_;
            else
                $ambiente = self::PRODUCCION;
        }
        // entregar WSDL
        return str_replace(
            '{server}',
            self::$server[$ambiente],
            self::$wsdl[$servicio]
        );
    }

}
