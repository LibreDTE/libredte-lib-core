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

namespace sasco\LibreDTE;

/**
 * Clase para acciones genéricas asociadas al SII de Chile
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-09-11
 */
class Sii
{

    private static $config = [
        'wsdl' => [
            '*' => 'https://{servidor}.sii.cl/DTEWS/{servicio}.jws?WSDL',
            'QueryEstDteAv' => 'https://{servidor}.sii.cl/DTEWS/services/{servicio}?WSDL',
            'wsDTECorreo' => 'https://{servidor}.sii.cl/DTEWS/services/{servicio}?WSDL',
        ],
        'servidor' => ['palena', 'maullin'], ///< servidores 0: producción, 1: certificación
        'certs' => [300, 100], ///< certificados 0: producción, 1: certificación
    ];

    const PRODUCCION = 0; ///< Constante para indicar ambiente de producción
    const CERTIFICACION = 1; ///< Constante para indicar ambiente de desarrollo

    const IVA = 19; ///< Tasa de IVA

    private static $retry = 10; ///< Veces que se reintentará conectar a SII al usar el servicio web
    private static $verificar_ssl = true; ///< Indica si se deberá verificar o no el certificado SSL del SII
    private static $ambiente = self::PRODUCCION; ///< Ambiente que se utilizará

    private static $direcciones_regionales = [
        'CHILLÁN VIEJO' => 'CHILLÁN',
        'HUECHURABA' => 'SANTIAGO NORTE',
        'LA CISTERNA' => 'SANTIAGO SUR',
        'LAS CONDES' => 'SANTIAGO ORIENTE',
        'LO ESPEJO' => 'SANTIAGO SUR',
        'PEÑALOLÉN' => 'ÑUÑOA',
        'PUDAHUEL' => 'SANTIAGO PONIENTE',
        'RECOLETA' => 'SANTIAGO NORTE',
        'SANTIAGO' => 'SANTIAGO CENTRO',
        'SAN MIGUEL' => 'SANTIAGO SUR',
        'SAN VICENTE' => 'SAN VICENTE TAGUA TAGUA',
        'TALTAL' => 'ANTOFAGASTA',
        'VITACURA' => 'SANTIAGO ORIENTE',
        'VICHUQUÉN' => 'CURICÓ',
    ]; /// Direcciones regionales del SII según la comuna

    /**
     * Método que permite asignar el nombre del servidor del SII que se
     * usará para las consultas al SII
     * @param servidor Servidor que se usará, si es https://maullin2.sii.cl, entonces se debe pasar como valor maullin2
     * @param certificacion Permite definir si se está cambiando el servidor de certificación o el de producción
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-14
     */
    public static function setServidor($servidor = 'maullin', $certificacion = Sii::CERTIFICACION)
    {
        self::$config['servidor'][$certificacion] = $servidor;
    }

    /**
     * Método que entrega el nombre del servidor a usar según el ambiente
     * @param ambiente Ambiente que se desea obtener el servidor, si es null se autodetectará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-01
     */
    public static function getServidor($ambiente = null)
    {
        return self::$config['servidor'][self::getAmbiente($ambiente)];
    }

    /**
     * Método que entrega la URL de un recurso en el SII según el ambiente que se esté usando
     * @param recurso Recurso del sitio del SII que se desea obtener la URL
     * @param ambiente Ambiente que se desea obtener el servidor, si es null se autodetectará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-11
     */
    public static function getURL($recurso, $ambiente = null)
    {
        return 'https://'.self::getServidor($ambiente).'.sii.cl'.$recurso;
    }

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
     * La otra manera, para evitar este segundo parámetro, es asignar el valor a
     * través de la configuración:
     *
     * \code{.php}
     *   \sasco\LibreDTE\Sii::setAmbiente(\sasco\LibreDTE\Sii::CERTIFICACION);
     * \endcode
     *
     * @param servicio Servicio por el cual se está solicitando su WSDL
     * @param ambiente Ambiente a usar: Sii::PRODUCCION o Sii::CERTIFICACION o null (para detección automática)
     * @return URL del WSDL del servicio según ambiente solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-06-11
     */
    public static function wsdl($servicio, $ambiente = null)
    {
        // determinar ambiente que se debe usar
        $ambiente = self::getAmbiente($ambiente);
        // entregar WSDL local (modificados para ambiente de certificación)
        if ($ambiente==self::CERTIFICACION) {
            $wsdl = dirname(dirname(__FILE__)).'/wsdl/'.self::$config['servidor'][$ambiente].'/'.$servicio.'.jws';
            if (is_readable($wsdl))
                return $wsdl;
        }
        // entregar WSDL oficial desde SII
        $location = isset(self::$config['wsdl'][$servicio]) ? self::$config['wsdl'][$servicio] : self::$config['wsdl']['*'];
        $wsdl = str_replace(
            ['{servidor}', '{servicio}'],
            [self::$config['servidor'][$ambiente], $servicio],
            $location
        );
        // entregar wsdl
        return $wsdl;
    }

    /**
     * Método para realizar una solicitud al servicio web del SII
     * @param wsdl Nombre del WSDL que se usará
     * @param request Nombre de la función que se ejecutará en el servicio web
     * @param args Argumentos que se pasarán al servicio web
     * @param retry Intentos que se realizarán como máximo para obtener respuesta
     * @return Objeto SimpleXMLElement con la espuesta del servicio web consultado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-28
     */
    public static function request($wsdl, $request, $args = null, $retry = null)
    {
        if (is_numeric($args)) {
            $retry = (int)$args;
            $args = null;
        }
        if (!$retry)
            $retry = self::$retry;
        if ($args and !is_array($args))
            $args = [$args];
        if (!self::$verificar_ssl) {
            if (self::getAmbiente()==self::PRODUCCION) {
                $msg = Estado::get(Estado::ENVIO_SSL_SIN_VERIFICAR);
                \sasco\LibreDTE\Log::write(Estado::ENVIO_SSL_SIN_VERIFICAR, $msg, LOG_WARNING);
            }
            $options = ['stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ])];
        } else {
            $options = [];
        }
        try {
            $soap = new \SoapClient(self::wsdl($wsdl), $options);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (isset($e->getTrace()[0]['args'][1]) and is_string($e->getTrace()[0]['args'][1])) {
                $msg .= ': '.$e->getTrace()[0]['args'][1];
            }
            \sasco\LibreDTE\Log::write(Estado::REQUEST_ERROR_SOAP, Estado::get(Estado::REQUEST_ERROR_SOAP, $msg));
            return false;
        }
        for ($i=0; $i<$retry; $i++) {
            try {
                if ($args) {
                    $body = call_user_func_array([$soap, $request], $args);
                } else {
                    $body = $soap->$request();
                }
                break;
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                if (isset($e->getTrace()[0]['args'][1]) and is_string($e->getTrace()[0]['args'][1])) {
                    $msg .= ': '.$e->getTrace()[0]['args'][1];
                }
                \sasco\LibreDTE\Log::write(Estado::REQUEST_ERROR_SOAP, Estado::get(Estado::REQUEST_ERROR_SOAP, $msg));
                $body = null;
            }
        }
        if ($body===null) {
            \sasco\LibreDTE\Log::write(Estado::REQUEST_ERROR_BODY, Estado::get(Estado::REQUEST_ERROR_BODY, $wsdl, $retry));
            return false;
        }
        return new \SimpleXMLElement($body, LIBXML_COMPACT);
    }

    /**
     * Método que permite indicar si se debe o no verificar el certificado SSL
     * del SII
     * @param verificar =true si se quiere verificar certificado, =false en caso que no (por defecto se verifica)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-16
     */
    public static function setVerificarSSL($verificar = true)
    {
        self::$verificar_ssl = $verificar;
    }

    /**
     * Método que indica si se está o no verificando el SSL en las conexiones al SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-05-11
     */
    public static function getVerificarSSL()
    {
        return self::$verificar_ssl;
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
     * @version 2016-08-06
     */
    public static function enviar($usuario, $empresa, $dte, $token, $retry = null)
    {
        // definir datos que se usarán en el envío
        list($rutSender, $dvSender) = explode('-', str_replace('.', '', $usuario));
        list($rutCompany, $dvCompany) = explode('-', str_replace('.', '', $empresa));
        if (strpos($dte, '<?xml')===false) {
            $dte = '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n".$dte;
        }
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
        // definir reintentos si no se pasaron
        if (!$retry)
            $retry = self::$retry;
        // crear sesión curl con sus opciones
        $curl = curl_init();
        $header = [
            'User-Agent: Mozilla/4.0 (compatible; PROG 1.0; LibreDTE)',
            'Referer: https://libredte.cl',
            'Cookie: TOKEN='.$token,
        ];
        $url = 'https://'.self::$config['servidor'][self::getAmbiente()].'.sii.cl/cgi_dte/UPL/DTEUpload';
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // si no se debe verificar el SSL se asigna opción a curl, además si
        // se está en el ambiente de producción y no se verifica SSL se
        // generará una entrada en el log
        if (!self::$verificar_ssl) {
            if (self::getAmbiente()==self::PRODUCCION) {
                $msg = Estado::get(Estado::ENVIO_SSL_SIN_VERIFICAR);
                \sasco\LibreDTE\Log::write(Estado::ENVIO_SSL_SIN_VERIFICAR, $msg, LOG_WARNING);
            }
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        // enviar XML al SII
        for ($i=0; $i<$retry; $i++) {
            $response = curl_exec($curl);
            if ($response and $response!='Error 500')
                break;
        }
        unlink($file);
        // verificar respuesta del envío y entregar error en caso que haya uno
        if (!$response or $response=='Error 500') {
            if (!$response)
                \sasco\LibreDTE\Log::write(Estado::ENVIO_ERROR_CURL, Estado::get(Estado::ENVIO_ERROR_CURL, curl_error($curl)));
            if ($response=='Error 500')
                \sasco\LibreDTE\Log::write(Estado::ENVIO_ERROR_500, Estado::get(Estado::ENVIO_ERROR_500));
            return false;
        }
        // cerrar sesión curl
        curl_close($curl);
        // crear XML con la respuesta y retornar
        try {
            $xml = new \SimpleXMLElement($response, LIBXML_COMPACT);
        } catch (Exception $e) {
            \sasco\LibreDTE\Log::write(Estado::ENVIO_ERROR_XML, Estado::get(Estado::ENVIO_ERROR_XML, $e->getMessage()));
            return false;
        }
        if ($xml->STATUS!=0) {
            \sasco\LibreDTE\Log::write(
                $xml->STATUS,
                Estado::get($xml->STATUS).(isset($xml->DETAIL)?'. '.implode("\n", (array)$xml->DETAIL->ERROR):'')
            );
        }
        return $xml;
    }

    /**
     * Método para obtener la clave pública (certificado X.509) del SII
     *
     * \code{.php}
     *   $pub_key = \sasco\LibreDTE\Sii::cert(100); // Certificado IDK 100 (certificación)
     * \endcode
     *
     * @param idk IDK de la clave pública del SII. Si no se indica se tratará de determinar con el ambiente que se esté usando
     * @return Contenido del certificado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-16
     */
    public static function cert($idk = null)
    {
        // si se pasó un idk y existe el archivo asociado se entrega
        if ($idk) {
            $cert = dirname(dirname(__FILE__)).'/certs/'.$idk.'.cer';
            if (is_readable($cert))
                return file_get_contents($cert);
        }
        // buscar certificado y entregar si existe o =false si no
        $ambiente = self::getAmbiente();
        $cert = dirname(dirname(__FILE__)).'/certs/'.self::$config['certs'][$ambiente].'.cer';
        if (!is_readable($cert)) {
            \sasco\LibreDTE\Log::write(Estado::SII_ERROR_CERTIFICADO, Estado::get(Estado::SII_ERROR_CERTIFICADO, self::$config['certs'][$ambiente]));
            return false;
        }
        return file_get_contents($cert);
    }

    /**
     * Método que asigna el ambiente que se usará por defecto (si no está
     * asignado con la constante _LibreDTE_CERTIFICACION_)
     * @param ambiente Ambiente a usar: Sii::PRODUCCION o Sii::CERTIFICACION
     * @warning No se está verificando SSL en ambiente de certificación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-28
     */
    public static function setAmbiente($ambiente = self::PRODUCCION)
    {
        $ambiente = $ambiente ? self::CERTIFICACION : self::PRODUCCION;
        if ($ambiente==self::CERTIFICACION) {
            self::setVerificarSSL(false);
        }
        self::$ambiente = $ambiente;
    }

    /**
     * Método que determina el ambiente que se debe utilizar: producción o
     * certificación
     * @param ambiente Ambiente a usar: Sii::PRODUCCION o Sii::CERTIFICACION o null (para detección automática)
     * @return Ambiente que se debe utilizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public static function getAmbiente($ambiente = null)
    {
        if ($ambiente===null) {
            if (defined('_LibreDTE_CERTIFICACION_'))
                $ambiente = (int)_LibreDTE_CERTIFICACION_;
            else
                $ambiente = self::$ambiente;
        }
        return $ambiente;
    }

    /**
     * Método que entrega la tasa de IVA vigente
     * @return Tasa de IVA vigente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-03
     */
    public static function getIVA()
    {
        return self::IVA;
    }

    /**
     * Método que entrega un arreglo con todos los datos de los contribuyentes
     * que operan con factura electrónica descargados desde el SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-07-07
     */
    public static function getContribuyentes(\sasco\LibreDTE\FirmaElectronica $Firma, $ambiente = null, $dia = null)
    {
        // solicitar token
        $token = \sasco\LibreDTE\Sii\Autenticacion::getToken($Firma);
        if (!$token)
            return false;
        // definir ambiente y servidor
        $ambiente = self::getAmbiente($ambiente);
        $servidor = self::$config['servidor'][$ambiente];
        // preparar consulta curl
        $curl = curl_init();
        $header = [
            'User-Agent: Mozilla/4.0 (compatible; PROG 1.0; LibreDTE)',
            'Referer: https://'.$servidor.'.sii.cl/cvc/dte/ee_empresas_dte.html',
            'Cookie: TOKEN='.$token,
            'Accept-Encoding' => 'gzip, deflate, sdch',
        ];
        $dia = $dia===null ? date('Ymd') : str_replace('-', '', $dia);
        $url = 'https://'.$servidor.'.sii.cl/cvc_cgi/dte/ce_empresas_dwnld?NOMBRE_ARCHIVO=ce_empresas_dwnld_'.$dia.'.csv';
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // si no se debe verificar el SSL se asigna opción a curl, además si
        // se está en el ambiente de producción y no se verifica SSL se
        // generará un error de nivel E_USER_NOTICE
        if (!self::$verificar_ssl) {
            if ($ambiente==self::PRODUCCION) {
                $msg = Estado::get(Estado::ENVIO_SSL_SIN_VERIFICAR);
                \sasco\LibreDTE\Log::write(Estado::ENVIO_SSL_SIN_VERIFICAR, $msg, LOG_WARNING);
            }
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        // realizar consulta curl
        $response = curl_exec($curl);
        if (!$response)
            return false;
        // cerrar sesión curl
        curl_close($curl);
        // entregar datos del archivo CSV
        ini_set('memory_limit', '1024M');
        $lines = explode("\n", $response);
        $n_lines = count($lines);
        $data = [];
        for ($i=1; $i<$n_lines; $i++) {
            $row = str_getcsv($lines[$i], ';', '');
            unset($lines[$i]);
            if (!isset($row[5]))
                continue;
            for ($j=0; $j<6; $j++)
                $row[$j] = trim($row[$j]);
            $row[1] = utf8_decode($row[1]);
            $row[4] = strtolower($row[4]);
            $row[5] = strtolower($row[5]);
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Método que entrega la dirección regional según la comuna que se esté
     * consultando
     * @param comuna de la sucursal del emior o bien código de la sucursal del SII
     * @return Dirección regional del SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-06-03
     */
    public static function getDireccionRegional($comuna)
    {
        if (!is_numeric($comuna)) {
            $direccion = mb_strtoupper($comuna, 'UTF-8');
            return isset(self::$direcciones_regionales[$direccion]) ? self::$direcciones_regionales[$direccion] : $direccion;
        }
        return 'SUC '.$comuna;
    }

}
