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
 * Clase para acciones genéricas asociadas al SII de Chile
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-07-15
 */
class Sii
{

    private static $wsdl = [
        'url' => 'https://{servidor}.sii.cl/DTEWS/{servicio}.jws?WSDL',
        'servidor' => ['palena', 'maullin'], ///< servidores 0: producción, 1: certificación
    ];
    const PRODUCCION = 0; ///< Constante para indicar ambiente de producción
    const CERTIFICACION = 1; ///< Constante para indicar ambiente de desarrollo

    private static $retry = 10; ///< Veces que se reintentará conectar a SII al usar el servicio web

    /**
     * Método para obtener el WSDL
     *
     * \code{.php}
     *   $wsdl = \sasco\LibreDTE\Sii::wsdl('CrSeed'); // WSDL para pedir semilla
     * \endcode
     *
     * Para forzar el uso del WSDL de certificación hay dos maneras, una es
     * pasando un segundo parámetro al método get con valor Sii::CERTIFICACION:
     *
     * \code{.php}
     *   $wsdl = \sasco\LibreDTE\Sii::wsdl('CrSeed', \sasco\LibreDTE\Sii::CERTIFICACION);
     * \endcode
     *
     * La otra manera, para evitar este segundo parámetro, es crear la constante
     * _LibreDTE_CERTIFICACION_ con valor true antes de ejecutar cualquier
     * llamada a la biblioteca:
     *
     * \code{.php}
     *   define('_LibreDTE_CERTIFICACION_', true);
     * \endcode
     *
     * @param servicio Servicio por el cual se está solicitando su WSDL
     * @param ambiente Ambiente a usar: Sii::PRODUCCION o Sii::CERTIFICACION
     * @return URL del WSDL del servicio según ambiente solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-28
     */
    public static function wsdl($servicio, $ambiente = null)
    {
        // determinar ambiente que se debe usar
        if ($ambiente===null) {
            if (defined('_LibreDTE_CERTIFICACION_'))
                $ambiente = (int)_LibreDTE_CERTIFICACION_;
            else
                $ambiente = self::PRODUCCION;
        }
        // entregar WSDL local (modificados para ambiente de certificación)
        if ($ambiente==self::CERTIFICACION) {
            $wsdl = dirname(dirname(__FILE__)).'/wsdl/'.$servicio.'.jws';
            if (is_readable($wsdl))
                return $wsdl;
        }
        // entregar WSDL oficial desde SII
        return str_replace(
            ['{servidor}', '{servicio}'],
            [self::$wsdl['servidor'][$ambiente], $servicio],
            self::$wsdl['url']
        );
    }

    /**
     * Método para realizar una solicitud al servicio web del SII
     * @param wsdl Nombre del WSDL que se usará
     * @param request Nombre de la función que se ejecutará en el servicio web
     * @param args Argumentos que se pasarán al servicio web
     * @param retry Intentos que se realizarán como máximo para obtener respuesta
     * @return Objeto SimpleXMLElement con la espuesta del servicio web consultado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-28
     */
    public static function request($wsdl, $request, $args = null, $retry = null)
    {
        if (is_numeric($args)) {
            $retry = (int)$args;
            $args = null;
        }
        if (!$retry)
            $retry = self::$retry;
        if ($args and !is_array($args)) {
            $args = [$args];
        }
        $soap = new \SoapClient(self::wsdl($wsdl));
        for ($i=0; $i<$retry; $i++) {
            try {
                if ($args) {
                    $body = call_user_func_array([$soap, $request], $args);
                } else {
                    $body = $soap->$request();
                }
                break;
            } catch (\Exception $e) {
                print_r($e);
                $body = null;
            }
        }
        if ($body===null)
            return false;
        return new \SimpleXMLElement($body, LIBXML_COMPACT);
    }

    /**
     * Método que realiza el envío de un DTE al SII
     * Referencia: http://www.sii.cl/factura_electronica/factura_mercado/envio.pdf
     * @param usuario RUN del usuario que envía el DTE
     * @param empresa RUT de la empresa emisora del DTE
     * @param dte Documento XML con el DTE que se desea enviar a SII
     * @param token Token de autenticación automática ante el SII
     * @param retry Intentos que se realizarán como máximo para obtener respuesta
     * @return Respuesta XML desde SII o bien null si no se pudo obtener respuesta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-28
     */
    public static function enviar($usuario, $empresa, $dte, $token, $retry = null)
    {
        $url = 'https://maullin.sii.cl/cgi_dte/UPL/DTEUpload';
        list($rutSender, $dvSender) = explode('-', str_replace('.', '', $usuario));
        list($rutCompany, $dvCompany) = explode('-', str_replace('.', '', $empresa));
        do {
            $file = sys_get_temp_dir().'/dte_'.md5(microtime().$token.$dte).'.xml';
        } while (file_exists($file));
        file_put_contents($file, $dte);
        $data = [
            'rutSender' => $rutSender,
            'dvSender' => $dvSender,
            'rutCompany' => $rutCompany,
            'dvCompany' => $dvCompany,
            'archivo' => curl_file_create(
                $file,
                'application/xml',
                basename($file)
            ),
        ];
        $header = [
            'User-Agent: Mozilla/4.0 (compatible; PROG 1.0; Windows NT 5.0; YComp 5.0.2.4)',
            //'Referer: http://libredte.cl',
            'Cookie: TOKEN='.$token,
        ];
        if (!$retry)
            $retry = self::$retry;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        for ($i=0; $i<$retry; $i++) {
            $response = curl_exec($curl);
            if ($response and $response!='Error 500')
                break;
        }
        unlink($file);
        return ($response and $response!='Error 500') ? new \SimpleXMLElement($response, LIBXML_COMPACT) : false;
    }

}
