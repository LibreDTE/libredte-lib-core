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

namespace libredte\lib\Sii;

/**
 * Clase para realizar autenticación automática ante el SII y obtener el token
 * necesario para las transacciones con el sitio.
 *
 * Provee solo el método estático getToken(). Modo de uso:
 *
 * \code{.php}
 *   $firma_config = ['file'=>'/ruta/al/certificado.p12', 'pass'=>'contraseña'];
 *   $token = \libredte\lib\Sii\Autenticacion::getToken($firma_config);
 * \endcode
 *
 * Referencia: http://www.sii.cl/factura_electronica/autenticacion.pdf
 */
class Autenticacion
{

    /**
     * Método para obtener el token de la sesión a través de una semilla
     * previamente firmada.
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     *
     * @param Firma objeto de la Firma electrónica o arreglo con configuración de la misma.
     * @return string|false Token para autenticación en SII o =false si no se pudo obtener.
     */
    public static function getToken($Firma)
    {
        if (!$Firma) {
            return false;
        }
        $semilla = self::getSeed();
        if (!$semilla) {
            return false;
        }
        $requestFirmado = self::getTokenRequest($semilla, $Firma);
        if (!$requestFirmado) {
            return false;
        }
        $xml = \libredte\lib\Sii::request('GetTokenFromSeed', 'getToken', $requestFirmado);
        if ($xml === false || (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0] !== '00') {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::AUTH_ERROR_TOKEN,
                \libredte\lib\Estado::get(\libredte\lib\Estado::AUTH_ERROR_TOKEN)
            );
            return false;
        }
        return (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_BODY/TOKEN')[0];
    }

    /**
     * Método para solicitar la semilla para la autenticación automática.
     * Nota: la semilla tiene una validez de 2 minutos.
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/CrSeed.jws?WSDL
     *
     * @return string|false Semilla obtenida desde SII.
     */
    private static function getSeed()
    {
        $xml = \libredte\lib\Sii::request('CrSeed', 'getSeed');
        if ($xml === false || (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0] !== '00') {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::AUTH_ERROR_SEMILLA,
                \libredte\lib\Estado::get(\libredte\lib\Estado::AUTH_ERROR_SEMILLA)
            );
            return false;
        }
        return (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_BODY/SEMILLA')[0];
    }

    /**
     * Método que firma una semilla previamente obtenida.
     * @param seed Semilla obtenida desde SII.
     * @param Firma objeto de la Firma electrónica o arreglo con configuración de la misma.
     * @return string|false Solicitud de token con la semilla incorporada y firmada.
     */
    private static function getTokenRequest(string $seed, $Firma)
    {
        if (is_array($Firma)) {
            $Firma = new \libredte\lib\FirmaElectronica($Firma);
        }
        $seedSigned = $Firma->signXML(
            (new \libredte\lib\XML())->generate([
                'getToken' => [
                    'item' => [
                        'Semilla' => $seed
                    ]
                ]
            ])->saveXML()
        );
        if (!$seedSigned) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::AUTH_ERROR_FIRMA_SOLICITUD_TOKEN,
                \libredte\lib\Estado::get(\libredte\lib\Estado::AUTH_ERROR_FIRMA_SOLICITUD_TOKEN)
            );
            return false;
        }
        return $seedSigned;
    }

}
