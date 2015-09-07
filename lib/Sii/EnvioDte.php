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
 * Clase que representa el envío de un DTE
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-07
 */
class EnvioDte
{

    private $dtes = []; ///< Objetos con los DTE que se enviarán
    private $config = [
        'SubTotDTE_max' => 20,
        'DTE_max' => 2000,
    ]; ///< Configuración/reglas para el documento XML
    private $caratula; ///< arreglo con la caratula del envío
    private $xml_data; ///< String con el documento XML
    private $xml; ///< Objeto XML que representa el EnvioDTE

    /**
     * Método que agrega un DTE al listado que se enviará
     * @param DTE Objeto del DTE
     * @return =true si se pudo agregar el DTE o =false si no se agregó por exceder el límite de un envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    public function agregar(Dte $DTE)
    {
        if (isset($this->dtes[$this->config['DTE_max']-1]))
            return false;
        $this->dtes[] = $DTE;
        return true;
    }

    /**
     * Método que realiza el envío del sobre con el o los DTEs al SII
     * @param caratula Arreglo con datos del envío: RutEnvia, FchResol y NroResol
     * @param Firma Objeto con la firma electrónica
     * @return Track ID del envío o =false si hubo algún problema al enviar el documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function enviar(array $caratula, \sasco\LibreDTE\FirmaElectronica $Firma)
    {
        // generar XML que se enviará
        if (!$this->xml_data)
            $this->xml_data = $this->generar($caratula, $Firma);
        if (!$this->xml_data)
            return false;
        // solicitar token
        $token = Autenticacion::getToken($Firma);
        if (!$token)
            return false;
        // enviar DTE
        $result = \sasco\LibreDTE\Sii::enviar($this->caratula['RutEnvia'], $this->caratula['RutEmisor'], $xml_data, $token);
        if ($result===false)
            return false;
        if (!is_numeric((string)$result->TRACKID))
            return false;
        return (int)(string)$result->TRACKID;
    }

    /**
     * Método que genera el XML para el envío del DTE al SII
     * @param caratula Arreglo con datos del envío: RutEnvia, FchResol y NroResol
     * @param Firma Objeto con la firma electrónica
     * @return XML con el envio del DTE firmado o =false si no se pudo generar o firmar el envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function generar(array $caratula, \sasco\LibreDTE\FirmaElectronica $Firma)
    {
        // si ya se había generado se entrega directamente
        if ($this->xml_data)
            return $this->xml_data;
        // si no hay DTEs para generar entregar falso
        if (!isset($this->dtes[0]))
            return false;
        // generar subtotales de DTE
        $SubTotDTE = $this->getSubTotDTE();
        if (isset($SubTotDTE[$this->config['SubTotDTE_max']]))
            return false;
        // armar caratula
        $this->caratula = array_merge([
            '@attributes' => [
                'version' => '1.0'
            ],
            'RutEmisor' => $this->dtes[0]->getEmisor(),
            'RutEnvia' => '',
            'RutReceptor' => $this->dtes[0]->getReceptor(),
            'FchResol' => '',
            'NroResol' => '',
            'TmstFirmaEnv' => date('Y-m-d\TH:i:s'),
            'SubTotDTE' => $SubTotDTE,
        ], $caratula);
        // genear XML del envío
        $xmlEnvio = (new \sasco\LibreDTE\XML())->generate([
            'EnvioDTE' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte EnvioDTE_v10.xsd',
                    'version' => '1.0'
                ],
                'SetDTE' => [
                    '@attributes' => [
                        'ID' => 'SetDoc'
                    ],
                    'Caratula' => $this->caratula,
                    'DTE' => null,
                ]
            ]
        ])->saveXML();
        // generar XML de los DTE que se deberán incorporar
        $DTEs = [];
        foreach ($this->dtes as &$DTE)
            $DTEs[] = trim(str_replace('<?xml version="1.0" encoding="ISO-8859-1"?>', '', $DTE->saveXML()));
        // firmar XML del envío y entregar
        $this->xml_data = $Firma->signXML(str_replace('<DTE/>', implode("\n", $DTEs), $xmlEnvio), '#SetDoc', 'SetDTE', true);
        return $this->xml_data;
    }

    /**
     * Método que obtiene los datos para generar los tags SubTotDTE
     * @return Arreglo con los datos para generar los tags SubTotDTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    private function getSubTotDTE()
    {
        $SubTotDTE = [];
        $subtotales = [];
        foreach ($this->dtes as &$DTE) {
            if (!isset($subtotales[$DTE->getTipo()]))
                $subtotales[$DTE->getTipo()] = 0;
            $subtotales[$DTE->getTipo()]++;
        }
        foreach ($subtotales as $tipo => $subtotal) {
            $SubTotDTE[] = [
                'TpoDTE' => $tipo,
                'NroDTE' => $subtotal,
            ];
        }
        return $SubTotDTE;
    }

    /**
     * Método que entrega el string XML del EnvioDte
     * @return String con XML
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function saveXML()
    {
        return $this->xml_data ? $this->xml_data : false;
    }

    /**
     * Método que carga un XML de EnvioDte y asigna el objeto XML correspondiente
     * para poder obtener los datos del envío
     * @return Objeto XML
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function loadXML($xml_data)
    {
        $this->xml_data = $xml_data;
        $this->xml = new \sasco\LibreDTE\XML();
        $this->xml->loadXML($this->xml_data);
        return $this->xml;
    }

    /**
     * Método que entrega un arreglo con los datos de la carátula del envío
     * @return Arreglo con datos de carátula
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getCaratula()
    {
        if (!$this->xml)
            return false;
        $Caratula = $this->xml->getElementsByTagName('Caratula')->item(0);
        if (!$Caratula)
            return false;
        $XML = new \sasco\LibreDTE\XML();
        $XML->loadXML($Caratula->C14N());
        $array = $XML->toArray();
        return isset($array['Caratula']) ? $array['Caratula'] : false;
    }

    /**
     * Método que entrega el arreglo con los objetos DTE del envío
     * @return Arreglo de objetos DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getDocumentos()
    {
        // si no hay documentos se deben crear
        if (!$this->dtes) {
            // si no hay XML no se pueden crear los documentos
            if (!$this->xml)
                return false;
            // crear documentos a partir del XML
            $DTEs = $this->xml->getElementsByTagName('DTE');
            foreach ($DTEs as $nodo_dte) {
                $this->dtes[] = new Dte($nodo_dte->C14N(), false); // cargar DTE sin normalizar
            }
        }
        return $this->dtes;
    }

}
