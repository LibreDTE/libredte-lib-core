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
 * Clase para realizar operaciones generales de DTEs
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-07-14
 */
class Sii_Dte
{

    /**
     * Método para obtener el estado de un DTE
     *
     * \code{.php}
     *   $query = [
     *      'RutConsultante'    => '',
     *      'DvConsultante'     => '',
     *      'RutCompania'       => '',
     *      'DvCompania'        => '',
     *      'RutReceptor'       => '',
     *      'DvReceptor'        => '',
     *      'TipoDte'           => '',
     *      'FolioDte'          => '',
     *      'FechaEmisionDte'   => '',
     *      'MontoDte'          => '',
     *   ];
     *   $estado = \sasco\LibreDTE\Sii_Dte::estado($query, $token);
     * \endcode
     *
     * Referencia: http://www.sii.cl/factura_electronica/factura_mercado/estado_dte.pdf
     *
     * @param query Arreglo con los datos del DTE que se consultarán
     * @param token Token de la autenticación automática
     * @return Objeto con el estado del DTE o =false en caso de error
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-14
     */
    public static function estado($query, $token)
    {
        extract($query);
        $soap = new \SoapClient('https://palena.sii.cl/DTEWS/QueryEstDte.jws?WSDL');
        try {
            $body = $soap->getEstDte(
                $RutConsultante, $DvConsultante,
                $RutCompania, $DvCompania,
                $RutReceptor, $DvReceptor,
                $TipoDte, $FolioDte, $FechaEmisionDte, $MontoDte, $token
            );
        } catch (\Exception $e) { return false; }
        $xml = new \SimpleXMLElement($body);
        return (object)[
            'estado' => (object)[
                'codigo' => (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0],
                'glosa' => (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/GLOSA_ESTADO')[0]
            ],
            'error' => (object)[
                'codigo' => (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ERR_CODE')[0],
                'glosa' => (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/GLOSA_ERR')[0]
            ],
        ];
    }

}
