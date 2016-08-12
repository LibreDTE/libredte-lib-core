<?php


namespace sasco\LibreDTE\Sii;

/**
 * Clase que representa la Cesion
 * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
 * @version 2016-08-10
 */
class Cesion
{

    private $dte; ///< Objeto XML que representa el DTE Cedido
    private $cesionario; ///< Detalles del cesionario
    private $xml; ///< Objeto XML que representa la cesion

    /**
     * Constructor de la clase Cesion
     * @param DTECedido Objerto DTECedido, Seq = Secuencia de la cesion
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function __construct(DTECedido $DTECedido, $Seq = 1)
    {


        //Busco Datos de la factura cedida
        $this->loadDTE($DTECedido);

        //Genero Caratula
        $this->xml = [
            'Cesion' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte Cesion_v10.xsd',
                    'version' => '1.0'
                ],
                'DocumentoCesion' => [
                    '@attributes' => ['ID' => 'Cesion'],
                    'SeqCesion' => $Seq,
                    'IdDTE' => [
                        'TipoDTE' => $this->dte['IdDoc']['TipoDTE'],
                        'RUTEmisor' => $this->dte['Emisor']['RUTEmisor'],
                        'RUTReceptor' => $this->dte['Receptor']['RUTRecep'],
                        'Folio' => $this->dte['IdDoc']['Folio'],
                        'FchEmis' => $this->dte['IdDoc']['FchEmis'],
                        'MntTotal' => $this->dte['Totales']['MntTotal']
                    ],
                    'Cedente' => [
                        'RUT' => $this->dte['Emisor']['RUTEmisor'],
                        'RazonSocial' => $this->dte['Emisor']['RznSoc'],
                        'Direccion' => $this->dte['Emisor']['DirOrigen'],
                        'eMail' => '',
                        'RUTAutorizado' => [
                            'RUT' => '',
                            'Nombre' => ''
                        ],
                        'DeclaracionJurada' => false
                    ],
                    'Cesionario' => [
                        'RUT' => '',
                        'RazonSocial' => '',
                        'Direccion' => '',
                        'eMail' => ''
                    ],
                    'MontoCesion' => '',
                    'UltimoVencimiento' => isset($this->dte['IdDoc']['MntPagos']['FchPago']) ? $this->dte['IdDoc']['MntPagos']['FchPago'] : $this->dte['IdDoc']['FchEmis'],
                    'OtrasCondiciones' => false,
                    'eMailDeudor' => false,
                    'TmstCesion' => date('Y-m-d\TH:i:s')
                ]
            ]
        ];

    }

    /**
     * Método que carga el DTECedido
     * @param xml a Array
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    private function loadDTE(DTECedido $DTECedido)
    {

        $this->dte = $DTECedido->getDTE();
        $this->dte = $this->dte->toArray()['DTE']['Documento']['Encabezado'];
    }

    /**
     * Método que carga datos del Cesionario
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function setCesionario($Rut, $Razon, $Direccion, $Email)
    {

        //Verifico que ya este la información del cesionario
        if (!$Rut || !$Razon || !$Direccion || !$Email) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS)
            );
            return false;
        }

        $this->xml['Cesion']['DocumentoCesion']['Cesionario']['RUT'] = $Rut;
        $this->xml['Cesion']['DocumentoCesion']['Cesionario']['RazonSocial'] = $Razon;
        $this->xml['Cesion']['DocumentoCesion']['Cesionario']['Direccion'] = $Direccion;
        $this->xml['Cesion']['DocumentoCesion']['Cesionario']['eMail'] = $Email;

        $this->cesionario = $this->xml['Cesion']['DocumentoCesion']['Cesionario'];
    }

    /**
     * Método que carga datos de la Declaracion
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function setDeclaracion($Rut, $Nombre, $Email)
    {

        //Verifico que ya este la información del cesionario
        if (!$this->cesionario || !$Rut || !$Nombre || !$Email) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS)
            );
            return false;
        }

        $this->xml['Cesion']['DocumentoCesion']['Cedente']['eMail'] = $Email;
        $this->xml['Cesion']['DocumentoCesion']['Cedente']['RUTAutorizado']['RUT'] = $Rut;
        $this->xml['Cesion']['DocumentoCesion']['Cedente']['RUTAutorizado']['Nombre'] = $Nombre;
        $this->xml['Cesion']['DocumentoCesion']['Cedente']['DeclaracionJurada'] = utf8_encode('Yo ' . strtoupper($Nombre) . ', Rut N° ' . $Rut . ',en representación de ' . $this->dte['Emisor']['RznSoc'] . ', RUT ' . $this->dte['Emisor']['RUTEmisor'] . ' declaro bajo juramento que he puesto a disposición del cesionario ' . $this->cesionario['RazonSocial'] . ', RUT ' . $this->cesionario['RUT'] . ', el (los) documento(s) donde constan los recibos de la recepción de las mercaderías entregadas o servicios prestados, entregados por parte del deudor de la factura ' . $this->dte['Receptor']['RznSocRecep'] . ', Rut ' . $this->dte['Receptor']['RUTRecep'] . ', de acuerdo a lo establecido en la Ley N° 19.983');
    }

    /**
     * Método que carga datos de los montos
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function setMontos($Monto, $Observacion = false, $Email = false)
    {

        //Verifico variables
        if (!$Monto) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS)
            );
            return false;
        }

        $this->xml['Cesion']['DocumentoCesion']['MontoCesion'] = $Monto;
        $this->xml['Cesion']['DocumentoCesion']['OtrasCondiciones'] = $Observacion;
        $this->xml['Cesion']['DocumentoCesion']['eMailDeudor'] = $Email;

    }

    /**
     * Método que realiza la firma de Cesion
     * @param Firma objeto que representa la Firma Electrónca
     * @return =true si el DTE pudo ser fimado o =false si no se pudo firmar
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function firmar(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $this->xml = (new \sasco\LibreDTE\XML())->generate($this->xml)->saveXML();

        $xml = $Firma->signXML($this->xml, '#Cesion', 'DocumentoCesion');
        if (!$xml) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_FIRMA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_FIRMA, '#Cesion')
            );
            return false;
        }

        $this->loadXML($xml);
        return true;
    }

    /**
     * Método que carga la Cesion ya armada desde un archivo XML
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
     * Método que entrega el Cesion en XML
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function saveXML()
    {
        return $this->xml->saveXML();
    }

    /**
     * Método que valida el schema de la Cesion
     * @return =true si el schema del documento del DTE es válido, =null si no se pudo determinar
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function schemaValidate()
    {
        return true;
    }


}
