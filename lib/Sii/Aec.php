<?php

namespace sasco\LibreDTE\Sii;

/**
 * Clase que representa la cesion de un documento
 * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
 * @version 2016-08-10
 */
class DocumentoAEC extends \sasco\LibreDTE\Sii\Base\Envio
{


    private $cedido; ///< Objetos DTECedido
    private $cesion; ///< Objetos sesion


    /**
     * Método para asignar la caratula
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function setCaratula(array $caratula)
    {

        // generar caratula
        $this->caratula = array_merge([
            '@attributes' => [
                'version' => '1.0'
            ],
            'RutCedente' => '', // RUT que Genera el Archivo de Transferencias
            'RutCesionario' => '', // RUT a Quien Va Dirigido el Archivo de Transferencias
            'NmbContacto' => false, // Persona de Contacto para aclarar dudas
            'FonoContacto' => false, // Telefono de Contacto
            'MailContacto' => false, // Correo Electronico de Contacto
            'TmstFirmaEnvio' => date('Y-m-d\TH:i:s')
        ], $caratula);
        return true;
    }

    /**
     * Método que realiza el envío del sobre con el o los DTEs al SII
     * @return Track ID del envío o =false si hubo algún problema al enviar el documento
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function enviar()
    {
        // enviar al SII
        return parent::enviar();
    }

    /**
     * Método que genera el XML AEC
     * @return XML AEC con DTE y Cesion
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10 
     */
    public function generar()
    {

        // genear XML del envío
        $xmlEnvio = (new \sasco\LibreDTE\XML())->generate([
            'AEC' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte AEC_v10.xsd',
                    'version' => '1.0'
                ],
                'DocumentoAEC' => [
                    '@attributes' => [
                        'ID' => 'AECDoc'
                    ],
                    'Caratula' => $this->caratula,
                    'Cesiones' => [
                        'DTECEDIDO' => '',
                        'DTECESION' => ''
                    ]
                ]
            ]
        ])->saveXML();

        //Verifico que ya este la información del cesionario
        if (!$this->cedido) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS)
            );
            return false;
        }
        $DteCedido = trim(str_replace(['<?xml version="1.0" encoding="ISO-8859-1"?>', '<?xml version="1.0"?>'], '', $this->cedido->saveXML()));
        $xmlEnvio = str_replace('<DTECEDIDO/>', $DteCedido, $xmlEnvio);

        //Verifico que ya este la información de la cesion
        if (!$this->cesion) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS)
            );
            return false;
        }
        $DteCesion = trim(str_replace(['<?xml version="1.0" encoding="ISO-8859-1"?>', '<?xml version="1.0"?>'], '', $this->cesion->saveXML()));
        $xmlEnvio = str_replace('<DTECESION/>', $DteCesion, $xmlEnvio);

        // firmar XML del envío y entregar
        $this->xml_data = $this->Firma->signXML($xmlEnvio, '#AECDoc', 'DocumentoAEC', true);
        return $this->xml_data;
    }

    /**
     * Método que agrega el objeto DTECedido
     * @param DTECedido Objeto del DTECedido
     * @return =true si se pudo agregar  o =false si no existe
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function agregarDteCedido(DTECedido $Cedido)
    {

        if (!$Cedido) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::ENVIODTE_FALTA_DTE,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIODTE_FALTA_DTE)
            );
            return false;
        }
        $this->cedido = $Cedido;
        return true;
    }

    /**
     * Método que agrega el objeto Cesion
     * @param Cesion Objeto del Cesion
     * @return =true si se pudo agregar  o =false si no existe
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function agregarCesion(Cesion $Cesion)
    {

        if (!$Cesion) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::ENVIODTE_FALTA_DTE,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIODTE_FALTA_DTE)
            );
            return false;
        }
        $this->cesion = $Cesion;
        return true;
    }

}
