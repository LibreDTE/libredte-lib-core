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
 * @version 2015-12-23
 */
class RespuestaEnvio extends \sasco\LibreDTE\Sii\Base\Documento
{

    private $respuesta_envios = [];
    private $respuesta_documentos = [];
    private $config = [
        'respuesta_envios_max' => 1000,
        'respuesta_documentos_max' => 1000,
    ]; ///< Configuración/reglas para el documento XML

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
     * @version 2015-12-15
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
        $this->id = 'ResultadoEnvio';
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
     * Método que indica si el XML corresonde a RecepcionEnvio
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-23
     */
    public function esRecepcionEnvio()
    {
        return isset($this->arreglo['RespuestaDTE']['Resultado']['RecepcionEnvio']);
    }

    /**
     * Método que indica si el XML corresonde a ResultadoDTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-23
     */
    public function esResultadoDTE()
    {
        return isset($this->arreglo['RespuestaDTE']['Resultado']['ResultadoDTE']);
    }

    /**
     * Método que entrega un arreglo con los resultados de recepciones del XML
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-23
     */
    public function getRecepciones()
    {
        if (!$this->esRecepcionEnvio())
            return false;
        // si no hay respustas se deben crear
        if (!$this->respuesta_envios) {
            // si no está creado el arrelgo con los datos error
            if (!$this->arreglo) {
                return false;
            }
            // crear repuestas a partir del arreglo
            $Recepciones = $this->arreglo['RespuestaDTE']['Resultado']['RecepcionEnvio']['RecepcionDTE'];
            if (!isset($Recepciones[0]))
                $Recepciones = [$Recepciones];
            foreach ($Recepciones as $Recepcion) {
                $this->respuesta_envios[] = $Recepcion;
            }
        }
        // entregar recibos
        return $this->respuesta_envios;
    }

    /**
     * Método que entrega un arreglo con los resultados de DTE del XML
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-23
     */
    public function getResultados()
    {
        if (!$this->esResultadoDTE())
            return false;
        // si no hay respustas se deben crear
        if (!$this->respuesta_documentos) {
            // si no está creado el arrelgo con los datos error
            if (!$this->arreglo) {
                return false;
            }
            // crear repuestas a partir del arreglo
            $Resultados = $this->arreglo['RespuestaDTE']['Resultado']['ResultadoDTE'];
            if (!isset($Resultados[0]))
                $Resultados = [$Resultados];
            foreach ($Resultados as $Resultado) {
                $this->respuesta_documentos[] = $Resultado;
            }
        }
        // entregar recibos
        return $this->respuesta_documentos;
    }

}
