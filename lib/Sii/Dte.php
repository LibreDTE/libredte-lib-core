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

namespace sasco\LibreDTE\Sii;

/**
 * Clase que representa un DTE y permite trabajar con el
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-08-29
 */
class Dte
{

    private $tipo; ///< Identificador del tipo de DTE: 33 (factura electrónica)
    private $folio; ///< Folio del documento
    private $xml; ///< Objeto XML que representa el DTE
    private $id; ///< Identificador único del DTE
    private $tipo_general; ///< Tipo general de DTE: Documento, Liquidacion o Exportaciones
    private $timestamp; ///< Timestamp del DTE
    private $datos = null; ///< Datos normalizados que se usaron para crear el DTE
    private $Signature = null; ///< Datos de la firma del DTE

    private $tipos = [
        'Documento' => [33, 34, 39, 41, 46, 52, 56, 61],
        'Liquidacion' => [43],
        'Exportaciones' => [110, 111, 112],
    ]; ///< Tipos posibles de documentos tributarios electrónicos

    private $noCedibles = [39, 41, 56, 61, 110, 111, 112]; ///< Documentos que no son cedibles

    /**
     * Constructor de la clase DTE
     * @param datos Arreglo con los datos del DTE o el XML completo del DTE
     * @param normalizar Si se pasa un arreglo permitirá indicar si el mismo se debe o no normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-03
     */
    public function __construct($datos, $normalizar = true)
    {
        if (is_array($datos))
            $this->setDatos($datos, $normalizar);
        else if (is_string($datos))
            $this->loadXML($datos);
        $this->timestamp = date('Y-m-d\TH:i:s');
    }

    /**
     * Método que carga el DTE ya armado desde un archivo XML
     * @param xml String con los datos completos del XML del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-09-01
     */
    private function loadXML($xml)
    {
        if (!empty($xml)) {
            $this->xml = new \sasco\LibreDTE\XML();
            if (!$this->xml->loadXML($xml) or !$this->schemaValidate()) {
                \sasco\LibreDTE\Log::write(
                    \sasco\LibreDTE\Estado::DTE_ERROR_LOADXML,
                    \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_LOADXML)
                );
                return false;
            }
            $TipoDTE = $this->xml->getElementsByTagName('TipoDTE')->item(0);
            if (!$TipoDTE) {
                return false;
            }
            $this->tipo = $TipoDTE->nodeValue;
            $this->tipo_general = $this->getTipoGeneral($this->tipo);
            if (!$this->tipo_general) {
                return false;
            }
            $Folio = $this->xml->getElementsByTagName('Folio')->item(0);
            if (!$Folio) {
                return false;
            }
            $this->folio = $Folio->nodeValue;
            if (isset($this->getDatos()['@attributes'])) {
                $this->id = $this->getDatos()['@attributes']['ID'];
            } else {
                $this->id = 'LibreDTE_T'.$this->tipo.'F'.$this->folio;
            }
            return true;
        }
        return false;
    }

    /**
     * Método que asigna los datos del DTE
     * @param datos Arreglo con los datos del DTE que se quire generar
     * @param normalizar Si se pasa un arreglo permitirá indicar si el mismo se debe o no normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-22
     */
    private function setDatos(array $datos, $normalizar = true)
    {
        if (!empty($datos)) {
            $this->tipo = $datos['Encabezado']['IdDoc']['TipoDTE'];
            $this->folio = $datos['Encabezado']['IdDoc']['Folio'];
            $this->id = 'LibreDTE_T'.$this->tipo.'F'.$this->folio;
            if ($normalizar) {
                $this->normalizar($datos);
                $method = 'normalizar_'.$this->tipo;
                if (method_exists($this, $method))
                    $this->$method($datos);
                $this->normalizar_final($datos);
            }
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
            $this->xml->generate($datos + ['TED' => null], null, $parent);
            $this->datos = $datos;
            if ($normalizar and !$this->verificarDatos()) {
                return false;
            }
            return $this->schemaValidate();
        }
        return false;
    }

    /**
     * Método que entrega el arreglo con los datos del DTE.
     * Si el DTE fue creado a partir de un arreglo serán los datos normalizados,
     * en cambio si se creó a partir de un XML serán todos los nodos del
     * documento sin cambios.
     * @return Arreglo con datos del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-04
     */
    public function getDatos()
    {
        if (!$this->datos) {
            $datos = $this->xml->toArray();
            if (!isset($datos['DTE'][$this->tipo_general])) {
                \sasco\LibreDTE\Log::write(
                    \sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS,
                    \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_GETDATOS)
                );
                return false;
            }
            $this->datos = $datos['DTE'][$this->tipo_general];
            if (isset($datos['DTE']['Signature'])) {
                $this->Signature = $datos['DTE']['Signature'];
            }
        }
        return $this->datos;
    }

    /**
     * Método que entrega el arreglo con los datos de la firma del DTE
     * @return Arreglo con datos de la firma
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-06-11
     */
    public function getFirma()
    {
        if (!$this->Signature) {
            $this->getDatos();
        }
        return $this->Signature;
    }

    /**
     * Método que entrega los datos del DTE (tag Documento) como un string JSON
     * @return String JSON "lindo" con los datos del documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-08
     */
    public function getJSON()
    {
        if (!$this->getDatos())
            return false;
        return json_encode($this->datos, JSON_PRETTY_PRINT);
    }

    /**
     * Método que entrega el ID del documento
     * @return String con el ID del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-17
     */
    public function getID($estandar = false)
    {
        return $estandar ? ('T'.$this->tipo.'F'.$this->folio) : $this->id;
    }

    /**
     * Método que entrega el tipo general de documento, de acuerdo a
     * $this->tipos
     * @param dte Tipo númerico de DTE, ejemplo: 33 (factura electrónica)
     * @return String con el tipo general: Documento, Liquidacion o Exportaciones
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    private function getTipoGeneral($dte)
    {
        foreach ($this->tipos as $tipo => $codigos)
            if (in_array($dte, $codigos))
                return $tipo;
        \sasco\LibreDTE\Log::write(
            \sasco\LibreDTE\Estado::DTE_ERROR_TIPO,
            \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_TIPO, $dte)
        );
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
     * @version 2015-09-07
     */
    public function getEmisor()
    {
        $nodo = $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Emisor/RUTEmisor')->item(0);
        if ($nodo)
            return $nodo->nodeValue;
        if (!$this->getDatos())
            return false;
        return $this->datos['Encabezado']['Emisor']['RUTEmisor'];
    }

    /**
     * Método que entrega rut del receptor del DTE
     * @return RUT del emiro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getReceptor()
    {
        $nodo = $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Receptor/RUTRecep')->item(0);
        if ($nodo)
            return $nodo->nodeValue;
        if (!$this->getDatos())
            return false;
        return $this->datos['Encabezado']['Receptor']['RUTRecep'];
    }

    /**
     * Método que entrega fecha de emisión del DTE
     * @return Fecha de emisión en formato AAAA-MM-DD
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getFechaEmision()
    {
        $nodo = $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/IdDoc/FchEmis')->item(0);
        if ($nodo)
            return $nodo->nodeValue;
        if (!$this->getDatos())
            return false;
        return $this->datos['Encabezado']['IdDoc']['FchEmis'];
    }

    /**
     * Método que entrega el monto total del DTE
     * @return Monto total del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public function getMontoTotal()
    {
        $nodo = $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Totales/MntTotal')->item(0);
        if ($nodo)
            return $nodo->nodeValue;
        if (!$this->getDatos())
            return false;
        return $this->datos['Encabezado']['Totales']['MntTotal'];
    }

    /**
     * Método que entrega el tipo de moneda del documento
     * @return String con el tipo de moneda
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-16
     */
    public function getMoneda()
    {
        $nodo = $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Totales/TpoMoneda')->item(0);
        if ($nodo)
            return $nodo->nodeValue;
        if (!$this->getDatos())
            return false;
        return $this->datos['Encabezado']['Totales']['TpoMoneda'];
    }

    /**
     * Método que entrega las referencias del DTE si existen
     * @return Arreglo con las referencias
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-11-17
     */
    public function getReferencias()
    {
        if (!$this->getDatos()) {
            return false;
        }
        $referencias = !empty($this->datos['Referencia']) ? $this->datos['Referencia'] : false;
        if (!$referencias) {
            return [];
        }
        if (!isset($referencias[0])) {
            $referencias = [$referencias];
        }
        return $referencias;
    }

    /**
     * Método que entrega el string XML del tag TED
     * @return String XML con tag TED
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-03
     */
    public function getTED()
    {
        /*$xml = new \sasco\LibreDTE\XML();
        $xml->loadXML($this->xml->getElementsByTagName('TED')->item(0)->getElementsByTagName('DD')->item(0)->C14N());
        $xml->documentElement->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi');
        $xml->documentElement->removeAttributeNS('http://www.sii.cl/SiiDte', '');
        $FRMT = $this->xml->getElementsByTagName('TED')->item(0)->getElementsByTagName('FRMT')->item(0)->nodeValue;
        $pub_key = '';
        if (openssl_verify($xml->getFlattened('/'), base64_decode($FRMT), $pub_key, OPENSSL_ALGO_SHA1)!==1);
            return false;*/
        $xml = new \sasco\LibreDTE\XML();
        $TED = $this->xml->getElementsByTagName('TED')->item(0);
        if (!$TED)
            return '<TED/>';
        $xml->loadXML($TED->C14N());
        $xml->documentElement->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi');
        $xml->documentElement->removeAttributeNS('http://www.sii.cl/SiiDte', '');
        $TED = $xml->getFlattened('/');
        return mb_detect_encoding($TED, ['UTF-8', 'ISO-8859-1']) != 'ISO-8859-1' ? utf8_decode($TED) : $TED;
    }

    /**
     * Método que indica si el DTE es de certificación o no
     * @return =true si el DTE es de certificación, =null si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-06-15
     */
    public function getCertificacion()
    {
        $datos = $this->getDatos();
        $idk = !empty($datos['TED']['DD']['CAF']['DA']['IDK']) ? (int)$datos['TED']['DD']['CAF']['DA']['IDK'] : null;
        return $idk ? $idk === 100 : null;
    }

    /**
     * Método que realiza el timbrado del DTE
     * @param Folios Objeto de los Folios con los que se desea timbrar
     * @return =true si se pudo timbrar o =false en caso de error
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-09-01
     */
    public function timbrar(Folios $Folios)
    {
        // verificar que el folio que se está usando para el DTE esté dentro
        // del rango de folios autorizados que se usarán para timbrar
        // Esta validación NO verifica si el folio ya fue usado, sólo si está
        // dentro del CAF que se está usando
        $folio = $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/IdDoc/Folio')->item(0)->nodeValue;
        if ($folio<$Folios->getDesde() or $folio>$Folios->getHasta()) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_RANGO_FOLIO,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_RANGO_FOLIO, $this->getID())
            );
            return false;
        }
        // verificar que existan datos para el timbre
        if (!$this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/IdDoc/FchEmis')->item(0)) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_FALTA_FCHEMIS,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_FALTA_FCHEMIS, $this->getID())
            );
            \sasco\LibreDTE\Log::write('Falta FchEmis del DTE '.$this->getID());
            return false;
        }
        if (!$this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Totales/MntTotal')->item(0)) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_FALTA_MNTTOTAL,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_FALTA_MNTTOTAL, $this->getID())
            );
            return false;
        }
        // timbrar
        $RR = $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Receptor/RUTRecep')->item(0)->nodeValue;
        $RSR_nodo = $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Receptor/RznSocRecep');
        $RSR = $RSR_nodo->length ? trim(mb_substr($RSR_nodo->item(0)->nodeValue, 0, 40)) : $RR;
        $TED = new \sasco\LibreDTE\XML();
        $TED->generate([
            'TED' => [
                '@attributes' => [
                    'version' => '1.0',
                ],
                'DD' => [
                    'RE' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Emisor/RUTEmisor')->item(0)->nodeValue,
                    'TD' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/IdDoc/TipoDTE')->item(0)->nodeValue,
                    'F' => $folio,
                    'FE' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/IdDoc/FchEmis')->item(0)->nodeValue,
                    'RR' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Receptor/RUTRecep')->item(0)->nodeValue,
                    'RSR' => $RSR,
                    'MNT' => $this->xml->xpath('/DTE/'.$this->tipo_general.'/Encabezado/Totales/MntTotal')->item(0)->nodeValue,
                    'IT1' => trim(mb_substr($this->xml->xpath('/DTE/'.$this->tipo_general.'/Detalle')->item(0)->getElementsByTagName('NmbItem')->item(0)->nodeValue, 0, 40)),
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
        if (openssl_sign($DD, $timbre, $Folios->getPrivateKey(), OPENSSL_ALGO_SHA1)==false) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_TIMBRE,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_TIMBRE, $this->getID())
            );
            return false;
        }
        $TED->getElementsByTagName('FRMT')->item(0)->nodeValue = base64_encode($timbre);
        $xml = str_replace('<TED/>', trim(str_replace('<?xml version="1.0" encoding="ISO-8859-1"?>', '', $TED->saveXML())), $this->saveXML());
        if (!$this->loadXML($xml)) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_TIMBRE,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_TIMBRE, $this->getID())
            );
            return false;
        }
        return true;
    }

    /**
     * Método que realiza la firma del DTE
     * @param Firma objeto que representa la Firma Electrónca
     * @return =true si el DTE pudo ser fimado o =false si no se pudo firmar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-22
     */
    public function firmar(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $parent = $this->xml->getElementsByTagName($this->tipo_general)->item(0);
        $this->xml->generate(['TmstFirma'=>$this->timestamp], null, $parent);
        $xml = $Firma->signXML($this->xml->saveXML(), '#'.$this->id, $this->tipo_general);
        if (!$xml) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_FIRMA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_FIRMA, $this->getID())
            );
            return false;
        }
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
     * Método que genera un arreglo con el resumen del documento. Este resumen
     * puede servir, por ejemplo, para generar los detalles de los IECV
     * @return Arreglo con el resumen del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-15
     */
    public function getResumen()
    {
        $this->getDatos();
        // generar resumen
        $resumen =  [
            'TpoDoc' => (int)$this->datos['Encabezado']['IdDoc']['TipoDTE'],
            'NroDoc' => (int)$this->datos['Encabezado']['IdDoc']['Folio'],
            'TasaImp' => 0,
            'FchDoc' => $this->datos['Encabezado']['IdDoc']['FchEmis'],
            'CdgSIISucur' => !empty($this->datos['Encabezado']['Emisor']['CdgSIISucur']) ? $this->datos['Encabezado']['Emisor']['CdgSIISucur'] : false,
            'RUTDoc' => $this->datos['Encabezado']['Receptor']['RUTRecep'],
            'RznSoc' => isset($this->datos['Encabezado']['Receptor']['RznSocRecep']) ? $this->datos['Encabezado']['Receptor']['RznSocRecep'] : false,
            'MntExe' => false,
            'MntNeto' => false,
            'MntIVA' => 0,
            'MntTotal' => 0,
        ];
        // obtener montos si es que existen en el documento
        $montos = ['TasaImp'=>'TasaIVA', 'MntExe'=>'MntExe', 'MntNeto'=>'MntNeto', 'MntIVA'=>'IVA', 'MntTotal'=>'MntTotal'];
        foreach ($montos as $dest => $orig) {
            if (!empty($this->datos['Encabezado']['Totales'][$orig])) {
                $resumen[$dest] = !$this->esExportacion() ? round($this->datos['Encabezado']['Totales'][$orig]) : $this->datos['Encabezado']['Totales'][$orig];
            }
        }
        // si es una boleta se calculan los datos para el resumen
        if ($this->esBoleta()) {
            if (!$resumen['TasaImp']) {
                $resumen['TasaImp'] = \sasco\LibreDTE\Sii::getIVA();
            }
            $resumen['MntExe'] = (int)$resumen['MntExe'];
            if (!$resumen['MntNeto']) {
                list($resumen['MntNeto'], $resumen['MntIVA']) = $this->calcularNetoIVA($resumen['MntTotal']-$resumen['MntExe'], $resumen['TasaImp']);
            }
        }
        // entregar resumen
        return $resumen;
    }

    /**
     * Método que permite obtener el monto neto y el IVA de ese neto a partir de
     * un monto total
     * @param total neto + iva
     * @param tasa Tasa del IVA
     * @return Arreglo con el neto y el iva
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function calcularNetoIVA($total, $tasa = null)
    {
        if ($tasa === 0 or $tasa === false)
            return [0, 0];
        if ($tasa === null)
            $tasa = \sasco\LibreDTE\Sii::getIVA();
        // WARNING: el IVA obtenido puede no ser el NETO*(TASA/100)
        // se calcula el monto neto y luego se obtiene el IVA haciendo la resta
        // entre el total y el neto, ya que hay casos de borde como:
        //  - BRUTO:   680 => NETO:   571 e IVA:   108 => TOTAL:   679
        //  - BRUTO: 86710 => NETO: 72866 e IVA: 13845 => TOTAL: 86711
        $neto = round($total / (1+($tasa/100)));
        $iva = $total - $neto;
        return [$neto, $iva];
    }

    /**
     * Método que normaliza los datos de un documento tributario electrónico
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-06-25
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
                    'IndNoRebaja' => false,
                    'TipoDespacho' => false,
                    'IndTraslado' => false,
                    'TpoImpresion' => false,
                    'IndServicio' => $this->esBoleta() ? 3 : false,
                    'MntBruto' => false,
                    'TpoTranCompra' => false,
                    'TpoTranVenta' => false,
                    'FmaPago' => false,
                    'FmaPagExp' => false,
                    'MntCancel' => false,
                    'SaldoInsol' => false,
                    'FchCancel' => false,
                    'MntPagos' => false,
                    'PeriodoDesde' => false,
                    'PeriodoHasta' => false,
                    'MedioPago' => false,
                    'TpoCtaPago' => false,
                    'NumCtaPago' => false,
                    'BcoPago' => false,
                    'TermPagoCdg' => false,
                    'TermPagoGlosa' => false,
                    'TermPagoDias' => false,
                    'FchVenc' => false,
                ],
                'Emisor' => [
                    'RUTEmisor' => false,
                    'RznSoc' => false,
                    'GiroEmis' => false,
                    'Telefono' => false,
                    'CorreoEmisor' => false,
                    'Acteco' => false,
                    'Sucursal' => false,
                    'CdgSIISucur' => false,
                    'DirOrigen' => false,
                    'CmnaOrigen' => false,
                    'CiudadOrigen' => false,
                    'CdgVendedor' => false,
                ],
                'Receptor' => [
                    'RUTRecep' => false,
                    'CdgIntRecep' => false,
                    'RznSocRecep' => false,
                    'Extranjero' => false,
                    'GiroRecep' => false,
                    'Contacto' => false,
                    'CorreoRecep' => false,
                    'DirRecep' => false,
                    'CmnaRecep' => false,
                    'CiudadRecep' => false,
                    'DirPostal' => false,
                    'CmnaPostal' => false,
                    'CiudadPostal' => false,
                ],
                'Totales' => [
                    'TpoMoneda' => false,
                ],
            ],
            'Detalle' => false,
            'SubTotInfo' => false,
            'DscRcgGlobal' => false,
            'Referencia' => false,
            'Comisiones' => false,
        ], $datos);
        // corregir algunos datos que podrían venir malos para no caer por schema
        $datos['Encabezado']['Emisor']['RUTEmisor'] = strtoupper(trim(str_replace('.', '', $datos['Encabezado']['Emisor']['RUTEmisor'])));
        $datos['Encabezado']['Receptor']['RUTRecep'] = strtoupper(trim(str_replace('.', '', $datos['Encabezado']['Receptor']['RUTRecep'])));
        $datos['Encabezado']['Receptor']['RznSocRecep'] = mb_substr($datos['Encabezado']['Receptor']['RznSocRecep'], 0, 100);
        if (!empty($datos['Encabezado']['Receptor']['GiroRecep'])) {
            $datos['Encabezado']['Receptor']['GiroRecep'] = mb_substr($datos['Encabezado']['Receptor']['GiroRecep'], 0, 40);
        }
        if (!empty($datos['Encabezado']['Receptor']['Contacto'])) {
            $datos['Encabezado']['Receptor']['Contacto'] = mb_substr($datos['Encabezado']['Receptor']['Contacto'], 0, 80);
        }
        if (!empty($datos['Encabezado']['Receptor']['CorreoRecep'])) {
            $datos['Encabezado']['Receptor']['CorreoRecep'] = mb_substr($datos['Encabezado']['Receptor']['CorreoRecep'], 0, 80);
        }
        if (!empty($datos['Encabezado']['Receptor']['DirRecep'])) {
            $datos['Encabezado']['Receptor']['DirRecep'] = mb_substr($datos['Encabezado']['Receptor']['DirRecep'], 0, 70);
        }
        if (!empty($datos['Encabezado']['Receptor']['CmnaRecep'])) {
            $datos['Encabezado']['Receptor']['CmnaRecep'] = mb_substr($datos['Encabezado']['Receptor']['CmnaRecep'], 0, 20);
        }
        if (!empty($datos['Encabezado']['Emisor']['Acteco'])) {
            if (strlen((string)$datos['Encabezado']['Emisor']['Acteco'])==5) {
                $datos['Encabezado']['Emisor']['Acteco'] = '0'.$datos['Encabezado']['Emisor']['Acteco'];
            }
        }
        // si existe descuento o recargo global se normalizan
        if (!empty($datos['DscRcgGlobal'])) {
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
        if (!empty($datos['Referencia'])) {
            if (!isset($datos['Referencia'][0])) {
                $datos['Referencia'] = [$datos['Referencia']];
            }
            $NroLinRef = 1;
            foreach ($datos['Referencia'] as &$r) {
                $r = array_merge([
                    'NroLinRef' => $NroLinRef++,
                    'TpoDocRef' => false,
                    'IndGlobal' => false,
                    'FolioRef' => false,
                    'RUTOtr' => false,
                    'FchRef' => date('Y-m-d'),
                    'CodRef' => false,
                    'RazonRef' => false,
                ], $r);
            }
        }
        // verificar que exista TpoTranVenta
        if (!in_array($datos['Encabezado']['IdDoc']['TipoDTE'], [39, 41, 110, 111, 112]) and empty($datos['Encabezado']['IdDoc']['TpoTranVenta'])) {
            $datos['Encabezado']['IdDoc']['TpoTranVenta'] = 1; // ventas del giro
        }
    }

    /**
     * Método que realiza la normalización final de los datos de un documento
     * tributario electrónico. Esto se aplica todos los documentos una vez que
     * ya se aplicaron las normalizaciones por tipo
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-23
     */
    private function normalizar_final(array &$datos)
    {
        // normalizar montos de pagos programados
        if (is_array($datos['Encabezado']['IdDoc']['MntPagos'])) {
            $montos = 0;
            if (!isset($datos['Encabezado']['IdDoc']['MntPagos'][0])) {
                $datos['Encabezado']['IdDoc']['MntPagos'] = [$datos['Encabezado']['IdDoc']['MntPagos']];
            }
            foreach ($datos['Encabezado']['IdDoc']['MntPagos'] as &$MntPagos) {
                $MntPagos = array_merge([
                    'FchPago' => null,
                    'MntPago' => null,
                    'GlosaPagos' => false,
                ], $MntPagos);
                if ($MntPagos['MntPago']===null) {
                    $MntPagos['MntPago'] = $datos['Encabezado']['Totales']['MntTotal'];
                }
            }
        }
        // si existe OtraMoneda se verifican los tipos de cambio y totales
        if (!empty($datos['Encabezado']['OtraMoneda'])) {
            if (!isset($datos['Encabezado']['OtraMoneda'][0])) {
                $datos['Encabezado']['OtraMoneda'] = [$datos['Encabezado']['OtraMoneda']];
            }
            foreach ($datos['Encabezado']['OtraMoneda'] as &$OtraMoneda) {
                // colocar campos por defecto
                $OtraMoneda = array_merge([
                    'TpoMoneda' => false,
                    'TpoCambio' => false,
                    'MntNetoOtrMnda' => false,
                    'MntExeOtrMnda' => false,
                    'MntFaeCarneOtrMnda' => false,
                    'MntMargComOtrMnda' => false,
                    'IVAOtrMnda' => false,
                    'ImpRetOtrMnda' => false,
                    'IVANoRetOtrMnda' => false,
                    'MntTotOtrMnda' => false,
                ], $OtraMoneda);
                // si no hay tipo de cambio no seguir
                if (!isset($OtraMoneda['TpoCambio'])) {
                    continue;
                }
                // buscar si los valores están asignados, si no lo están asignar
                // usando el tipo de cambio que existe para la moneda
                foreach (['MntNeto', 'MntExe', 'IVA', 'IVANoRet'] as $monto) {
                    if (empty($OtraMoneda[$monto.'OtrMnda']) and !empty($datos['Encabezado']['Totales'][$monto])) {
                        $OtraMoneda[$monto.'OtrMnda'] = round($datos['Encabezado']['Totales'][$monto] * $OtraMoneda['TpoCambio'], 4);
                    }
                }
                // calcular MntFaeCarneOtrMnda, MntMargComOtrMnda, ImpRetOtrMnda
                if (empty($OtraMoneda['MntFaeCarneOtrMnda'])) {
                    $OtraMoneda['MntFaeCarneOtrMnda'] = false; // TODO
                }
                if (empty($OtraMoneda['MntMargComOtrMnda'])) {
                    $OtraMoneda['MntMargComOtrMnda'] = false; // TODO
                }
                if (empty($OtraMoneda['ImpRetOtrMnda'])) {
                    $OtraMoneda['ImpRetOtrMnda'] = false; // TODO
                }
                // calcular monto total
                if (empty($OtraMoneda['MntTotOtrMnda'])) {
                    $OtraMoneda['MntTotOtrMnda'] = 0;
                    $cols = ['MntNetoOtrMnda', 'MntExeOtrMnda', 'MntFaeCarneOtrMnda', 'MntMargComOtrMnda', 'IVAOtrMnda', 'IVANoRetOtrMnda'];
                    foreach ($cols as $monto) {
                        if (!empty($OtraMoneda[$monto])) {
                            $OtraMoneda['MntTotOtrMnda'] += $OtraMoneda[$monto];
                        }
                    }
                    // agregar total de impuesto retenido otra moneda
                    if (!empty($OtraMoneda['ImpRetOtrMnda'])) {
                        // TODO
                    }
                    // aproximar el total si es en pesos chilenos
                    if ($OtraMoneda['TpoMoneda']=='PESO CL') {
                        $OtraMoneda['MntTotOtrMnda'] = round($OtraMoneda['MntTotOtrMnda']);
                    }
                }
                // si el tipo de cambio es 0, se quita
                if ($OtraMoneda['TpoCambio']==0) {
                    $OtraMoneda['TpoCambio'] = false;
                }
            }
        }
    }

    /**
     * Método que normaliza los datos de una factura electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-01
     */
    private function normalizar_33(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'RUTMandante' => false,
                'Receptor' => false,
                'RUTSolicita' => false,
                'Transporte' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => false,
                    'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                    'IVA' => 0,
                    'ImptoReten' => false,
                    'CredEC' => false,
                    'MntTotal' => 0,
                ],
                'OtraMoneda' => false,
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_impuesto_retenido($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
        $this->normalizar_transporte($datos);
    }

    /**
     * Método que normaliza los datos de una factura exenta electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-02-23
     */
    private function normalizar_34(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'RUTSolicita' => false,
                'Totales' => [
                    'MntExe' => false,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
    }

    /**
     * Método que normaliza los datos de una boleta electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-14
     */
    private function normalizar_39(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => [
                    'RUTEmisor' => false,
                    'RznSocEmisor' => false,
                    'GiroEmisor' => false,
                ],
                'Receptor' => false,
                'Totales' => [
                    'MntExe' => false,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_boletas($datos);
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
    }

    /**
     * Método que normaliza los datos de una boleta exenta electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-14
     */
    private function normalizar_41(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => [
                    'RUTEmisor' => false,
                    'RznSocEmisor' => false,
                    'GiroEmisor' => false,
                ],
                'Receptor' => false,
                'Totales' => [
                    'MntExe' => 0,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_boletas($datos);
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
    }

    /**
     * Método que normaliza los datos de una factura de compra electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-02-26
     */
    private function normalizar_46(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'RUTSolicita' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => false,
                    'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                    'IVA' => 0,
                    'ImptoReten' => false,
                    'IVANoRet' => false,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_impuesto_retenido($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
    }

    /**
     * Método que normaliza los datos de una guía de despacho electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-01
     */
    private function normalizar_52(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'RUTSolicita' => false,
                'Transporte' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => false,
                    'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                    'IVA' => 0,
                    'ImptoReten' => false,
                    'CredEC' => false,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // si es traslado interno se copia el emisor en el receptor sólo si el
        // receptor no está definido o bien si el receptor tiene RUT diferente
        // al emisor
        if ($datos['Encabezado']['IdDoc']['IndTraslado']==5) {
            if (!$datos['Encabezado']['Receptor'] or $datos['Encabezado']['Receptor']['RUTRecep']!=$datos['Encabezado']['Emisor']['RUTEmisor']) {
                $datos['Encabezado']['Receptor'] = [];
                $cols = [
                    'RUTEmisor'=>'RUTRecep',
                    'RznSoc'=>'RznSocRecep',
                    'GiroEmis'=>'GiroRecep',
                    'Telefono'=>'Contacto',
                    'CorreoEmisor'=>'CorreoRecep',
                    'DirOrigen'=>'DirRecep',
                    'CmnaOrigen'=>'CmnaRecep',
                ];
                foreach ($cols as $emisor => $receptor) {
                    if (!empty($datos['Encabezado']['Emisor'][$emisor])) {
                        $datos['Encabezado']['Receptor'][$receptor] = $datos['Encabezado']['Emisor'][$emisor];
                    }
                }
                if (!empty($datos['Encabezado']['Receptor']['GiroRecep'])) {
                    $datos['Encabezado']['Receptor']['GiroRecep'] = mb_substr($datos['Encabezado']['Receptor']['GiroRecep'], 0, 40);
                }
            }
        }
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_impuesto_retenido($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
        $this->normalizar_transporte($datos);
    }

    /**
     * Método que normaliza los datos de una nota de débito
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-02-23
     */
    private function normalizar_56(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'RUTSolicita' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => 0,
                    'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                    'IVA' => false,
                    'ImptoReten' => false,
                    'IVANoRet' => false,
                    'CredEC' => false,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_impuesto_retenido($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
        if (!$datos['Encabezado']['Totales']['MntNeto']) {
            $datos['Encabezado']['Totales']['MntNeto'] = 0;
            $datos['Encabezado']['Totales']['TasaIVA'] = false;
        }
    }

    /**
     * Método que normaliza los datos de una nota de crédito
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-02-23
     */
    private function normalizar_61(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'RUTSolicita' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => 0,
                    'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                    'IVA' => false,
                    'ImptoReten' => false,
                    'IVANoRet' => false,
                    'CredEC' => false,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_impuesto_retenido($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
        if (!$datos['Encabezado']['Totales']['MntNeto']) {
            $datos['Encabezado']['Totales']['MntNeto'] = 0;
            $datos['Encabezado']['Totales']['TasaIVA'] = false;
        }
    }

    /**
     * Método que normaliza los datos de una factura electrónica de exportación
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function normalizar_110(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'Transporte' => [
                    'Patente' => false,
                    'RUTTrans' => false,
                    'Chofer' => false,
                    'DirDest' => false,
                    'CmnaDest' => false,
                    'CiudadDest' => false,
                    'Aduana' => [
                        'CodModVenta' => false,
                        'CodClauVenta' => false,
                        'TotClauVenta' => false,
                        'CodViaTransp' => false,
                        'NombreTransp' => false,
                        'RUTCiaTransp' => false,
                        'NomCiaTransp' => false,
                        'IdAdicTransp' => false,
                        'Booking' => false,
                        'Operador' => false,
                        'CodPtoEmbarque' => false,
                        'IdAdicPtoEmb' => false,
                        'CodPtoDesemb' => false,
                        'IdAdicPtoDesemb' => false,
                        'Tara' => false,
                        'CodUnidMedTara' => false,
                        'PesoBruto' => false,
                        'CodUnidPesoBruto' => false,
                        'PesoNeto' => false,
                        'CodUnidPesoNeto' => false,
                        'TotItems' => false,
                        'TotBultos' => false,
                        'TipoBultos' => false,
                        'MntFlete' => false,
                        'MntSeguro' => false,
                        'CodPaisRecep' => false,
                        'CodPaisDestin' => false,
                    ],
                ],
                'Totales' => [
                    'TpoMoneda' => null,
                    'MntExe' => 0,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_impuesto_retenido($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
        $this->normalizar_exportacion($datos);
    }

    /**
     * Método que normaliza los datos de una nota de débito de exportación
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function normalizar_111(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'Transporte' => [
                    'Patente' => false,
                    'RUTTrans' => false,
                    'Chofer' => false,
                    'DirDest' => false,
                    'CmnaDest' => false,
                    'CiudadDest' => false,
                    'Aduana' => [
                        'CodModVenta' => false,
                        'CodClauVenta' => false,
                        'TotClauVenta' => false,
                        'CodViaTransp' => false,
                        'NombreTransp' => false,
                        'RUTCiaTransp' => false,
                        'NomCiaTransp' => false,
                        'IdAdicTransp' => false,
                        'Booking' => false,
                        'Operador' => false,
                        'CodPtoEmbarque' => false,
                        'IdAdicPtoEmb' => false,
                        'CodPtoDesemb' => false,
                        'IdAdicPtoDesemb' => false,
                        'Tara' => false,
                        'CodUnidMedTara' => false,
                        'PesoBruto' => false,
                        'CodUnidPesoBruto' => false,
                        'PesoNeto' => false,
                        'CodUnidPesoNeto' => false,
                        'TotItems' => false,
                        'TotBultos' => false,
                        'TipoBultos' => false,
                        'MntFlete' => false,
                        'MntSeguro' => false,
                        'CodPaisRecep' => false,
                        'CodPaisDestin' => false,
                    ],
                ],
                'Totales' => [
                    'TpoMoneda' => null,
                    'MntExe' => 0,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_impuesto_retenido($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
        $this->normalizar_exportacion($datos);
    }

    /**
     * Método que normaliza los datos de una nota de crédito de exportación
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function normalizar_112(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'Transporte' => [
                    'Patente' => false,
                    'RUTTrans' => false,
                    'Chofer' => false,
                    'DirDest' => false,
                    'CmnaDest' => false,
                    'CiudadDest' => false,
                    'Aduana' => [
                        'CodModVenta' => false,
                        'CodClauVenta' => false,
                        'TotClauVenta' => false,
                        'CodViaTransp' => false,
                        'NombreTransp' => false,
                        'RUTCiaTransp' => false,
                        'NomCiaTransp' => false,
                        'IdAdicTransp' => false,
                        'Booking' => false,
                        'Operador' => false,
                        'CodPtoEmbarque' => false,
                        'IdAdicPtoEmb' => false,
                        'CodPtoDesemb' => false,
                        'IdAdicPtoDesemb' => false,
                        'Tara' => false,
                        'CodUnidMedTara' => false,
                        'PesoBruto' => false,
                        'CodUnidPesoBruto' => false,
                        'PesoNeto' => false,
                        'CodUnidPesoNeto' => false,
                        'TotItems' => false,
                        'TotBultos' => false,
                        'TipoBultos' => false,
                        'MntFlete' => false,
                        'MntSeguro' => false,
                        'CodPaisRecep' => false,
                        'CodPaisDestin' => false,
                    ],
                ],
                'Totales' => [
                    'TpoMoneda' => null,
                    'MntExe' => 0,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_impuesto_retenido($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
        $this->normalizar_exportacion($datos);
    }

    /**
     * Método que normaliza los datos de exportacion de un documento
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-15
     */
    public function normalizar_exportacion(array &$datos)
    {
        // agregar modalidad de venta por defecto si no existe
        if (empty($datos['Encabezado']['Transporte']['Aduana']['CodModVenta']) and (!isset($datos['Encabezado']['IdDoc']['IndServicio']) or !in_array($datos['Encabezado']['IdDoc']['IndServicio'], [3, 4, 5]))) {
            $datos['Encabezado']['Transporte']['Aduana']['CodModVenta'] = 1;
        }
        // quitar campos que no son parte del documento de exportacion
        $datos['Encabezado']['Receptor']['CmnaRecep'] = false;
        // colocar forma de pago de exportación
        if (!empty($datos['Encabezado']['IdDoc']['FmaPago'])) {
            $formas = [3 => 21];
            if (isset($formas[$datos['Encabezado']['IdDoc']['FmaPago']])) {
                $datos['Encabezado']['IdDoc']['FmaPagExp'] = $formas[$datos['Encabezado']['IdDoc']['FmaPago']];
            }
            $datos['Encabezado']['IdDoc']['FmaPago'] = false;
        }
        // si es entrega gratuita se coloca el tipo de cambio en CLP en 0 para que total sea 0
        if (!empty($datos['Encabezado']['IdDoc']['FmaPagExp']) and $datos['Encabezado']['IdDoc']['FmaPagExp']==21 and !empty($datos['Encabezado']['OtraMoneda'])) {
            if (!isset($datos['Encabezado']['OtraMoneda'][0])) {
                $datos['Encabezado']['OtraMoneda'] = [$datos['Encabezado']['OtraMoneda']];
            }
            foreach ($datos['Encabezado']['OtraMoneda'] as &$OtraMoneda) {
                if ($OtraMoneda['TpoMoneda']=='PESO CL') {
                    $OtraMoneda['TpoCambio'] = 0;
                }
            }
        }
    }

    /**
     * Método que normaliza los detalles del documento
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @warning Revisar como se aplican descuentos y recargos, ¿debería ser un porcentaje del monto original?
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-07-24
     */
    private function normalizar_detalle(array &$datos)
    {
        if (!isset($datos['Detalle'][0]))
            $datos['Detalle'] = [$datos['Detalle']];
        $item = 1;
        foreach ($datos['Detalle'] as &$d) {
            $d = array_merge([
                'NroLinDet' => $item++,
                'CdgItem' => false,
                'IndExe' => false,
                'Retenedor' => false,
                'NmbItem' => false,
                'DscItem' => false,
                'QtyRef' => false,
                'UnmdRef' => false,
                'PrcRef' => false,
                'QtyItem' => false,
                'Subcantidad' => false,
                'FchElabor' => false,
                'FchVencim' => false,
                'UnmdItem' => false,
                'PrcItem' => false,
                'DescuentoPct' => false,
                'DescuentoMonto' => false,
                'RecargoPct' => false,
                'RecargoMonto' => false,
                'CodImpAdic' => false,
                'MontoItem' => false,
            ], $d);
            // corregir datos
            $d['NmbItem'] = mb_substr($d['NmbItem'], 0, 80);
            if (!empty($d['DscItem'])) {
                $d['DscItem'] = mb_substr($d['DscItem'], 0, 1000);
            }
            // normalizar
            if ($this->esExportacion()) {
                $d['IndExe'] = 1;
            }
            if (is_array($d['CdgItem'])) {
                $d['CdgItem'] = array_merge([
                    'TpoCodigo' => false,
                    'VlrCodigo' => false,
                ], $d['CdgItem']);
                if ($d['Retenedor']===false and $d['CdgItem']['TpoCodigo']=='CPCS') {
                    $d['Retenedor'] = true;
                }
            }
            if ($d['Retenedor']!==false) {
                if (!is_array($d['Retenedor'])) {
                    $d['Retenedor'] = ['IndAgente'=>'R'];
                }
                $d['Retenedor'] = array_merge([
                    'IndAgente' => 'R',
                    'MntBaseFaena' => false,
                    'MntMargComer' => false,
                    'PrcConsFinal' => false,
                ], $d['Retenedor']);
            }
            if ($d['CdgItem']!==false and !is_array($d['CdgItem'])) {
                $d['CdgItem'] = [
                    'TpoCodigo' => empty($d['Retenedor']['IndAgente']) ? 'INT1' : 'CPCS',
                    'VlrCodigo' => $d['CdgItem'],
                ];
            }
            if ($d['PrcItem']) {
                if (!$d['QtyItem'])
                    $d['QtyItem'] = 1;
                if (empty($d['MontoItem'])) {
                    $d['MontoItem'] = $this->round(
                        (float)$d['QtyItem'] * (float)$d['PrcItem'],
                        $datos['Encabezado']['Totales']['TpoMoneda']
                    );
                    // aplicar descuento
                    if ($d['DescuentoPct']) {
                        $d['DescuentoMonto'] = round($d['MontoItem'] * (float)$d['DescuentoPct']/100);
                    }
                    $d['MontoItem'] -= $d['DescuentoMonto'];
                    // aplicar recargo
                    if ($d['RecargoPct']) {
                        $d['RecargoMonto'] = round($d['MontoItem'] * (float)$d['RecargoPct']/100);
                    }
                    $d['MontoItem'] += $d['RecargoMonto'];
                    // aproximar monto del item
                    $d['MontoItem'] = $this->round(
                        $d['MontoItem'], $datos['Encabezado']['Totales']['TpoMoneda']
                    );
                }
            } else if (empty($d['MontoItem'])) {
                $d['MontoItem'] = 0;
            }
            // sumar valor del monto a MntNeto o MntExe según corresponda
            if ($d['MontoItem']) {
                // si no es boleta
                if (!$this->esBoleta()) {
                    if ((!isset($datos['Encabezado']['Totales']['MntNeto']) or $datos['Encabezado']['Totales']['MntNeto']===false) and isset($datos['Encabezado']['Totales']['MntExe'])) {
                        $datos['Encabezado']['Totales']['MntExe'] += $d['MontoItem'];
                    } else {
                        if (!empty($d['IndExe'])) {
                            if ($d['IndExe']==1) {
                                $datos['Encabezado']['Totales']['MntExe'] += $d['MontoItem'];
                            }
                        } else {
                            $datos['Encabezado']['Totales']['MntNeto'] += $d['MontoItem'];
                        }
                    }
                }
                // si es boleta
                else {
                    // si es exento
                    if (!empty($d['IndExe'])) {
                        if ($d['IndExe']==1) {
                            $datos['Encabezado']['Totales']['MntExe'] += $d['MontoItem'];
                        }
                    }
                    // agregar al monto total
                    $datos['Encabezado']['Totales']['MntTotal'] += $d['MontoItem'];
                }
            }
        }
    }

    /**
     * Método que aplica los descuentos y recargos generales respectivos a los
     * montos que correspondan según e indicador del descuento o recargo
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @warning Boleta afecta con algún item exento el descuento se podría estar aplicando mal
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-06
     */
    private function normalizar_aplicar_descuentos_recargos(array &$datos)
    {
        if (!empty($datos['DscRcgGlobal'])) {
            if (!isset($datos['DscRcgGlobal'][0]))
                $datos['DscRcgGlobal'] = [$datos['DscRcgGlobal']];
            foreach ($datos['DscRcgGlobal'] as &$dr) {
                $dr = array_merge([
                    'NroLinDR' => false,
                    'TpoMov' => false,
                    'GlosaDR' => false,
                    'TpoValor' => false,
                    'ValorDR' => false,
                    'ValorDROtrMnda' => false,
                    'IndExeDR' => false,
                ], $dr);
                if ($this->esExportacion()) {
                    $dr['IndExeDR'] = 1;
                }
                // determinar a que aplicar el descuento/recargo
                if (!isset($dr['IndExeDR']) or $dr['IndExeDR']===false) {
                    $monto = $this->getTipo()==39 ? 'MntTotal' : 'MntNeto';
                } else if ($dr['IndExeDR']==1) {
                    $monto = 'MntExe';
                } else if ($dr['IndExeDR']==2) {
                    $monto = 'MontoNF';
                }
                // si no hay monto al que aplicar el descuento se omite
                if (empty($datos['Encabezado']['Totales'][$monto])) {
                    continue;
                }
                // calcular valor del descuento o recargo
                if ($dr['TpoValor']=='$') {
                    $dr['ValorDR'] = $this->round($dr['ValorDR'], $datos['Encabezado']['Totales']['TpoMoneda'], 2);
                }
                $valor =
                    $dr['TpoValor']=='%'
                    ? $this->round(($dr['ValorDR']/100)*$datos['Encabezado']['Totales'][$monto], $datos['Encabezado']['Totales']['TpoMoneda'])
                    : $dr['ValorDR']
                ;
                // aplicar descuento
                if ($dr['TpoMov']=='D') {
                    $datos['Encabezado']['Totales'][$monto] -= $valor;
                }
                // aplicar recargo
                else if ($dr['TpoMov']=='R') {
                    $datos['Encabezado']['Totales'][$monto] += $valor;
                }
                $datos['Encabezado']['Totales'][$monto] = $this->round(
                    $datos['Encabezado']['Totales'][$monto],
                    $datos['Encabezado']['Totales']['TpoMoneda']
                );
                // si el descuento global se aplica a una boleta exenta se copia el valor exento al total
                if ($this->getTipo()==41 and isset($dr['IndExeDR']) and $dr['IndExeDR']==1) {
                    $datos['Encabezado']['Totales']['MntTotal'] = $datos['Encabezado']['Totales']['MntExe'];
                }
            }
        }
    }

    /**
     * Método que calcula los montos de impuestos adicionales o retenciones
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function normalizar_impuesto_retenido(array &$datos)
    {
        // copiar montos
        $montos = [];
        foreach ($datos['Detalle'] as &$d) {
            if (!empty($d['CodImpAdic'])) {
                if (!isset($montos[$d['CodImpAdic']]))
                    $montos[$d['CodImpAdic']] = 0;
                $montos[$d['CodImpAdic']] += $d['MontoItem'];
            }
        }
        // si hay montos y no hay total para impuesto retenido se arma
        if (!empty($montos)) {
            if (!is_array($datos['Encabezado']['Totales']['ImptoReten'])) {
                $datos['Encabezado']['Totales']['ImptoReten'] = [];
            } else if (!isset($datos['Encabezado']['Totales']['ImptoReten'][0])) {
                $datos['Encabezado']['Totales']['ImptoReten'] = [$datos['Encabezado']['Totales']['ImptoReten']];
            }
        }
        // armar impuesto adicional o retención en los totales
        foreach ($montos as $codigo => $neto) {
            // buscar si existe el impuesto en los totales
            $i = 0;
            foreach ($datos['Encabezado']['Totales']['ImptoReten'] as &$ImptoReten) {
                if ($ImptoReten['TipoImp']==$codigo) {
                    break;
                }
                $i++;
            }
            // si no existe se crea
            if (!isset($datos['Encabezado']['Totales']['ImptoReten'][$i])) {
                $datos['Encabezado']['Totales']['ImptoReten'][] = [
                    'TipoImp' => $codigo
                ];
            }
            // se normaliza
            $datos['Encabezado']['Totales']['ImptoReten'][$i] = array_merge([
                'TipoImp' => $codigo,
                'TasaImp' => ImpuestosAdicionales::getTasa($codigo),
                'MontoImp' => null,
            ], $datos['Encabezado']['Totales']['ImptoReten'][$i]);
            // si el monto no existe se asigna
            if ($datos['Encabezado']['Totales']['ImptoReten'][$i]['MontoImp']===null) {
                $datos['Encabezado']['Totales']['ImptoReten'][$i]['MontoImp'] = round(
                    $neto * $datos['Encabezado']['Totales']['ImptoReten'][$i]['TasaImp']/100
                );
            }
        }
        // quitar los codigos que no existen en el detalle
        if (isset($datos['Encabezado']['Totales']['ImptoReten']) and is_array($datos['Encabezado']['Totales']['ImptoReten'])) {
            $codigos = array_keys($montos);
            $n_impuestos = count($datos['Encabezado']['Totales']['ImptoReten']);
            for ($i=0; $i<$n_impuestos; $i++) {
                if (!in_array($datos['Encabezado']['Totales']['ImptoReten'][$i]['TipoImp'], $codigos)) {
                    unset($datos['Encabezado']['Totales']['ImptoReten'][$i]);
                }
            }
            sort($datos['Encabezado']['Totales']['ImptoReten']);
        }
    }

    /**
     * Método que calcula el monto del IVA y el monto total del documento a
     * partir del monto neto y la tasa de IVA si es que existe
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function normalizar_agregar_IVA_MntTotal(array &$datos)
    {
        // agregar IVA y monto total
        if (!empty($datos['Encabezado']['Totales']['MntNeto'])) {
            if ($datos['Encabezado']['IdDoc']['MntBruto']==1) {
                list($datos['Encabezado']['Totales']['MntNeto'], $datos['Encabezado']['Totales']['IVA']) = $this->calcularNetoIVA(
                    $datos['Encabezado']['Totales']['MntNeto'],
                    $datos['Encabezado']['Totales']['TasaIVA']
                );
            } else {
                if (empty($datos['Encabezado']['Totales']['IVA']) and !empty($datos['Encabezado']['Totales']['TasaIVA'])) {
                    $datos['Encabezado']['Totales']['IVA'] = round(
                        $datos['Encabezado']['Totales']['MntNeto']*($datos['Encabezado']['Totales']['TasaIVA']/100)
                    );
                }
            }
            if (empty($datos['Encabezado']['Totales']['MntTotal'])) {
                $datos['Encabezado']['Totales']['MntTotal'] = $datos['Encabezado']['Totales']['MntNeto'];
                if (!empty($datos['Encabezado']['Totales']['IVA']))
                    $datos['Encabezado']['Totales']['MntTotal'] += $datos['Encabezado']['Totales']['IVA'];
                if (!empty($datos['Encabezado']['Totales']['MntExe']))
                    $datos['Encabezado']['Totales']['MntTotal'] += $datos['Encabezado']['Totales']['MntExe'];
            }
        } else {
            if (!$datos['Encabezado']['Totales']['MntTotal'] and !empty($datos['Encabezado']['Totales']['MntExe'])) {
                $datos['Encabezado']['Totales']['MntTotal'] = $datos['Encabezado']['Totales']['MntExe'];
            }
        }
        // si hay impuesto retenido o adicional se contabiliza en el total
        if (!empty($datos['Encabezado']['Totales']['ImptoReten'])) {
            foreach ($datos['Encabezado']['Totales']['ImptoReten'] as &$ImptoReten) {
                // si es retención se resta al total y se traspasaa IVA no retenido
                // en caso que corresponda
                if (ImpuestosAdicionales::getTipo($ImptoReten['TipoImp'])=='R') {
                    $datos['Encabezado']['Totales']['MntTotal'] -= $ImptoReten['MontoImp'];
                    if ($ImptoReten['MontoImp']!=$datos['Encabezado']['Totales']['IVA']) {
                        $datos['Encabezado']['Totales']['IVANoRet'] = $datos['Encabezado']['Totales']['IVA'] - $ImptoReten['MontoImp'];
                    }
                }
                // si es adicional se suma al total
                else if (ImpuestosAdicionales::getTipo($ImptoReten['TipoImp'])=='A' and isset($ImptoReten['MontoImp'])) {
                    $datos['Encabezado']['Totales']['MntTotal'] += $ImptoReten['MontoImp'];
                }
            }
        }
        // si hay impuesto de crédito a constructoras del 65% se descuenta del total
        if (!empty($datos['Encabezado']['Totales']['CredEC'])) {
            if ($datos['Encabezado']['Totales']['CredEC']===true)
                $datos['Encabezado']['Totales']['CredEC'] = round($datos['Encabezado']['Totales']['IVA'] * 0.65); // TODO: mover a constante o método
            $datos['Encabezado']['Totales']['MntTotal'] -= $datos['Encabezado']['Totales']['CredEC'];
        }
    }

    /**
     * Método que normaliza los datos de transporte
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-01
     */
    private function normalizar_transporte(array &$datos)
    {
        if (!empty($datos['Encabezado']['Transporte'])) {
            $datos['Encabezado']['Transporte'] = array_merge([
                'Patente' => false,
                'RUTTrans' => false,
                'Chofer' => false,
                'DirDest' => false,
                'CmnaDest' => false,
                'CiudadDest' => false,
                'Aduana' => false,
            ], $datos['Encabezado']['Transporte']);
        }
    }

    /**
     * Método que normaliza las boletas electrónicas, dte 39 y 41
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-09-30
     */
    private function normalizar_boletas(array &$datos)
    {
        // cambiar tags de DTE a boleta si se pasaron
        if ($datos['Encabezado']['Emisor']['RznSoc']) {
            $datos['Encabezado']['Emisor']['RznSocEmisor'] = $datos['Encabezado']['Emisor']['RznSoc'];
            $datos['Encabezado']['Emisor']['RznSoc'] = false;
        }
        if ($datos['Encabezado']['Emisor']['GiroEmis']) {
            $datos['Encabezado']['Emisor']['GiroEmisor'] = $datos['Encabezado']['Emisor']['GiroEmis'];
            $datos['Encabezado']['Emisor']['GiroEmis'] = false;
        }
        $datos['Encabezado']['Emisor']['Acteco'] = false;
        $datos['Encabezado']['Emisor']['Telefono'] = false;
        $datos['Encabezado']['Emisor']['CorreoEmisor'] = false;
        $datos['Encabezado']['Emisor']['CdgVendedor'] = false;
        $datos['Encabezado']['Receptor']['GiroRecep'] = false;
        if (!empty($datos['Encabezado']['Receptor']['CorreoRecep'])) {
            $datos['Referencia'][] = [
                'NroLinRef' => !empty($datos['Referencia']) ? (count($datos['Referencia'])+1) : 1,
                'RazonRef' => mb_substr('Email receptor: '.$datos['Encabezado']['Receptor']['CorreoRecep'], 0, 90),
            ];
        }
        $datos['Encabezado']['Receptor']['CorreoRecep'] = false;
        // quitar otros tags que no son parte de las boletas
        $datos['Encabezado']['IdDoc']['FmaPago'] = false;
        $datos['Encabezado']['IdDoc']['FchCancel'] = false;
        $datos['Encabezado']['IdDoc']['MedioPago'] = false;
        $datos['Encabezado']['IdDoc']['TpoCtaPago'] = false;
        $datos['Encabezado']['IdDoc']['NumCtaPago'] = false;
        $datos['Encabezado']['IdDoc']['BcoPago'] = false;
        $datos['Encabezado']['IdDoc']['TermPagoGlosa'] = false;
        $datos['Encabezado']['RUTSolicita'] = false;
        $datos['Encabezado']['IdDoc']['TpoTranCompra'] = false;
        $datos['Encabezado']['IdDoc']['TpoTranVenta'] = false;
        // ajustar las referencias si existen
        if (!empty($datos['Referencia'])) {
            if (!isset($datos['Referencia'][0])) {
                $datos['Referencia'] = [$datos['Referencia']];
            }
            foreach ($datos['Referencia'] as &$r) {
                foreach (['TpoDocRef', 'FolioRef', 'FchRef'] as $c) {
                    if (isset($r[$c])) {
                        unset($r[$c]);
                    }
                }
            }
        }
    }

    /**
     * Método que redondea valores. Si los montos son en pesos chilenos se
     * redondea, si no se mantienen todos los decimales
     * @param valor Valor que se desea redondear
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function round($valor, $moneda = false, $decimal = 4)
    {
        return (!$moneda or $moneda=='PESO CL') ? (int)round($valor) : (float)round($valor, $decimal);
    }

    /**
     * Método que determina el estado de validación sobre el DTE, se verifica:
     *  - Firma del DTE
     *  - RUT del emisor (si se pasó uno para comparar)
     *  - RUT del receptor (si se pasó uno para comparar)
     * @return Código del estado de la validación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-03
     */
    public function getEstadoValidacion(array $datos = null)
    {
        if (!$this->checkFirma()) {
            return 1;
        }
        if (is_array($datos)) {
            if (isset($datos['RUTEmisor']) and $this->getEmisor()!=$datos['RUTEmisor']) {
                return 2;
            }
            if (isset($datos['RUTRecep']) and $this->getReceptor()!=$datos['RUTRecep']) {
                return 3;
            }
        }
        return 0;
    }

    /**
     * Método que indica si la firma del DTE es o no válida
     * @return =true si la firma del DTE es válida, =null si no se pudo determinar
     * @warning No se está verificando el valor del DigestValue del documento (sólo la firma de ese DigestValue)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-04
     */
    public function checkFirma()
    {
        if (!$this->xml) {
            return null;
        }
        // obtener firma
        $Signature = $this->xml->documentElement->getElementsByTagName('Signature')->item(0);
        // preparar documento a validar
        $D = $this->xml->documentElement->getElementsByTagName('Documento')->item(0);
        $Documento = new \sasco\LibreDTE\XML();
        $Documento->loadXML($D->C14N());
        $Documento->documentElement->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi');
        $SignedInfo = new \sasco\LibreDTE\XML();
        $SignedInfo->loadXML($Signature->getElementsByTagName('SignedInfo')->item(0)->C14N());
        $SignedInfo->documentElement->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi');
        $DigestValue = $Signature->getElementsByTagName('DigestValue')->item(0)->nodeValue;
        $SignatureValue = trim(str_replace(["\n", ' ', "\t"], '', $Signature->getElementsByTagName('SignatureValue')->item(0)->nodeValue));
        $X509Certificate = trim(str_replace(["\n", ' ', "\t"], '', $Signature->getElementsByTagName('X509Certificate')->item(0)->nodeValue));
        $X509Certificate = '-----BEGIN CERTIFICATE-----'."\n".wordwrap($X509Certificate, 64, "\n", true)."\n".'-----END CERTIFICATE----- ';
        $valid = openssl_verify($SignedInfo->C14N(), base64_decode($SignatureValue), $X509Certificate) === 1 ? true : false;
        return $valid;
        //return $valid and $DigestValue===base64_encode(sha1($Documento->C14N(), true));
    }

    /**
     * Método que indica si el documento es o no cedible
     * @return =true si el documento es cedible
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-10
     */
    public function esCedible()
    {
        return !in_array($this->getTipo(), $this->noCedibles);
    }

    /**
     * Método que indica si el documento es o no una boleta electrónica
     * @return =true si el documento es una boleta electrónica
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-11
     */
    public function esBoleta()
    {
        return in_array($this->getTipo(), [39, 41]);
    }

    /**
     * Método que indica si el documento es o no una exportación
     * @return =true si el documento es una exportación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    public function esExportacion()
    {
        return in_array($this->getTipo(), $this->tipos['Exportaciones']);
    }

    /**
     * Método que valida el schema del DTE
     * @return =true si el schema del documento del DTE es válido, =null si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-15
     */
    public function schemaValidate()
    {
        return true;
    }

    /**
     * Método que valida los datos del DTE
     * @return =true si no hay errores de validación, =false si se encontraron errores al validar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-11-04
     */
    public function verificarDatos()
    {
        if (class_exists('\sasco\LibreDTE\Sii\Dte\VerificadorDatos')) {
            if (!\sasco\LibreDTE\Sii\Dte\VerificadorDatos::check($this->getDatos())) {
                return false;
            }
        }
        return true;
    }

    /**
     * Método que obtiene el estado del DTE
     * @param Firma objeto que representa la Firma Electrónca
     * @return Arreglo con el estado del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-24
     */
    public function getEstado(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        // solicitar token
        $token = \sasco\LibreDTE\Sii\Autenticacion::getToken($Firma);
        if (!$token) {
            return false;
        }
        // consultar estado dte
        $run = $Firma->getID();
        if ($run===false) {
            return false;
        }
        list($RutConsultante, $DvConsultante) = explode('-', $run);
        list($RutCompania, $DvCompania) = explode('-', $this->getEmisor());
        list($RutReceptor, $DvReceptor) = explode('-', $this->getReceptor());
        list($Y, $m, $d) = explode('-', $this->getFechaEmision());
        $xml = \sasco\LibreDTE\Sii::request('QueryEstDte', 'getEstDte', [
            'RutConsultante'  => $RutConsultante,
            'DvConsultante'   => $DvConsultante,
            'RutCompania'     => $RutCompania,
            'DvCompania'      => $DvCompania,
            'RutReceptor'     => $RutReceptor,
            'DvReceptor'      => $DvReceptor,
            'TipoDte'         => $this->getTipo(),
            'FolioDte'        => $this->getFolio(),
            'FechaEmisionDte' => $d.$m.$Y,
            'MontoDte'        => $this->getMontoTotal(),
            'token'           => $token,
        ]);
        // si el estado se pudo recuperar se muestra
        if ($xml===false) {
            return false;
        }
        // entregar estado
        return (array)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR')[0];
    }

    /**
     * Método que obtiene el estado avanzado del DTE
     * @param Firma objeto que representa la Firma Electrónca
     * @return Arreglo con el estado del DTE
     * @todo Corregir warning y también definir que se retornará (sobre todo en caso de error)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-05
     */
    public function getEstadoAvanzado(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        // solicitar token
        $token = \sasco\LibreDTE\Sii\Autenticacion::getToken($Firma);
        if (!$token) {
            return false;
        }
        // consultar estado dte
        list($RutEmpresa, $DvEmpresa) = explode('-', $this->getEmisor());
        list($RutReceptor, $DvReceptor) = explode('-', $this->getReceptor());
        list($Y, $m, $d) = explode('-', $this->getFechaEmision());
        $xml = \sasco\LibreDTE\Sii::request('QueryEstDteAv', 'getEstDteAv', [
            'RutEmpresa'      => $RutEmpresa,
            'DvEmpresa'       => $DvEmpresa,
            'RutReceptor'     => $RutReceptor,
            'DvReceptor'      => $DvReceptor,
            'TipoDte'         => $this->getTipo(),
            'FolioDte'        => $this->getFolio(),
            'FechaEmisionDte' => $d.'-'.$m.'-'.$Y,
            'MontoDte'        => $this->getMontoTotal(),
            'FirmaDte'        => str_replace("\n", '', $this->getFirma()['SignatureValue']),
            'token'           => $token,
        ]);
        // si el estado se pudo recuperar se muestra
        if ($xml===false) {
            return false;
        }
        // entregar estado
        return (array)$xml->xpath('/SII:RESPUESTA/SII:RESP_BODY')[0];
    }

    /**
     * Método que entrega la última acción registrada para el DTE en el registro de compra y venta
     * @return Arreglo con los datos de la última acción
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-08-29
     */
    public function getUltimaAccionRCV(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        list($emisor_rut, $emisor_dv) = explode('-', $this->getEmisor());
        $RCV = new \sasco\LibreDTE\Sii\RegistroCompraVenta($Firma);
        try {
            $eventos = $RCV->listarEventosHistDoc($emisor_rut, $emisor_dv, $this->getTipo(), $this->getFolio());
            return $eventos ? $eventos[count($eventos)-1] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

}
