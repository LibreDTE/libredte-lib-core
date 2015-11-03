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

namespace sasco\LibreDTE\Sii;

/**
 * Clase para realizar autenticación automática ante el SII y obtener el token
 * necesario para las transacciones con el sitio.
 *
 * Provee sólo el método estático getToken(). Modo de uso:
 *
 * \code{.php}
 *   $firma_config = ['file'=>'/ruta/al/certificado.p12', 'pass'=>'contraseña'];
 *   $token = \sasco\LibreDTE\Sii\Autenticacion::getToken($firma_config);
 * \endcode
 *
 * Si se está utilizando con el framework SowerPHP se puede omitir la
 * configuración de la firma, ya que se leerá desde la configuración de la
 * aplicación: firma_electronica.default
 *
 * \code{.php}
 *   $token = \sasco\LibreDTE\Sii\Autenticacion::getToken();
 * \endcode
 *
 * Referencia: http://www.sii.cl/factura_electronica/autenticacion.pdf
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-08-19
 */
class Autenticacion
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
     * @version 2015-09-17
     */
    private static function getSeed()
    {
        $xml = \sasco\LibreDTE\Sii::request('CrSeed', 'getSeed');
        if ($xml===false or (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0]!=='00') {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::AUTH_ERROR_SEMILLA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::AUTH_ERROR_SEMILLA)
            );
            return false;
        }
        return (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_BODY/SEMILLA')[0];
    }

    /**
     * Método que firma una semilla previamente obtenida
     * @param seed Semilla obtenida desde SII
     * @param Firma objeto de la Firma electrónica o arreglo con configuración de la misma
     * @return Solicitud de token con la semilla incorporada y firmada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    private static function getTokenRequest($seed, $Firma = [])
    {
        if (is_array($Firma))
            $Firma = new \sasco\LibreDTE\FirmaElectronica($Firma);
        $seedSigned = $Firma->signXML(
            (new \sasco\LibreDTE\XML())->generate([
                'getToken' => [
                    'item' => [
                        'Semilla' => $seed
                    ]
                ]
            ])->saveXML()
        );
        if (!$seedSigned) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::AUTH_ERROR_FIRMA_SOLICITUD_TOKEN,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::AUTH_ERROR_FIRMA_SOLICITUD_TOKEN)
            );
            return false;
        }
        return $seedSigned;
    }

    /**
     * Método para obtener el token de la sesión a través de una semilla
     * previamente firmada
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     *
     * @param Firma objeto de la Firma electrónica o arreglo con configuración de la misma
     * @return Token para autenticación en SII o =false si no se pudo obtener
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-11-03
     */
    public static function getToken($Firma = [])
    {
        if (!$Firma) return false;
        $semilla = self::getSeed();
        if (!$semilla) return false;
        $requestFirmado = self::getTokenRequest($semilla, $Firma);
        if (!$requestFirmado) return false;
        $xml = \sasco\LibreDTE\Sii::request('GetTokenFromSeed', 'getToken', $requestFirmado);
        if ($xml===false or (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0]!=='00') {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::AUTH_ERROR_TOKEN,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::AUTH_ERROR_TOKEN)
            );
            return false;
        }
        return (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_BODY/TOKEN')[0];
    }

}
