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
include_once dirname(dirname(__FILE__)).'/FirmaElectronica.php';

/**
 * Clase para realizar autenticación automática ante el SII y obtener el token
 * necesario para las transacciones con el sitio.
 *
 * Provee sólo el método estático getToken(). Modo de uso:
 *
 * \code{.php}
 *   include_once 'sasco/libredte/lib/Sii/Autenticacion.php';
 *   $firma_config = ['file'=>'/ruta/al/certificado.p12', 'pass'=>'contraseña'];
 *   $token = \sasco\LibreDTE\Sii_Autenticacion::getToken($firma_config);
 * \endcode
 *
 * Si se está utilizando con el framework SowerPHP se puede omitir la
 * configuración de la firma, ya que se leerá desde la configuración de la
 * aplicación: firma_electronica.default
 *
 * \code{.php}
 *   \sowerphp\core\App::import('Vendor/sasco/libredte/lib/Sii/Autenticacion');
 *   $token = \sasco\LibreDTE\Sii_Autenticacion::getToken();
 * \endcode
 *
 * Referencia: http://www.sii.cl/factura_electronica/autenticacion.pdf
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2014-12-18
 */
class Sii_Autenticacion
{

    private static $retry = 10; ///< Veces que se reintentará conectar a SII al usar el servicio web
    private static $getToken_xml = '<?xml version="1.0"?>
<getToken>
<item>
<Semilla>{semilla}</Semilla>
</item>
<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
<SignedInfo>
<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
<Reference URI="">
<Transforms>
<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
</Transforms>
<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
<DigestValue/>
</Reference>
</SignedInfo>
<SignatureValue/>
<KeyInfo>
<KeyValue>
<RSAKeyValue>
<Modulus/>
<Exponent/>
</RSAKeyValue>
</KeyValue>
<X509Data>
<X509Certificate/>
</X509Data>
</KeyInfo>
</Signature>
</getToken>'; ///< XML para solicitar token

    /**
     * Método para solicitar la semilla para la autenticación automática.
     * Nota: la semilla tiene una validez de 2 minutos.
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/CrSeed.jws?WSDL
     *
     * @return Semilla obtenida desde SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-18
     */
    private static function getSeed()
    {
        $soap = new \SoapClient('https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL');
        for ($i=0; $i<self::$retry; $i++) {
            try {
                $body = $soap->getSeed();
                break;
            } catch (\Exception $e) {
                $body = null;
            }
        }
        if ($body===null) return false;
        $xml = new \SimpleXMLElement($body);
        if ((string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0] !== '00')
            return false;
        return (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_BODY/SEMILLA')[0];
    }

    /**
     * Método que firma una semilla previamente obtenida
     * @param seed Semilla obtenida desde SII
     * @param firma_config Configuración de la firma electrónica
     * @return Solicitud de token con la semilla incorporada y firmada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-08
     */
    private static function getTokenRequest($seed, $firma_config = [])
    {
        $xml_data = str_replace('{semilla}', $seed, self::$getToken_xml);
        $seedSigned = (new FirmaElectronica($firma_config))->signXML($xml_data);
        if (!$seedSigned) return false;
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
     * @version 2014-12-18
     */
    public static function getToken($firma_config = [])
    {
        $semilla = self::getSeed();
        if (!$semilla) return false;
        $requestFirmado = self::getTokenRequest($semilla, $firma_config);
        if (!$requestFirmado) return false;
        $soap = new \SoapClient('https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL');
        for ($i=0; $i<self::$retry; $i++) {
            try {
                $body = $soap->getToken($requestFirmado);
                break;
            } catch (\Exception $e) {
                $body = null;
            }
        }
        if ($body===null) return false;
        $xml = new \SimpleXMLElement($body);
        if ((string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0] !== '00')
            return false;
        return (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_BODY/TOKEN')[0];
    }

}
