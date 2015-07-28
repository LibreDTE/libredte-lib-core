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

// importar clases
include_once dirname(dirname(__FILE__)).'/XML.php';
include_once dirname(dirname(__FILE__)).'/FirmaElectronica.php';
include_once dirname(dirname(__FILE__)).'/Sii.php';

/**
 * Clase para realizar autenticación automática ante el SII y obtener el token
 * necesario para las transacciones con el sitio.
 *
 * Provee sólo el método estático getToken(). Modo de uso:
 *
 * \code{.php}
 *   $firma_config = ['file'=>'/ruta/al/certificado.p12', 'pass'=>'contraseña'];
 *   $token = \sasco\LibreDTE\Sii_Autenticacion::getToken($firma_config);
 * \endcode
 *
 * Si se está utilizando con el framework SowerPHP se puede omitir la
 * configuración de la firma, ya que se leerá desde la configuración de la
 * aplicación: firma_electronica.default
 *
 * \code{.php}
 *   $token = \sasco\LibreDTE\Sii_Autenticacion::getToken();
 * \endcode
 *
 * Referencia: http://www.sii.cl/factura_electronica/autenticacion.pdf
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-07-27
 */
class Sii_Autenticacion
{

    /**
     * Método para solicitar la semilla para la autenticación automática.
     * Nota: la semilla tiene una validez de 2 minutos.
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/CrSeed.jws?WSDL
     *
     * @return Semilla obtenida desde SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-28
     */
    private static function getSeed()
    {
        $xml = Sii::request('CrSeed', 'getSeed');
        if ($xml===null or (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0]!=='00')
            return false;
        return (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_BODY/SEMILLA')[0];
    }

    /**
     * Método que firma una semilla previamente obtenida
     * @param seed Semilla obtenida desde SII
     * @param firma_config Configuración de la firma electrónica
     * @return Solicitud de token con la semilla incorporada y firmada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-28
     */
    private static function getTokenRequest($seed, $firma_config = [])
    {
        $xml_data = XML::get('getToken', [
            'semilla'=>$seed,
            'Signature'=>XML::get('Signature', ['referencia'=>'']),
        ]);
        if (!$xml_data)
            return false;
        $seedSigned = (new FirmaElectronica($firma_config))->signXML($xml_data);
        if (!$seedSigned)
            return false;
        return $seedSigned;
    }

    /**
     * Método para obtener el token de la sesión a través de una semilla
     * previamente firmada
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     *
     * @param firma_config Configuración de la firma electrónica
     * @return Token para autenticación en SII o =false si no se pudo obtener
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-28
     */
    public static function getToken($firma_config = [])
    {
        $semilla = self::getSeed();
        if (!$semilla) return false;
        $requestFirmado = self::getTokenRequest($semilla, $firma_config);
        if (!$requestFirmado) return false;
        $xml = Sii::request('GetTokenFromSeed', 'getToken', $requestFirmado);
        if ($xml===null or (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0]!=='00')
            return false;
        return (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_BODY/TOKEN')[0];
    }

}
