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
 * Clase que representa un DTE y permite trabajar con el
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-02
 */
class Dte
{

    private $tipo; ///< Identificador del tipo de DTE: 33 (factura electrónica)
    private $folio; ///< Folio del documento
    private $xml; ///< Objeto XML que representa el DTE
    private $id; ///< Identificador único del DTE
    private $tipo_general; ///< Tipo general de DTE: Documento, Liquidacion o Exportaciones
    private $timestamp; ///< Timestamp del DTE

    private $tipos = [
        'Documento' => [33, 34, 46, 52, 56, 61],
        'Liquidacion' => [43],
        'Exportaciones' => [110, 111, 112],
    ]; ///< Tipos posibles de documentos tributarios electrónicos

    /**
     * Constructor de la clase DTE
     * @param datos Arreglo con los datos del DTE o el XML completo del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-30
     */
    public function __construct($datos = null)
    {
        if (is_array($datos))
            $this->setDatos($datos);
        else if (is_string($datos))
            $this->loadXML($datos);
        $this->timestamp = date('Y-m-d\TH:i:s');
    }

    /**
     * Método que carga el DTE ya armado desde un archivo XML
     * @param xml String con los datos completos del XML del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-20
     */
    private function loadXML($xml)
    {
        if (!empty($xml)) {
            $this->xml = new \sasco\LibreDTE\XML();
            $this->xml->loadXML($xml);
            $this->tipo = $this->xml->getElementsByTagName('TipoDTE')->item(0)->nodeValue;
            $this->tipo_general = $this->getTipoGeneral($this->tipo);
            $this->folio = $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/IdDoc/Folio')->item(0)->nodeValue;
            $this->id = 'T'.$this->tipo.'F'.$this->folio;
        }
    }

    /**
     * Método que asigna los datos del DTE
     * @param datos Arreglo con los datos del DTE que se quire generar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    private function setDatos(array $datos)
    {
        if (!empty($datos)) {
            if (is_string($datos))
                $datos = json_decode($datos);
            $this->tipo = $datos['Encabezado']['IdDoc']['TipoDTE'];
            $this->folio = $datos['Encabezado']['IdDoc']['Folio'];
            $this->id = 'T'.$this->tipo.'F'.$this->folio;
            $this->normalizar($datos);
            $method = 'normalizar_'.$this->tipo;
            if (method_exists($this, $method))
                $this->$method($datos);
            $this->tipo_general = $this->getTipoGeneral($this->tipo);
            $this->xml = (new \sasco\LibreDTE\XML())->generate([
                'DTE' => [
                    '@attributes' => [
                        'version' => '1.0',
                    ],
                    $this->tipo_general => [
                        '@attributes' => [
                            'ID' => $this->id
                        ],
                    ]
                ]
            ]);
            $parent = $this->xml->getElementsByTagName($this->tipo_general)->item(0);
            $this->xml->generate($datos + ['TED' => null], $parent);
        }
    }

    /**
     * Método que entrega el tipo general de documento, de acuerdo a
     * $this->tipos
     * @param dte Tipo númerico de DTE, ejemplo: 33 (factura electrónica)
     * @return String con el tipo general: Documento, Liquidacion o Exportaciones
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-20
     */
    private function getTipoGeneral($dte = null)
    {
        foreach ($this->tipos as $tipo => $codigos)
            if (in_array($dte, $codigos))
                return $tipo;
        return false;
    }

    /**
     * Método que entrega el tipo de DTE
     * @return Tipo de dte, ej: 33 (factura electrónica)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    public function getTipo()
    {
        return $this->tipo;
    }

    /**
     * Método que entrega el folio del DTE
     * @return Folio del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    public function getFolio()
    {
        return $this->folio;
    }

    /**
     * Método que entrega rut del emisor del DTE
     * @return RUT del emiro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    public function getEmisor()
    {
        return $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Emisor/RUTEmisor')->item(0)->nodeValue;
    }

    /**
     * Método que entrega rut del receptor del DTE
     * @return RUT del emiro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    public function getReceptor()
    {
        return $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Receptor/RUTRecep')->item(0)->nodeValue;
    }

    /**
     * Método que realiza el timbrado del DTE
     * @param Folios Objeto de los Folios con los que se desea timbrar
     * @return =true si se pudo timbrar o =false en caso de error
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    public function timbrar(Folios $Folios)
    {
        $TED = new \sasco\LibreDTE\XML();
        $TED->generate([
            'TED' => [
                '@attributes' => [
                    'version' => '1.0',
                ],
                'DD' => [
                    'RE' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Emisor/RUTEmisor')->item(0)->nodeValue,
                    'TD' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/IdDoc/TipoDTE')->item(0)->nodeValue,
                    'F' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/IdDoc/Folio')->item(0)->nodeValue,
                    'FE' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/IdDoc/FchEmis')->item(0)->nodeValue,
                    'RR' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Receptor/RUTRecep')->item(0)->nodeValue,
                    'RSR' => substr($this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Receptor/RznSocRecep')->item(0)->nodeValue, 0, 40),
                    'MNT' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Totales/MntTotal')->item(0)->nodeValue,
                    'IT1' => substr($this->xml->xpath('/DTE/'.$this->tipo_general.'/Detalle')->item(0)->getElementsByTagName('NmbItem')->item(0)->nodeValue, 0, 40),
                    'CAF' => $Folios->getCaf(),
                    'TSTED' => $this->timestamp,
                ],
                'FRMT' => [
                    '@attributes' => [
                        'algoritmo' => 'SHA1withRSA'
                    ],
                ],
            ]
        ]);
        $DD = $TED->getFlattened('/TED/DD');
        if (openssl_sign($DD, $timbre, $Folios->getPrivateKey(), OPENSSL_ALGO_SHA1)==false)
            return false;
        $TED->getElementsByTagName('FRMT')->item(0)->nodeValue = base64_encode($timbre);
        $xml = str_replace('<TED/>', trim(str_replace('<?xml version="1.0" encoding="ISO-8859-1"?>', '', $TED->saveXML())), $this->saveXML());
        $this->loadXML($xml);
        return true;
    }

    /**
     * Método que realiza la firma del DTE
     * @param Firma objeto que representa la Firma Electrónca
     * @return =true si el DTE pudo ser fimado o =false si no se pudo firmar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    public function firmar(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $parent = $this->xml->getElementsByTagName($this->tipo_general)->item(0);
        $this->xml->generate(['TmstFirma'=>$this->timestamp], $parent);
        $xml = $Firma->signXML($this->xml->saveXML(), '#'.$this->id, $this->tipo_general);
        if (!$xml)
            return false;
        $this->loadXML($xml);
        return true;
    }

    /**
     * Método que entrega el DTE en XML
     * @return XML con el DTE (podría: con o sin timbre y con o sin firma)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-20
     */
    public function saveXML()
    {
        return $this->xml->saveXML();
    }

    /**
     * Método que normaliza los datos de un documento tributario electrónico
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    private function normalizar(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => false,
                    'Folio' => false,
                    'FchEmis' => date('Y-m-d'),
                ],
                'Emisor' => false,
                'Receptor' => false,
            ],
        ], $datos);
        // si existe descuento o recargo global se normalizan
        if (isset($datos['DscRcgGlobal'])) {
            if (!isset($datos['DscRcgGlobal'][0]))
                $datos['DscRcgGlobal'] = [$datos['DscRcgGlobal']];
            $NroLinDR = 1;
            foreach ($datos['DscRcgGlobal'] as &$dr) {
                $dr = array_merge([
                    'NroLinDR' => $NroLinDR++,
                ], $dr);
            }
        }
        // si existe una o más referencias se normalizan
        if (isset($datos['Referencia'])) {
            if (!isset($datos['Referencia'][0]))
                $datos['Referencia'] = [$datos['Referencia']];
            $NroLinRef = 1;
            foreach ($datos['Referencia'] as &$r) {
                $r = array_merge([
                    'NroLinRef' => $NroLinRef++,
                    'TpoDocRef' => false,
                    'FolioRef' => false,
                    'FchRef' => date('Y-m-d'),
                ], $r);
            }
        }
    }

    /**
     * Método que normaliza los datos de una factura electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-03
     */
    private function normalizar_33(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => false,
                    'TasaIVA' => 19,
                    'IVA' => 0,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // procesar cada detalle
        if (!isset($datos['Detalle'][0]))
            $datos['Detalle'] = [$datos['Detalle']];
        $item = 1;
        foreach ($datos['Detalle'] as &$d) {
            $d = array_merge([
                'NroLinDet' => $item++,
                'IndExe' => false,
                'NmbItem' => false,
                'QtyItem' => false,
                'PrcItem' => false,
                'DescuentoPct' => false,
                'DescuentoMonto' => false,
            ], $d);
            if (!isset($d['MontoItem'])) {
                $d['MontoItem'] = $d['QtyItem'] * $d['PrcItem'];
                $DescuentoPct = $d['DescuentoPct'] ? $d['DescuentoPct'] : 0;
                if ($DescuentoPct) {
                    $d['DescuentoMonto'] = round($d['MontoItem'] * $DescuentoPct/100);
                    $d['MontoItem'] = $d['MontoItem'] - $d['DescuentoMonto'];
                }
            }
            if ($d['IndExe']) {
                if ($d['IndExe']==1) {
                    $datos['Encabezado']['Totales']['MntExe'] += $d['MontoItem'];
                }
            } else {
                $datos['Encabezado']['Totales']['MntNeto'] += $d['MontoItem'];
            }
        }
        // aplicar descuento y/o recargo global
        if (isset($datos['DscRcgGlobal'])) {
            foreach ($datos['DscRcgGlobal'] as $dr) {
                $valor = $dr['TpoValor']=='%' ? (($dr['ValorDR']/100)*$datos['Encabezado']['Totales']['MntNeto']) : $dr['ValorDR'];
                // aplicar descuento
                if ($dr['TpoMov']=='D') {
                    $datos['Encabezado']['Totales']['MntNeto'] -= $valor;
                }
                // aplicar recargo
                else if ($dr['TpoMov']=='R') {
                    $datos['Encabezado']['Totales']['MntNeto'] += $valor;
                }
            }
            $datos['Encabezado']['Totales']['MntNeto'] = round($datos['Encabezado']['Totales']['MntNeto']);
        }
        // determinar IVA y monto total
        $datos['Encabezado']['Totales']['IVA'] = round($datos['Encabezado']['Totales']['MntNeto']*($datos['Encabezado']['Totales']['TasaIVA']/100));
        $datos['Encabezado']['Totales']['MntTotal'] = $datos['Encabezado']['Totales']['MntNeto'] + $datos['Encabezado']['Totales']['IVA'] + $datos['Encabezado']['Totales']['MntExe'];
    }

}
