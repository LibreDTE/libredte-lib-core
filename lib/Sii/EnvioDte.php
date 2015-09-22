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
    private $Firma; ///< objeto de la firma electrónica
    private $xml_data; ///< String con el documento XML
    private $xml; ///< Objeto XML que representa el EnvioDTE
    private $arreglo; ///< Arreglo con los datos del XML

    /**
     * Método que agrega un DTE al listado que se enviará
     * @param DTE Objeto del DTE
     * @return =true si se pudo agregar el DTE o =false si no se agregó por exceder el límite de un envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public function agregar(Dte $DTE)
    {
        if (isset($this->dtes[$this->config['DTE_max']-1])) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::ENVIODTE_DTE_MAX,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIODTE_DTE_MAX, $this->config['DTE_max'])
            );
            return false;
        }
        $this->dtes[] = $DTE;
        return true;
    }

    /**
     * Método para asignar la caratula
     * @param caratula Arreglo con datos del envío: RutEnvia, FchResol y NroResol
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-22
     */
    public function setCaratula(array $caratula)
    {
        // si no hay DTEs para generar entregar falso
        if (!isset($this->dtes[0])) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::ENVIODTE_FALTA_DTE,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIODTE_FALTA_DTE)
            );
            return false;
        }
        // si se agregaron demasiados DTE error
        $SubTotDTE = $this->getSubTotDTE();
        if (isset($SubTotDTE[$this->config['SubTotDTE_max']])) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::ENVIODTE_TIPO_DTE_MAX,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIODTE_TIPO_DTE_MAX, $this->config['SubTotDTE_max'])
            );
            return false;
        }
        // generar caratula
        $this->caratula = array_merge([
            '@attributes' => [
                'version' => '1.0'
            ],
            'RutEmisor' => $this->dtes[0]->getEmisor(),
            'RutEnvia' => isset($this->Firma) ? $this->Firma->getID() : '',
            'RutReceptor' => $this->dtes[0]->getReceptor(),
            'FchResol' => '',
            'NroResol' => '',
            'TmstFirmaEnv' => date('Y-m-d\TH:i:s'),
            'SubTotDTE' => $SubTotDTE,
        ], $caratula);
        return true;
    }

    /**
     * Método para asignar la caratula
     * @param Firma Objeto con la firma electrónica
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function setFirma(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $this->Firma = $Firma;
    }

    /**
     * Método que realiza el envío del sobre con el o los DTEs al SII
     * @return Track ID del envío o =false si hubo algún problema al enviar el documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public function enviar()
    {
        // generar XML que se enviará
        if (!$this->xml_data)
            $this->xml_data = $this->generar();
        if (!$this->xml_data) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::ENVIODTE_FALTA_XML,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIODTE_FALTA_XML)
            );
            return false;
        }
        // validar schema del documento antes de enviar
        if (!$this->schemaValidate())
            return false;
        // solicitar token
        $token = Autenticacion::getToken($this->Firma);
        if (!$token)
            return false;
        // enviar DTE
        $result = \sasco\LibreDTE\Sii::enviar($this->caratula['RutEnvia'], $this->caratula['RutEmisor'], $this->xml_data, $token);
        if ($result===false)
            return false;
        if (!is_numeric((string)$result->TRACKID))
            return false;
        return (int)(string)$result->TRACKID;
    }

    /**
     * Método que genera el XML para el envío del DTE al SII
     * @return XML con el envio del DTE firmado o =false si no se pudo generar o firmar el envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public function generar()
    {
        // si ya se había generado se entrega directamente
        if ($this->xml_data)
            return $this->xml_data;
        // si no hay DTEs para generar entregar falso
        if (!isset($this->dtes[0])) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::ENVIODTE_FALTA_DTE,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIODTE_FALTA_DTE)
            );
            return false;
        }
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
        $xml = str_replace('<DTE/>', implode("\n", $DTEs), $xmlEnvio);
        $this->xml_data = $this->Firma ? $this->Firma->signXML($xml, '#SetDoc', 'SetDTE', true) : $xml;
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
        $this->toArray();
        return $this->xml;
    }

    public function toArray()
    {
        if (!$this->xml)
            return false;
        if (!$this->arreglo)
            $this->arreglo = $this->xml->toArray();
        return $this->arreglo;
    }

    /**
     * Método que entrega un arreglo con los datos de la carátula del envío
     * @return Arreglo con datos de carátula
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getCaratula()
    {
        return isset($this->arreglo['EnvioDTE']['SetDTE']['Caratula']) ? $this->arreglo['EnvioDTE']['SetDTE']['Caratula'] : false;
    }

    /**
     * Método que entrega el ID de SetDTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getID()
    {
        return isset($this->arreglo['EnvioDTE']['SetDTE']['@attributes']['ID']) ? $this->arreglo['EnvioDTE']['SetDTE']['@attributes']['ID'] : false;
    }

    /**
     * Método que entrega el DigestValue de la firma del envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getDigest()
    {
        return isset($this->arreglo['EnvioDTE']['Signature']['SignedInfo']['Reference']['DigestValue']) ? $this->arreglo['EnvioDTE']['Signature']['SignedInfo']['Reference']['DigestValue'] : false;
    }

    /**
     * Método que entrega el rut del emisor del envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getEmisor()
    {
        $Caratula = $this->getCaratula();
        return $Caratula ? $Caratula['RutEmisor'] : false;
    }

    /**
     * Método que entrega el rut del receptor del envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getReceptor()
    {
        $Caratula = $this->getCaratula();
        return $Caratula ? $Caratula['RutReceptor'] : false;
    }

    /**
     * Método que entrega el arreglo con los objetos DTE del envío
     * @return Arreglo de objetos DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public function getDocumentos()
    {
        // si no hay documentos se deben crear
        if (!$this->dtes) {
            // si no hay XML no se pueden crear los documentos
            if (!$this->xml) {
                \sasco\LibreDTE\Log::write(
                    \sasco\LibreDTE\Estado::ENVIODTE_GETDOCUMENTOS_FALTA_XML,
                    \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIODTE_GETDOCUMENTOS_FALTA_XML)
                );
                return false;
            }
            // crear documentos a partir del XML
            $DTEs = $this->xml->getElementsByTagName('DTE');
            foreach ($DTEs as $nodo_dte) {
                $this->dtes[] = new Dte($nodo_dte->C14N(), false); // cargar DTE sin normalizar
            }
        }
        return $this->dtes;
    }

    /**
     * Método que determina el estado de validación sobre el envío
     * @param datos Arreglo con datos para hacer las validaciones
     * @return Código del estado de la validación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getEstadoValidacion(array $datos = null)
    {
        if (!$this->schemaValidate())
            return 1;
        if (!$this->checkFirma())
            return 2;
        if ($datos and $this->getReceptor()!=$datos['RutReceptor'])
            return 3;
        return 0;
    }

    /**
     * Método que valida el schema del EnvioDTE
     * @return =true si el schema del documento del envío es válido, =null si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-18
     */
    public function schemaValidate()
    {
        if (!$this->xml_data)
            return null;
        $xsd = dirname(dirname(dirname(__FILE__))).'/schemas/EnvioDTE_v10.xsd';
        $this->xml = new \sasco\LibreDTE\XML();
        $this->xml->loadXML($this->xml_data);
        $result = $this->xml->schemaValidate($xsd);
        if (!$result) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::ENVIODTE_ERROR_SCHEMA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIODTE_ERROR_SCHEMA, implode("\n", $this->xml->getErrors()))
            );
        }
        return $result;
    }

    /**
     * Método que indica si la firma del documento es o no válida
     * @return =true si la firma del documento del envío es válida, =null si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function checkFirma()
    {
        if (!$this->xml)
            return null;
        // listado de firmas del XML
        $Signatures = $this->xml->documentElement->getElementsByTagName('Signature');
        // verificar firma de SetDTE
        $SetDTE = $this->xml->documentElement->getElementsByTagName('SetDTE')->item(0)->C14N();
        $SignedInfo = $Signatures->item($Signatures->length-1)->getElementsByTagName('SignedInfo')->item(0);
        $DigestValue = $Signatures->item($Signatures->length-1)->getElementsByTagName('DigestValue')->item(0)->nodeValue;
        $SignatureValue = $Signatures->item($Signatures->length-1)->getElementsByTagName('SignatureValue')->item(0)->nodeValue;
        $X509Certificate = $Signatures->item($Signatures->length-1)->getElementsByTagName('X509Certificate')->item(0)->nodeValue;
        $X509Certificate = '-----BEGIN CERTIFICATE-----'."\n".wordwrap(trim($X509Certificate), 64, "\n", true)."\n".'-----END CERTIFICATE----- ';
        $valid = openssl_verify($SignedInfo->C14N(), base64_decode($SignatureValue), $X509Certificate) === 1 ? true : false;
        return $valid and $DigestValue===base64_encode(sha1($SetDTE, true));
    }

}
