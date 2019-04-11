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
 * Clase que representa la cesion de un documento
 * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-12-10
 */
class Aec extends \sasco\LibreDTE\Sii\Base\Envio
{

    private $cedido; ///< Objeto DteCedido
    private $cesiones = []; ///< Arreglo con los objetos de cesiones (ya que se puede ceder múltiples veces el DTE)

    /**
     * Método que agrega el objeto DteCedido
     * @param DteCedido Objeto de \sasco\LibreDTE\Sii\Factoring\DteCedido
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function agregarDteCedido(DTECedido $Cedido)
    {
        $this->cedido = $Cedido;
    }

    /**
     * Método que agrega el objeto Cesion
     * @param Cesion Objeto de \sasco\LibreDTE\Sii\Factoring\Cesion
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function agregarCesion(Cesion $Cesion)
    {
        $this->cesiones[] = $Cesion;
    }

    /**
     * Método para asignar la carátula. Opcional, si no se usa se sacan los datos
     * del documento de Cesion
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function setCaratula(array $caratula = [])
    {
        $this->caratula = array_merge([
            '@attributes' => [
                'version' => '1.0'
            ],
            'RutCedente' => isset($this->cesiones[0]) ? $this->cesiones[0]->getCedente()['RUT'] : false,
            'RutCesionario' => isset($this->cesiones[0]) ? $this->cesiones[0]->getCesionario()['RUT'] : false,
            'NmbContacto' => isset($this->cesiones[0]) ? $this->cesiones[0]->getCedente()['RUTAutorizado']['Nombre'] : false,
            'FonoContacto' => false,
            'MailContacto' => isset($this->cesiones[0]) ? $this->cesiones[0]->getCedente()['eMail'] : false,
            'TmstFirmaEnvio' => date('Y-m-d\TH:i:s'),
        ], $caratula);
    }

    /**
     * Método que genera el XML del AEC
     * @return XML AEC con DTE y Cesion
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function generar()
    {
        if (!isset($this->cedido) or !isset($this->cesiones[0]))
            return false;
        if (!isset($this->caratula))
            $this->setCaratula();
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
                        'ID' => 'LibreDTE_AEC'
                    ],
                    'Caratula' => $this->caratula,
                    'Cesiones' => [
                        'DTECedido' => null,
                        'Cesion' => null,
                    ]
                ]
            ]
        ])->saveXML();
        // agregar XML de DTE cedido y cesión
        $cedido_xml = trim(str_replace(['<?xml version="1.0" encoding="ISO-8859-1"?>', '<?xml version="1.0"?>'], '', $this->cedido->saveXML()));
        $cesion_xml = '';
        foreach ($this->cesiones as $cesion) {
            $cesion_xml .= trim(str_replace(['<?xml version="1.0" encoding="ISO-8859-1"?>', '<?xml version="1.0"?>'], '', $cesion->saveXML()))."\n";
        }
        $xmlEnvio = str_replace(
            ['<DTECedido/>', '<Cesion/>'],
            [$cedido_xml, $cesion_xml],
            $xmlEnvio
        );
        // firmar XML del envío y entregar
        $this->xml_data = $this->Firma->signXML($xmlEnvio, '#LibreDTE_AEC', 'DocumentoAEC', true);
        return $this->xml_data;
    }

    /**
     * Método que realiza el envío del AEC al SII
     * @return Track ID del envío o =false si hubo algún problema al enviar el documento
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-12-10
     */
    public function enviar()
    {
        // generar XML que se enviará
        if (!$this->xml_data)
            $this->xml_data = $this->generar();
        if (!$this->xml_data) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DOCUMENTO_ERROR_GENERAR_XML,
                \sasco\LibreDTE\Estado::get(
                    \sasco\LibreDTE\Estado::DOCUMENTO_ERROR_GENERAR_XML,
                    substr(get_class($this), strrpos(get_class($this), '\\')+1)
                )
            );
            return false;
        }
        // validar schema del documento antes de enviar
        if (!$this->schemaValidate())
            return false;
        // solicitar token
        $token = \sasco\LibreDTE\Sii\Autenticacion::getToken($this->Firma);
        if (!$token)
            return false;
        // enviar AEC
        $email = $this->caratula['MailContacto'];
        $emisor = $this->caratula['RutCedente'];
        $result = $this->enviarRTC($email, $emisor, $this->xml_data, $token, 10);
        if ($result===false)
            return false;
        if (!is_numeric((string)$result->TRACKID))
            return false;
        return (int)(string)$result->TRACKID;
    }

    /**
     * Método que realiza el envío de un AEC al SII
     * Referencia: https://palena.sii.cl/cgi_rtc/RTC/RTCDocum.cgi?2
     * @param email del usuario que envía el AEC
     * @param empresa RUT de la empresa emisora del AEC
     * @param dte Documento XML con el DTE que se desea enviar a SII
     * @param token Token de autenticación automática ante el SII
     * @param retry Intentos que se realizarán como máximo para obtener respuesta
     * @return Respuesta XML desde SII o bien null si no se pudo obtener respuesta
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @author Esteban De La Fuente Rubio (esteban[sasco.cl])
     * @version 2017-05-11
     */
    private function enviarRTC($email, $empresa, $dte, $token, $retry = null)
    {
        // definir datos que se usarán en el envío
        list($rutCompany, $dvCompany) = explode('-', str_replace('.', '', $empresa));
        if (strpos($dte, '<?xml') === false) {
            $dte = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . $dte;
        }
        do {
            $file = sys_get_temp_dir() . '/aec_' . md5(microtime() . $token . $dte) . '.xml';
        } while (file_exists($file));
        file_put_contents($file, $dte);
        $data = [
            'emailNotif' => $email,
            'rutCompany' => $rutCompany,
            'dvCompany' => $dvCompany,
            'archivo' => curl_file_create(
                $file,
                'application/xml',
                basename($file)
            ),
        ];
        // crear sesión curl con sus opciones
        $curl = curl_init();
        $header = [
            'User-Agent: Mozilla/4.0 (compatible; PROG 1.0; LibreDTE)',
            'Referer: https://libredte.cl',
            'Cookie: TOKEN='.$token,
        ];
        $url = 'https://'.\sasco\LibreDTE\Sii::getServidor().'.sii.cl/cgi_rtc/RTC/RTCAnotEnvio.cgi';
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // si no se debe verificar el SSL se asigna opción a curl, además si
        // se está en el ambiente de producción y no se verifica SSL se
        // generará una entrada en el log
        if (!\sasco\LibreDTE\Sii::getVerificarSSL()) {
            if (\sasco\LibreDTE\Sii::getAmbiente()==\sasco\LibreDTE\Sii::PRODUCCION) {
                \sasco\LibreDTE\Log::write(
                    \sasco\LibreDTE\Estado::ENVIO_SSL_SIN_VERIFICAR,
                    \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIO_SSL_SIN_VERIFICAR),
                    LOG_WARNING
                );
            }
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        // enviar XML al SII
        for ($i = 0; $i < $retry; $i++) {
            $response = curl_exec($curl);
            if ($response and $response != 'Error 500')
                break;
        }
        unlink($file);
        // verificar respuesta del envío y entregar error en caso que haya uno
        if (!$response or $response == 'Error 500') {
            if (!$response)
                \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::ENVIO_ERROR_CURL, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIO_ERROR_CURL, curl_error($curl)));
            if ($response == 'Error 500')
                \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::ENVIO_ERROR_500, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIO_ERROR_500));
            return false;
        }
        // cerrar sesión curl
        curl_close($curl);
        // crear XML con la respuesta y retornar
        try {
            $xml = new \SimpleXMLElement($response, LIBXML_COMPACT);
        } catch (Exception $e) {
            \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::ENVIO_ERROR_XML, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIO_ERROR_XML, $e->getMessage()));
            return false;
        }
        /*
         * 0 Envío recibido OK.
         * 1 Rut usuario autenticado no tiene permiso para enviar en empresa Cedente.
         * 2 Error en tamaño del archivo enviado.
         * 4 Faltan parámetros de entrada.
         * 5 Error de autenticación, TOKEN inválido, no existe o está expirado.
         * 6 Empresa no es DTE.
         * 9 Error Interno.
         * 10 Error Interno
         */
        $error = [
            1 => 'Rut usuario autenticado no tiene permiso para enviar en empresa Cedente',
            2 => 'Error en tamaño del archivo enviado',
            4 => 'Faltan parámetros de entrada',
            5 => 'Error de autenticación, TOKEN inválido, no existe o está expirado',
            6 => 'Empresa no es DTE',
            9 => 'Error Interno',
            10 => 'Error Interno'
        ];
        if ($xml->STATUS != 0) {
            \sasco\LibreDTE\Log::write(
                $xml->STATUS,
                $error[(int)$xml->STATUS]
            );
        }
        return $xml;
    }

}
