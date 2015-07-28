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
 * @version 2015-07-28
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
     * @return Objeto SimpleXMLElement con el estado del DTE o =false en caso de no poder determinar el estado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-28
     */
    public static function estado($query, $token)
    {
        extract($query);
        $xml = Sii::request('QueryEstDte', 'getEstDte', [
            $RutConsultante, $DvConsultante,
            $RutCompania, $DvCompania,
            $RutReceptor, $DvReceptor,
            $TipoDte, $FolioDte, $FechaEmisionDte, $MontoDte,
            $token
        ]);
        if ($xml===false)
            return false;
        return [
            'codigo' => (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/ERR_CODE')[0],
            'glosa' => (string)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR/GLOSA_ERR')[0],
        ];
    }

    /**
     * Método que entrega el estado de un DTE enviado al SII
     *
     * Referencia: http://www.sii.cl/factura_electronica/factura_mercado/estado_envio.pdf
     *
     * @param empresa
     * @param trackID
     * @param token
     * @return Objeto SimpleXMLElement con el estado del DTE o =false en caso de no poder determinar el estado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-28
     */
    public static function estadoEnvio($empresa, $trackID, $token)
    {
        list($rut, $dv) = explode('-', str_replace('.', '', $empresa));
        return Sii::request('QueryEstUp', 'getEstUp', [$rut, $dv, $trackID, $token]);
    }

}
