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

namespace sasco\LibreDTE\Sii\Factoring;

/**
 * Clase que representa el DTE cedido
 * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-12-10
 */
class DteCedido
{

    private $dte; ///< Objeto con el DTE que se está cediendo
    private $xml; ///< String con el XML del DTE cedido

    /**
     * Constructor de la clase DteCedido
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-09
     */
    public function __construct(\sasco\LibreDTE\Sii\Dte $DTE)
    {
        $this->dte = $DTE;
        $xml = (new \sasco\LibreDTE\XML())->generate([
            'DTECedido' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'version' => '1.0'
                ],
                'DocumentoDTECedido' => [
                    '@attributes' => [
                        'ID' => 'LibreDTE_DTECedido'
                    ],
                    'DTE' => null,
                    'ImagenDTE' => false,
                    'Recibo' => false,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ]
            ]
        ])->saveXML();
        $xml_dte = $this->dte->saveXML();
        $xml_dte = substr($xml_dte, strpos($xml_dte, '<DTE'));
        $this->xml = str_replace('<DTE/>', $xml_dte, $xml);
    }

    /**
     * Método que realiza la firma del DTE cedido
     * @param Firma objeto que representa la Firma Electrónca
     * @return =true si el DTE pudo ser fimado o =false si no se pudo firmar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function firmar(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $xml = $Firma->signXML($this->xml, '#LibreDTE_DTECedido', 'DocumentoDTECedido');
        if (!$xml) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_FIRMA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_FIRMA, '#LibreDTE_DTECedido')
            );
            return false;
        }
        $this->xml = $xml;
        return true;
    }

    /**
     * Método que entrega el XML del DTE cedido
     * @return XML del DTE cedido (puede ser: con o sin firma)
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function saveXML()
    {
        return $this->xml;
    }

    /**
     * Método que valida el schema del DTE
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function schemaValidate()
    {
        return true;
    }

    /**
     * Método que entrega el objeto del DTE que se está cediendo
     * @return \sasco\LibreDTE\Sii\Dte
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function getDTE()
    {
        return $this->dte;
    }

}
