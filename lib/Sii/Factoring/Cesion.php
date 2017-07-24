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
 * Clase que representa la cesion electrónica
 * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-12-30
 */
class Cesion
{

    private $Encabezado; ///< Encabezado del DTE que se está cediendo
    private $datos; ///< Datos del XML de cesión
    private $declaracion = 'Yo, {usuario_nombre}, RUN {usuario_run}, representando a {emisor_razon_social}, RUT {emisor_rut}, declaro que he puesto a disposición del cesionario {cesionario_razon_social}, RUT {cesionario_rut}, el documento donde constan los recibos de la recepción de mercaderías entregadas o servicios prestados, entregados por parte del deudor de la factura {receptor_razon_social}, RUT {receptor_rut}, de acuerdo a lo establecido en la Ley N° 19.983'; ///< Declaración estándar en caso que no sea indicada al momento de crear al cedente

    /**
     * Constructor de la clase Cesion
     * @param DTECedido Objeto DteCedido
     * @param Seq secuencia de la cesión
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function __construct(DteCedido $DTECedido, $Seq = 1)
    {
        $this->Encabezado = $DTECedido->getDTE()->getDatos()['Encabezado'];
        $this->datos = [
            'Cesion' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'version' => '1.0'
                ],
                'DocumentoCesion' => [
                    '@attributes' => [
                        'ID' => 'LibreDTE_Cesion',
                    ],
                    'SeqCesion' => $Seq,
                    'IdDTE' => [
                        'TipoDTE' => $this->Encabezado['IdDoc']['TipoDTE'],
                        'RUTEmisor' => $this->Encabezado['Emisor']['RUTEmisor'],
                        'RUTReceptor' => $this->Encabezado['Receptor']['RUTRecep'],
                        'Folio' => $this->Encabezado['IdDoc']['Folio'],
                        'FchEmis' => $this->Encabezado['IdDoc']['FchEmis'],
                        'MntTotal' => $this->Encabezado['Totales']['MntTotal'],
                    ],
                    'Cedente' => false,
                    'Cesionario' => false,
                    'MontoCesion' => $this->Encabezado['Totales']['MntTotal'],
                    'UltimoVencimiento' => isset($this->Encabezado['IdDoc']['MntPagos']['FchPago']) ? $this->Encabezado['IdDoc']['MntPagos']['FchPago'] : $this->Encabezado['IdDoc']['FchEmis'],
                    'OtrasCondiciones' => false,
                    'eMailDeudor' => false,
                    'TmstCesion' => date('Y-m-d\TH:i:s')
                ]
            ]
        ];
    }

    /**
     * Método que permite cambiar la declaración por defecto
     * Están disponibles las siguientes variables dentro del string de la declaración:
     *   - {usuario_nombre}
     *   - {usuario_run}
     *   - {emisor_razon_social}
     *   - {emisor_rut}
     *   - {cesionario_razon_social}
     *   - {cesionario_rut}
     *   - {receptor_razon_social}
     *   - {receptor_rut}
     * @param declaracion String con la declaración y las variables para poder reemplazar los datos si es necesario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function setDeclaracion($declaracion)
    {
        $this->declaracion = $declaracion;
    }

    /**
     * Método que agrega los datos del cedente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-07-24
     */
    public function setCedente(array $cedente = [])
    {
        $this->datos['Cesion']['DocumentoCesion']['Cedente'] = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'RUT' => $this->Encabezado['Emisor']['RUTEmisor'],
            'RazonSocial' => $this->Encabezado['Emisor']['RznSoc'],
            'Direccion' => $this->Encabezado['Emisor']['DirOrigen'].', '.$this->Encabezado['Emisor']['CmnaOrigen'],
            'eMail' => !empty($this->Encabezado['Emisor']['CorreoEmisor']) ? $this->Encabezado['Emisor']['CorreoEmisor'] : false,
            'RUTAutorizado' => [
                'RUT' => false,
                'Nombre' => false,
            ],
            'DeclaracionJurada' => false,
        ], $cedente);
        if (!$this->datos['Cesion']['DocumentoCesion']['Cedente']['DeclaracionJurada']) {
            $this->datos['Cesion']['DocumentoCesion']['Cedente']['DeclaracionJurada'] = mb_substr(str_replace(
                [
                    '{usuario_nombre}',
                    '{usuario_run}',
                    '{emisor_razon_social}',
                    '{emisor_rut}',
                    '{cesionario_razon_social}',
                    '{cesionario_rut}',
                    '{receptor_razon_social}',
                    '{receptor_rut}',
                ],
                [
                    $this->datos['Cesion']['DocumentoCesion']['Cedente']['RUTAutorizado']['Nombre'],
                    $this->datos['Cesion']['DocumentoCesion']['Cedente']['RUTAutorizado']['RUT'],
                    $this->datos['Cesion']['DocumentoCesion']['Cedente']['RazonSocial'],
                    $this->datos['Cesion']['DocumentoCesion']['Cedente']['RUT'],
                    $this->datos['Cesion']['DocumentoCesion']['Cesionario']['RazonSocial'],
                    $this->datos['Cesion']['DocumentoCesion']['Cesionario']['RUT'],
                    $this->Encabezado['Receptor']['RznSocRecep'],
                    $this->Encabezado['Receptor']['RUTRecep'],
                ],
                $this->declaracion
            ), 0, 512);
        }
    }

    /**
     * Método que agrega los datos del cesionario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function setCesionario(array $cesionario)
    {
        $this->datos['Cesion']['DocumentoCesion']['Cesionario'] = $cesionario;
    }

    /**
     * Método que asigna otros datos de la cesión. Su uso es opcional, ya que de no ser llamado
     * se usará el monto total del documento y su fecha de emisión o pago si existe
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function setDatos(array $datos)
    {
        if (!empty($datos['MontoCesion'])) {
            $this->datos['Cesion']['DocumentoCesion']['MontoCesion'] = $datos['MontoCesion'];
        }
        if (!empty($datos['UltimoVencimiento'])) {
            $this->datos['Cesion']['DocumentoCesion']['UltimoVencimiento'] = $datos['UltimoVencimiento'];
        }
        if (!empty($datos['OtrasCondiciones'])) {
            $this->datos['Cesion']['DocumentoCesion']['OtrasCondiciones'] = $datos['OtrasCondiciones'];
        }
        if (!empty($datos['eMailDeudor'])) {
            $this->datos['Cesion']['DocumentoCesion']['eMailDeudor'] = $datos['eMailDeudor'];
        }
    }

    /**
     * Método que realiza la firma de cesión
     * @param Firma objeto que representa la Firma Electrónca
     * @return =true si el DTE pudo ser fimado o =false si no se pudo firmar
     * @author Adonias Vasquez (adonias.vasquez[at]epys.cl)
     * @version 2016-08-10
     */
    public function firmar(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $xml_unsigned = (new \sasco\LibreDTE\XML())->generate($this->datos)->saveXML();
        $xml = $Firma->signXML($xml_unsigned, '#LibreDTE_Cesion', 'DocumentoCesion');
        if (!$xml) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_FIRMA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_FIRMA, '#LibreDTE_Cesion')
            );
            return false;
        }
        $this->xml = new \sasco\LibreDTE\XML();
        if (!$this->xml->loadXML($xml) or !$this->schemaValidate())
            return false;
        return true;
    }

    /**
     * Método que entrega el string con el XML de la cesion
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

    /**
     * Método que entrega los datos del cedente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function getCedente()
    {
        return $this->datos['Cesion']['DocumentoCesion']['Cedente'];
    }

    /**
     * Método que entrega los datos del cesionario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-10
     */
    public function getCesionario()
    {
        return $this->datos['Cesion']['DocumentoCesion']['Cesionario'];
    }

}
