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
 * @version 2015-12-11
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

    private $tipos = [
        'Documento' => [33, 34, 39, 41, 46, 52, 56, 61],
        'Liquidacion' => [43],
        'Exportaciones' => [110, 111, 112],
    ]; ///< Tipos posibles de documentos tributarios electrónicos

    private $noCedibles = [56, 61, 111, 112]; ///< Notas de crédito y notas de débito no son cedibles

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
     * @version 2015-11-21
     */
    private function loadXML($xml)
    {
        if (!empty($xml)) {
            $this->xml = new \sasco\LibreDTE\XML();
            if (!$this->xml->loadXML($xml) or !$this->schemaValidate())
                return false;
            $TipoDTE = $this->xml->getElementsByTagName('TipoDTE')->item(0);
            if (!$TipoDTE)
                return false;
            $this->tipo = $TipoDTE->nodeValue;
            $this->tipo_general = $this->getTipoGeneral($this->tipo);
            if (!$this->tipo_general)
                return false;
            $Folio = $this->xml->getElementsByTagName('Folio')->item(0);
            if (!$Folio)
                return false;
            $this->folio = $Folio->nodeValue;
            $this->id = 'T'.$this->tipo.'F'.$this->folio;
            return true;
        }
        return false;
    }

    /**
     * Método que asigna los datos del DTE
     * @param datos Arreglo con los datos del DTE que se quire generar
     * @param normalizar Si se pasa un arreglo permitirá indicar si el mismo se debe o no normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-11-21
     */
    private function setDatos(array $datos, $normalizar = true)
    {
        if (!empty($datos)) {
            $this->tipo = $datos['Encabezado']['IdDoc']['TipoDTE'];
            $this->folio = $datos['Encabezado']['IdDoc']['Folio'];
            $this->id = 'T'.$this->tipo.'F'.$this->folio;
            if ($normalizar) {
                $this->normalizar($datos);
                $method = 'normalizar_'.$this->tipo;
                if (method_exists($this, $method))
                    $this->$method($datos);
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
            $this->xml->generate($datos + ['TED' => null], $parent);
            $this->datos = $datos;
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
     * @version 2015-09-17
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
        }
        return $this->datos;
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
     * @version 2015-09-06
     */
    public function getID()
    {
        return $this->id;
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
     * Método que entrega el string XML del tag TED
     * @return String XML con tag TED
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-09
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
        $xml->loadXML($this->xml->getElementsByTagName('TED')->item(0)->C14N());
        $xml->documentElement->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi');
        $xml->documentElement->removeAttributeNS('http://www.sii.cl/SiiDte', '');
        $TED = $xml->getFlattened('/');
        return mb_detect_encoding($TED, ['UTF-8', 'ISO-8859-1']) != 'ISO-8859-1' ? utf8_decode($TED) : $TED;
    }

    /**
     * Método que realiza el timbrado del DTE
     * @param Folios Objeto de los Folios con los que se desea timbrar
     * @return =true si se pudo timbrar o =false en caso de error
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
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
        if (openssl_sign($DD, $timbre, $Folios->getPrivateKey(), OPENSSL_ALGO_SHA1)==false) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DTE_ERROR_TIMBRE,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::DTE_ERROR_TIMBRE, $this->getID())
            );
            return false;
        }
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
     * @version 2015-09-17
     */
    public function firmar(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $parent = $this->xml->getElementsByTagName($this->tipo_general)->item(0);
        $this->xml->generate(['TmstFirma'=>$this->timestamp], $parent);
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
     * @version 2015-12-14
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
            'RznSoc' => $this->datos['Encabezado']['Receptor']['RznSocRecep'],
            'MntExe' => false,
            'MntNeto' => false,
            'MntIVA' => 0,
            'MntTotal' => (int)$this->datos['Encabezado']['Totales']['MntTotal'],
        ];
        // obtener montos si es que existen en el documento
        $montos = ['TasaImp'=>'TasaIVA', 'MntExe'=>'MntExe', 'MntNeto'=>'MntNeto', 'MntIVA'=>'IVA'];
        foreach ($montos as $dest => $orig) {
            if (!empty($this->datos['Encabezado']['Totales'][$orig])) {
                $resumen[$dest] = (int)$this->datos['Encabezado']['Totales'][$orig];
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
     * @version 2015-12-14
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
     * @version 2015-12-14
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
                    'TipoDespacho' => false,
                    'IndTraslado' => false,
                    'IndServicio' => $this->esBoleta() ? 3 : false,
                    'MntBruto' => false,
                ],
                'Emisor' => [
                    'RUTEmisor' => false,
                    'RznSoc' => false,
                    'GiroEmis' => false,
                    'Telefono' => false,
                    'CorreoEmisor' => false,
                    'Acteco' => false,
                    'CdgSIISucur' => false,
                    'DirOrigen' => false,
                    'CmnaOrigen' => false,
                ],
                'Receptor' => [
                    'RUTRecep' => false,
                    'RznSocRecep' => false,
                    'GiroRecep' => false,
                    'Contacto' => false,
                    'CorreoRecep' => false,
                    'DirRecep' => false,
                    'CmnaRecep' => false,
                ],
            ],
        ], $datos);
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
            if (!isset($datos['Referencia'][0]))
                $datos['Referencia'] = [$datos['Referencia']];
            $NroLinRef = 1;
            foreach ($datos['Referencia'] as &$r) {
                $r = array_merge([
                    'NroLinRef' => $NroLinRef++,
                    'TpoDocRef' => false,
                    'FolioRef' => false,
                    'FchRef' => date('Y-m-d'),
                    'CodRef' => false,
                ], $r);
            }
        }
    }

    /**
     * Método que normaliza los datos de una factura electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-25
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
                    'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                    'IVA' => 0,
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
     * Método que normaliza los datos de una factura exenta electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-25
     */
    private function normalizar_34(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
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
     * @version 2015-12-14
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
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
    }

    /**
     * Método que normaliza los datos de una guía de despacho electrónica
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-25
     */
    private function normalizar_52(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'Transporte' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => false,
                    'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                    'IVA' => 0,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // si es traslado interno se copia el emisor en el receptor sólo si el
        // receptor no está definido o bien se el receptor tiene RUT diferente
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
            }
        }
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_aplicar_descuentos_recargos($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
    }

    /**
     * Método que normaliza los datos de una nota de débito
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-06
     */
    private function normalizar_56(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'Totales' => [
                    'MntNeto' => false,
                    'MntExe' => false,
                    'TasaIVA' => false,
                    'IVA' =>false,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
        if ($datos['Encabezado']['Totales']['MntNeto']===false) {
            $datos['Encabezado']['Totales']['MntNeto'] = 0;
        }
    }

    /**
     * Método que normaliza los datos de una nota de crédito
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-06
     */
    private function normalizar_61(array &$datos)
    {
        // completar con nodos por defecto
        $datos = \sasco\LibreDTE\Arreglo::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'Totales' => [
                    'MntNeto' => false,
                    'MntExe' => false,
                    'TasaIVA' => false,
                    'IVA' =>false,
                    'MntTotal' => 0,
                ]
            ],
        ], $datos);
        // normalizar datos
        $this->normalizar_detalle($datos);
        $this->normalizar_agregar_IVA_MntTotal($datos);
        if ($datos['Encabezado']['Totales']['MntNeto']===false) {
            $datos['Encabezado']['Totales']['MntNeto'] = 0;
        }
    }

    /**
     * Método que normaliza los detalles del documento
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-13
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
                'IndExe' =>false,
                'NmbItem' => false,
                'DscItem' => false,
                'QtyItem' => false,
                'UnmdItem' => false,
                'PrcItem' => false,
                'DescuentoPct' => false,
                'DescuentoMonto' => false,
            ], $d);
            if ($d['CdgItem']!==false and !is_array($d['CdgItem'])) {
                $d['CdgItem'] = [
                    'TpoCodigo' => 'INT1',
                    'VlrCodigo' => $d['CdgItem'],
                ];
            }
            if (empty($d['MontoItem'])) {
                $d['MontoItem'] = round($d['QtyItem'] * $d['PrcItem']);
                if ($d['DescuentoPct'])
                    $d['DescuentoMonto'] = round($d['MontoItem'] * (int)$d['DescuentoPct']/100);
                $d['MontoItem'] -= (int)$d['DescuentoMonto'];
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
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-11
     */
    private function normalizar_aplicar_descuentos_recargos(array &$datos)
    {
        if (!empty($datos['DscRcgGlobal'])) {
            foreach ($datos['DscRcgGlobal'] as $dr) {
                // determinar a que aplicar el descuento/recargo
                if (!isset($dr['IndExeDR']))
                    $monto = 'MntNeto';
                else if ($dr['IndExeDR']==1)
                    $monto = 'MntExe';
                else if ($dr['IndExeDR']==2)
                    $monto = 'MontoNF';
                // si no hay monto al que aplicar el descuento se omite
                if (empty($datos['Encabezado']['Totales'][$monto]))
                    continue;
                // calcular valor del descuento o recargo
                $valor = $dr['TpoValor']=='%' ? (($dr['ValorDR']/100)*$datos['Encabezado']['Totales'][$monto]) : $dr['ValorDR'];
                // aplicar descuento
                if ($dr['TpoMov']=='D') {
                    $datos['Encabezado']['Totales'][$monto] -= $valor;
                }
                // aplicar recargo
                else if ($dr['TpoMov']=='R') {
                    $datos['Encabezado']['Totales'][$monto] += $valor;
                }
                $datos['Encabezado']['Totales'][$monto] = round($datos['Encabezado']['Totales'][$monto]);
            }
        }
    }

    /**
     * Método que calcula el monto del IVA y el monto total del documento a
     * partir del monto neto y la tasa de IVA si es que existe
     * @param datos Arreglo con los datos del documento que se desean normalizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    private function normalizar_agregar_IVA_MntTotal(array &$datos)
    {
        if (!empty($datos['Encabezado']['Totales']['MntNeto'])) {
            if ($datos['Encabezado']['IdDoc']['MntBruto']==1) {
                list($datos['Encabezado']['Totales']['MntNeto'], $datos['Encabezado']['Totales']['IVA']) = $this->calcularNetoIVA(
                    $datos['Encabezado']['Totales']['MntNeto'],
                    $datos['Encabezado']['Totales']['TasaIVA']
                );
            } else {
                if (empty($datos['Encabezado']['Totales']['IVA']) and !empty($datos['Encabezado']['Totales']['TasaIVA'])) {
                    $datos['Encabezado']['Totales']['IVA'] = round($datos['Encabezado']['Totales']['MntNeto']*($datos['Encabezado']['Totales']['TasaIVA']/100));
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
    }

    /**
     * Método que determina el estado de validación sobre el DTE, se verifica:
     *  - Firma del DTE
     *  - RUT del emisor (si se pasó uno para comparar)
     *  - RUT del receptor (si se pasó uno para comparar)
     * @return Código del estado de la validación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-08
     */
    public function getEstadoValidacion(array $datos = null)
    {
        if (!$this->checkFirma())
            return 1;
        if (is_array($datos)) {
            if (isset($datos['RUTEmisor']) and $this->getEmisor()!=$datos['RUTEmisor'])
                return 2;
            if (isset($datos['RUTRecep']) and $this->getReceptor()!=$datos['RUTRecep'])
                return 3;
        }
        return 0;
    }

    /**
     * Método que indica si la firma del DTE es o no válida
     * @return =true si la firma del DTE es válida, =null si no se pudo determinar
     * @warning No se está verificando el valor del DigestValue del documento (sólo la firma de ese DigestValue)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-08
     */
    public function checkFirma()
    {
        if (!$this->xml)
            return null;
        // obtener firma
        $Signature = $this->xml->documentElement->getElementsByTagName('Signature')->item(0);
        // preparar documento a validar
        $D = $this->xml->documentElement->getElementsByTagName('Documento')->item(0);
        $Documento = new \sasco\LibreDTE\XML();
        $Documento->loadXML($D->C14N());
        $Documento->documentElement->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi');
        $Documento->documentElement->removeAttributeNS('http://www.sii.cl/SiiDte', '');
        $SignedInfo = new \sasco\LibreDTE\XML();
        $SignedInfo->loadXML($Signature->getElementsByTagName('SignedInfo')->item(0)->C14N());
        $SignedInfo->documentElement->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi');
        $DigestValue = $Signature->getElementsByTagName('DigestValue')->item(0)->nodeValue;
        $SignatureValue = $Signature->getElementsByTagName('SignatureValue')->item(0)->nodeValue;
        $X509Certificate = $Signature->getElementsByTagName('X509Certificate')->item(0)->nodeValue;
        $X509Certificate = '-----BEGIN CERTIFICATE-----'."\n".wordwrap(trim($X509Certificate), 64, "\n", true)."\n".'-----END CERTIFICATE----- ';
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
        if (!$token)
            return false;
        // consultar estado dte
        $run = $Firma->getID();
        if ($run===false)
            return false;
        list($RutConsultante, $DvConsultante) = explode('-', $run);
        list($RutCompania, $DvCompania) = explode('-', $this->getEmisor());
        list($RutReceptor, $DvReceptor) = explode('-', $this->getReceptor());
        list($Y, $m, $d) = explode('-', $this->getFechaEmision());
        $xml = \sasco\LibreDTE\Sii::request('QueryEstDte', 'getEstDte', [
            'RutConsultante'    => $RutConsultante,
            'DvConsultante'     => $DvConsultante,
            'RutCompania'       => $RutCompania,
            'DvCompania'        => $DvCompania,
            'RutReceptor'       => $RutReceptor,
            'DvReceptor'        => $DvReceptor,
            'TipoDte'           => $this->getTipo(),
            'FolioDte'          => $this->getFolio(),
            'FechaEmisionDte'   => $d.$m.$Y,
            'MontoDte'          => $this->getMontoTotal(),
            'token'             => $token,
        ]);
        // si el estado se pudo recuperar se muestra
        if ($xml===false)
            return false;
        // entregar estado
        return (array)$xml->xpath('/SII:RESPUESTA/SII:RESP_HDR')[0];
    }

}
