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
 * Clase que representa la respuesta a un envío de un DTE por un proveedor
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-08
 */
class RespuestaEnvio
{

    private $respuesta_envios = [];
    private $respuesta_documentos = [];
    private $config = [
        'respuesta_envios_max' => 1000,
        'respuesta_documentos_max' => 1000,
    ]; ///< Configuración/reglas para el documento XML
    private $caratula; ///< arreglo con la caratula del envío
    private $Firma; ///< objeto de la firma electrónica
    private $xml_data; ///< String con el documento XML

    // posibles estados para la respuesta del envío
    public static $estados = [
        'envio' => [
            0 => 'Envío Recibido Conforme',
            1 => 'Envío Rechazado - Error de Schema',
            2 => 'Envío Rechazado - Error de Firma',
            3 => 'Envío Rechazado - RUT Receptor No Corresponde',
            90 => 'Envío Rechazado - Archivo Repetido',
            91 => 'Envío Rechazado - Archivo Ilegible',
            99 => 'Envío Rechazado - Otros',
        ],
        'documento' => [
            0 => 'DTE Recibido OK',
            1 => 'DTE No Recibido - Error de Firma',
            2 => 'DTE No Recibido - Error en RUT Emisor',
            3 => 'DTE No Recibido - Error en RUT Receptor',
            4 => 'DTE No Recibido - DTE Repetido',
            99 => 'DTE No Recibido - Otros',
        ],
        'respuesta_documento' => [
            0 => 'ACEPTADO OK',
            1 => 'ACEPTADO CON DISCREPANCIAS',
            2 => 'RECHAZADO',
        ],
    ];

    /**
     * Método que agrega una respuesta de envío
     * @param datos Arreglo con los datos de la respuesta
     * @return =true si se pudo agregar la respuesta o =false si no se agregó por exceder el límite o bien ya existía al menos una respuesta de documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function agregarRespuestaEnvio(array $datos)
    {
        if (isset($this->respuesta_documentos[0]))
            return false;
        if (isset($this->respuesta_envios[$this->config['respuesta_envios_max']-1]))
            return false;
        $this->respuesta_envios[] = array_merge([
            'NmbEnvio' => false,
            'FchRecep' => date('Y-m-d\TH:i:s'),
            'CodEnvio' => 0,
            'EnvioDTEID' => false,
            'Digest' => false,
            'RutEmisor' => false,
            'RutReceptor' => false,
            'EstadoRecepEnv' => false,
            'RecepEnvGlosa' => false,
            'NroDTE' => false,
            'RecepcionDTE' => false,
        ], $datos);
        return true;
    }

    /**
     * Método que agrega una respuesta de documento
     * @param datos Arreglo con los datos de la respuesta
     * @return =true si se pudo agregar la respuesta o =false si no se agregó por exceder el límite o bien ya existía al menos una respuesta de envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-08
     */
    public function agregarRespuestaDocumento(array $datos)
    {
        if (isset($this->respuesta_envios[0]))
            return false;
        if (isset($this->respuesta_documentos[$this->config['respuesta_documentos_max']-1]))
            return false;
        $this->respuesta_documentos[] = array_merge([
            'TipoDTE' => false,
            'Folio' => false,
            'FchEmis' => false,
            'RUTEmisor' => false,
            'RUTRecep' => false,
            'MntTotal' => false,
            'CodEnvio' => false,
            'EstadoDTE' => false,
            'EstadoDTEGlosa' => false,
            'CodRchDsc' => false,
        ], $datos);
        return true;
    }

    /**
     * Método para asignar la caratula
     * @param caratula Arreglo con datos de la respuesta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function setCaratula(array $caratula)
    {
        $this->caratula = array_merge([
            '@attributes' => [
                'version' => '1.0'
            ],
            'RutResponde' => false,
            'RutRecibe' => false,
            'IdRespuesta' => 0,
            'NroDetalles' => isset($this->respuesta_envios[0]) ? count($this->respuesta_envios) : count($this->respuesta_documentos),
            'NmbContacto' => false,
            'FonoContacto' => false,
            'MailContacto' => false,
            'TmstFirmaResp' => date('Y-m-d\TH:i:s'),
        ], $caratula);
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
     * Método que genera el XML para el envío de la respuesta al SII
     * @param caratula Arreglo con la carátula de la respuesta
     * @param Firma Objeto con la firma electrónica
     * @return XML con la respuesta firmada o =false si no se pudo generar o firmar la respuesta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public function generar()
    {
        // si ya se había generado se entrega directamente
        if ($this->xml_data)
            return $this->xml_data;
        // si no hay respuestas para generar entregar falso
        if (!isset($this->respuesta_envios[0]) and !isset($this->respuesta_documentos[0])) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::RESPUESTAENVIO_FALTA_RESPUESTA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::RESPUESTAENVIO_FALTA_RESPUESTA)
            );
            return false;
        }
        // si no hay carátula error
        if (!$this->caratula) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::RESPUESTAENVIO_FALTA_CARATULA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::RESPUESTAENVIO_FALTA_CARATULA)
            );
            return false;
        }
        // crear arreglo de lo que se enviará
        $arreglo = [
            'RespuestaDTE' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte RespuestaEnvioDTE_v10.xsd',
                    'version' => '1.0',
                ],
                'Resultado' => [
                    '@attributes' => [
                        'ID' => 'ResultadoEnvio'
                    ],
                    'Caratula' => $this->caratula,
                ]
            ]
        ];
        if (isset($this->respuesta_envios[0])) {
            $arreglo['RespuestaDTE']['Resultado']['RecepcionEnvio'] = $this->respuesta_envios;
        } else {
            $arreglo['RespuestaDTE']['Resultado']['ResultadoDTE'] = $this->respuesta_documentos;
        }
        // generar XML del envío
        $xmlEnvio = (new \sasco\LibreDTE\XML())->generate($arreglo)->saveXML();
        // firmar XML del envío y entregar
        $this->xml_data = $this->Firma ? $this->Firma->signXML($xmlEnvio, '#ResultadoEnvio', 'Resultado', true) : $xmlEnvio;
        return $this->xml_data;
    }

    /**
     * Método que valida el XML que se genera para la respuesta del envío
     * @return =true si el schema del documento del envío es válido, =null si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public function schemaValidate()
    {
        if (!$this->xml_data)
            return null;
        $xsd = dirname(dirname(dirname(__FILE__))).'/schemas/RespuestaEnvioDTE_v10.xsd';
        $this->xml = new \sasco\LibreDTE\XML();
        $this->xml->loadXML($this->xml_data);
        $result = $this->xml->schemaValidate($xsd);
        if (!$result) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::RESPUESTAENVIO_ERROR_SCHEMA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::RESPUESTAENVIO_ERROR_SCHEMA, implode("\n", libxml_get_errors()))
            );
        }
        return $result;
    }

}
