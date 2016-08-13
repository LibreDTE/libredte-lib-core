<?php


namespace sasco\LibreDTE\Sii;

/**
 * Clase que representa el DTE Cedido
 * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
 * @version 2016-08-10
 */
class DTECedido
{

    private $envio; ///< Objeto XML de EnvioDte
    private $dtes; ///< Objetos XMLs de EnvioDte
    private $dte; ///< Objeto XML que representa el DTE Cedido
    private $xml; ///< Objeto XML que representa el DTECedido

    /**
     * Constructor de la clase DTECedido
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function __construct()
    {
        $this->xml = (new \sasco\LibreDTE\XML())->generate([
            'DTECedido' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'version' => '1.0'
                ],
                'DocumentoDTECedido' => [
                    '@attributes' => [
                        'ID' => 'DTECedido'
                    ],
                    'DTE' => '',
                    'ImagenDTE' => base64_encode(time()),
                    'Recibo' => false,
                    'TmstFirma' => date('Y-m-d\TH:i:s')
                ]
            ]
        ])->saveXML();

    }

    /**
     * Método que carga el primer DTE un archivo XML EnvioDTE
     * @param xml String con los datos completos del XML del EnvioDTE
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function loadEnvioDTE($xml)
    {

        // Cargar EnvioDTE y extraer arreglo con datos de carátula y DTEs
        $this->envio = new \sasco\LibreDTE\XML();
        if (!$this->envio->loadXML($xml)) {

            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS)
            );
            return false;
        }
        // crear documentos a partir del XML
        $this->dtes = $this->envio->getElementsByTagName('DTE');
        $this->dte = $this->dtes[0]->C14N();

        //Limpio XML y extraigo solo <DTE></DTE>
        $start = '<DTE';
        $end = '</DTE>';
        $output = strstr(substr($xml, strpos($xml, $start) + strlen($start)), $end, true);
        $dte = $start.$output.$end;
        $this->xml = str_replace('<DTE/>', $dte, $this->xml);

        return true;
    }

    /**
     * Método que carga un archivo XML DTE
     * @param xml String con los datos completos del XML del DTE
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function loadDTE($xml)
    {
        if (!empty($xml)) {

            $this->dte = $xml;

            $this->xml = str_replace('<DTE/>', $this->dte, $this->xml);

            print_r($this->xml);exit();
            
            return true;

        }

        \sasco\LibreDTE\Log::write(
            \sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS,
            \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS)
        );
        return false;
    }


    /**
     * Método que realiza la firma del DTE
     * @param Firma objeto que representa la Firma Electrónca
     * @return =true si el DTE pudo ser fimado o =false si no se pudo firmar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public function firmar(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $xml = $Firma->signXML($this->xml, '#DTECedido', 'DocumentoDTECedido');
        if (!$xml) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_FIRMA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_FIRMA, '#DTECedido')
            );
            return false;
        }

        $this->xml = $xml;
        return true;
    }

    /**
     * Método que carga el DTE ya armado desde un archivo XML
     * @param xml String con los datos completos del XML del DTE
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    private function loadXML($xml)
    {
        if (!empty($xml)) {
            $this->xml = new \sasco\LibreDTE\XML();
            if (!$this->xml->loadXML($xml) or !$this->schemaValidate())
                return false;

            return true;
        }
        return false;
    }

    /**
     * Método que entrega el DTECedido en XML
     * @return XML con el DTECedido (podría: con o sin timbre y con o sin firma)
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
     * Método que carga el DTE ya armado desde un archivo XML
     * @param xml String con los datos completos del XML del DTE
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function getDTE()
    {
        if (!empty($this->dte)) {
            $xml = new \sasco\LibreDTE\XML();
            if (!$xml->loadXML($this->dte))
                return false;

            return $xml;
        }
    }

    /**
     * Método que carga el ID del DTE ya armado desde un archivo XML
     * @param xml String con los datos completos del XML del DTE
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function getDTEID()
    {
        if (!empty($this->dte)) {
            $xml = new \sasco\LibreDTE\XML();
            if (!$xml->loadXML($this->dte))
                return false;

            $xml = $xml->toArray();

            return ($xml['DTE']['Documento']['@attributes']['ID']);

        }
    }
}
