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
 * Clase que representa el envío de un Libro de Compra o Venta
 *  - Libros simplificados: https://www.sii.cl/DJI/DJI_Formato_XML.html
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-29
 */
class LibroCompraVenta extends \sasco\LibreDTE\Sii\Base\Libro
{

    private $simplificado = false; ///< Indica si el libro es simplificado o no

    /**
     * Constructor del libro
     * @param simplificado Indica si el libro es (=true) o no simplificado (=false, por defecto)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-08
     */
    public function __construct($simplificado = false)
    {
        $this->simplificado = $simplificado;
    }

    /**
     * Método que agrega un detalle al listado que se enviará
     * @param detalle Arreglo con el resumen del DTE que se desea agregar
     * @return =true si se pudo agregar el detalle o =false si no se agregó por exceder el límite del libro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-05
     */
    public function agregar(array $detalle, $normalizar = true)
    {
        if ($normalizar)
            $this->normalizarDetalle($detalle);
        $this->detalles[] = $detalle;
        return true;
    }

    /**
     * Método que normaliza un detalle del libro de compra o venta
     * @param detalle Arreglo con el resumen del DTE que se desea agregar
     * @return Arreglo con el detalle normalizado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-29
     */
    private function normalizarDetalle(array &$detalle)
    {
        // agregar nodos (esto para mantener orden del XML)
        $detalle = array_merge([
            'TpoDoc' => false,
            'NroDoc' => false,
            'Anulado' => false,
            'TpoImp' => 1,
            'TasaImp' => false,
            'FchDoc' => false,
            'CdgSIISucur' => false,
            'RUTDoc' => false,
            'RznSoc' => false,
            'MntExe' => false,
            'MntNeto' => false,
            'MntIVA' => false,
            'MntActivoFijo' => false,
            'MntIVAActivoFijo' => false,
            'IVANoRec' => false,
            'IVAUsoComun' => false,
            'OtrosImp' => false,
            'MntSinCred' => false,
            'MntTotal' => false,
            'IVANoRetenido' => false,
        ], $detalle);
        // calcular valores que no se hayan entregado
        if (isset($detalle['FctProp'])) {
            if ($detalle['IVAUsoComun']===false)
                $detalle['IVAUsoComun'] = round($detalle['MntNeto'] * ($detalle['TasaImp']/100));
        } else if (!$detalle['MntIVA'] and !is_array($detalle['IVANoRec']) and $detalle['TasaImp'] and $detalle['MntNeto']) {
            $detalle['MntIVA'] = round($detalle['MntNeto'] * ($detalle['TasaImp']/100));
        }
        // normalizar IVA no recuperable
        if (!empty($detalle['IVANoRec'])) {
            if (!isset($detalle['IVANoRec'][0]))
                $detalle['IVANoRec'] = [$detalle['IVANoRec']];
        }
        // normalizar otros impuestos
        if (!empty($detalle['OtrosImp'])) {
            if (!isset($detalle['OtrosImp'][0]))
                $detalle['OtrosImp'] = [$detalle['OtrosImp']];
        }
        // calcular monto total si no se especificó
        if ($detalle['MntTotal']===false) {
            // calcular monto total inicial
            $detalle['MntTotal'] = $detalle['MntExe'] + $detalle['MntNeto'] + (int)$detalle['MntIVA'];
            // agregar iva no recuperable al monto total
            if (!empty($detalle['IVANoRec'])) {
                foreach ($detalle['IVANoRec'] as $IVANoRec) {
                    $detalle['MntTotal'] += $IVANoRec['MntIVANoRec'];
                }
            }
            // agregar iva de uso común al monto total
            if (isset($detalle['FctProp'])) {
                $detalle['MntTotal'] += $detalle['IVAUsoComun'];
            }
            // descontar del total la retención total de IVA
            if (!empty($detalle['OtrosImp'])) {
                foreach ($detalle['OtrosImp'] as $OtrosImp) {
                    if ($OtrosImp['CodImp']==15) {
                        $detalle['MntTotal'] -= $OtrosImp['MntImp'];
                    }
                }
            }
        }
        // si no hay no hay monto neto, no se crean campos para IVA
        if (!$detalle['MntNeto']) {
            $detalle['MntNeto'] = $detalle['TasaImp'] = $detalle['MntIVA'] = false;
        }
        // si el código de sucursal no existe se pone a falso, esto básicamente
        // porque algunos sistemas podrían usar 0 cuando no hay CdgSIISucur
        if (!$detalle['CdgSIISucur'])
            $detalle['CdgSIISucur'] = false;
    }

    /**
     * Método que agrega el detalle del libro de compras a partir de un archivo
     * CSV.
     *
     * Formato del archivo (desde la columna A):
     *   TpoDoc -> 0
     *   NroDoc -> 1
     *   RUTDoc -> 2
     *   TasaImp -> 3
     *   RznSoc -> 4 (opcional)
     *   TpoImp -> 5 (opcional, por defecto 1)
     *   FchDoc -> 6
     *   Anulado -> 7
     *   MntExe -> 8
     *   MntNeto -> 9
     *   MntIVA -> 10 (calculable)
     *   IVANoRec: (opcional)
     *     CodIVANoRec -> 11
     *     MntIVANoRec -> 12 (calculable)
     *   IVAUsoComun -> 13 (calculable)
     *   FctProp -> 14
     *   OtrosImp: (opcional)
     *     CodImp -> 15
     *     TasaImp -> 16
     *     MntImp -> 17 (calculable)
     *   MntTotal -> 18 (calculable)
     *   MntSinCred -> 19 (opcional)
     *   MntActivoFijo -> 20 (opcional)
     *   MntIVAActivoFijo -> 21 (opcional)
     *   IVANoRetenido -> 22 (opcional)
     *   CdgSIISucur -> 23 (opcional)
     *
     * @param archivo  Ruta al archivo que se desea cargar
     * @param separador Separador de campos del archivo CSV
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-29
     */
    public function agregarComprasCSV($archivo, $separador = ';')
    {
        $data = \sasco\LibreDTE\CSV::read($archivo);
        $n_data = count($data);
        $detalles = [];
        for ($i=1; $i<$n_data; $i++) {
            // detalle genérico
            $detalle = [
                'TpoDoc' => $data[$i][0],
                'NroDoc' => $data[$i][1],
                'RUTDoc' => $data[$i][2],
                'TasaImp' => !empty($data[$i][3]) ? $data[$i][3] : false,
                'RznSoc' => !empty($data[$i][4]) ? $data[$i][4] : false,
                'TpoImp' => !empty($data[$i][5]) ? $data[$i][5] : 1,
                'FchDoc' => $data[$i][6],
                'Anulado' => !empty($data[$i][7]) ? $data[$i][7] : false,
                'MntExe' => !empty($data[$i][8]) ? $data[$i][8] : false,
                'MntNeto' => !empty($data[$i][9]) ? $data[$i][9] : false,
                'MntIVA' => !empty($data[$i][10]) ? $data[$i][10] : 0,
                'IVAUsoComun' => !empty($data[$i][13]) ? $data[$i][13] : false,
                'MntSinCred' => !empty($data[$i][19]) ? $data[$i][19] : false,
                'MntActivoFijo' => !empty($data[$i][20]) ? $data[$i][20] : false,
                'MntIVAActivoFijo' => !empty($data[$i][21]) ? $data[$i][21] : false,
                'IVANoRetenido' => !empty($data[$i][22]) ? $data[$i][22] : false,
                'CdgSIISucur' => !empty($data[$i][23]) ? $data[$i][23] : false,
            ];
            // agregar código y monto de iva no recuperable si existe
            if (!empty($data[$i][11])) {
                $detalle['IVANoRec'] = [
                    'CodIVANoRec' => $data[$i][11],
                    'MntIVANoRec' => !empty($data[$i][12]) ? $data[$i][12] : round($detalle['MntNeto'] * ($detalle['TasaImp']/100)),
                ];
            }
            // si hay factor de proporcionalidad se agrega
            if (!empty($data[$i][14])) {
                $detalle['FctProp'] = $data[$i][14];
            }
            // agregar código y monto de otros impuestos
            if (!empty($data[$i][15]) and !empty($data[$i][16])) {
                $detalle['OtrosImp'] = [
                    'CodImp' => $data[$i][15],
                    'TasaImp' => $data[$i][16],
                    'MntImp' => !empty($data[$i][17]) ? $data[$i][17] : round($detalle['MntNeto'] * ($data[$i][16]/100)),
                ];
            }
            // si hay monto total se agrega
            if (!empty($data[$i][18])) {
                $detalle['MntTotal'] = $data[$i][18];
            }
            // agregar a los detalles
            $this->agregar($detalle);
        }
    }

    /**
     * Método que agrega el detalle del libro de compras a partir de un archivo
     * CSV.
     *
     * Formato del archivo (desde la columna A):
     *   TpoDoc -> 0
     *   NroDoc -> 1
     *   TasaImp -> 2
     *   FchDoc -> 3
     *   CdgSIISucur -> 4 (opcional)
     *   RUTDoc -> 5
     *   RznSoc -> 6 (opcional)
     *   MntExe -> 7
     *   MntNeto -> 8
     *   MntIVA -> 9 (calculable)
     *   OtrosImp: (opcional)
     *     CodImp -> 10
     *     TasaImp -> 11
     *     MntImp -> 12 (calculable)
     *   MntTotal -> 13 (calculable)
     *
     * @param archivo  Ruta al archivo que se desea cargar
     * @param separador Separador de campos del archivo CSV
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-18
     */
    public function agregarVentasCSV($archivo, $separador = ';')
    {
        $data = \sasco\LibreDTE\CSV::read($archivo);
        $n_data = count($data);
        $detalles = [];
        for ($i=1; $i<$n_data; $i++) {
            // detalle genérico
            $detalle = [
                'TpoDoc' => $data[$i][0],
                'NroDoc' => $data[$i][1],
                'TasaImp' => !empty($data[$i][2]) ? $data[$i][2] : false,
                'FchDoc' => $data[$i][3],
                'CdgSIISucur' => !empty($data[$i][4]) ? $data[$i][4] : false,
                'RUTDoc' => $data[$i][5],
                'RznSoc' => !empty($data[$i][6]) ? $data[$i][6] : false,
                'MntExe' => !empty($data[$i][7]) ? $data[$i][7] : false,
                'MntNeto' => !empty($data[$i][8]) ? $data[$i][8] : false,
                'MntIVA' => !empty($data[$i][9]) ? $data[$i][9] : 0,
            ];
            // agregar código y monto de otros impuestos
            if (!empty($data[$i][10]) and !empty($data[$i][11])) {
                $detalle['OtrosImp'] = [
                    'CodImp' => $data[$i][10],
                    'TasaImp' => $data[$i][11],
                    'MntImp' => !empty($data[$i][12]) ? $data[$i][12] : round($detalle['MntNeto'] * ($data[$i][11]/100)),
                ];
            }
            // si hay monto total se agrega
            if (!empty($data[$i][13])) {
                $detalle['MntTotal'] = $data[$i][13];
            }
            // agregar a los detalles
            $this->agregar($detalle);
        }
    }

    /**
     * Método para asignar la caratula
     * @param caratula Arreglo con datos del envío: RutEnvia, FchResol y NroResol
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-24
     */
    public function setCaratula(array $caratula)
    {
        $this->caratula = array_merge([
            'RutEmisorLibro' => false,
            'RutEnvia' => isset($this->Firma) ? $this->Firma->getID() : false,
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => false,
            'NroResol' => false,
            'TipoOperacion' => 'VENTA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
            'FolioNotificacion' => false,
        ], $caratula);
        if ($this->caratula['TipoEnvio']=='ESPECIAL')
            $this->caratula['FolioNotificacion'] = null;
        $this->id = 'LIBRO_'.$this->caratula['TipoOperacion'].'_'.str_replace('-', '', $this->caratula['RutEmisorLibro']).'_'.str_replace('-', '', $this->caratula['PeriodoTributario']).'_'.date('U');
    }

    /**
     * Método que genera el XML del libro IECV para el envío al SII
     * @param incluirDetalle =true no se incluirá el detalle de los DTEs (sólo se usará para calcular totales)
     * @return XML con el envio del libro de compra y venta firmado o =false si no se pudo generar o firmar el envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    public function generar($incluirDetalle = true)
    {
        // si ya se había generado se entrega directamente
        if ($this->xml_data)
            return $this->xml_data;
        // generar totales de DTE y sus montos
        $TotalesPeriodo = $this->getTotalesPeriodo();
        $ResumenPeriodo = $TotalesPeriodo ? ['TotalesPeriodo'=>$TotalesPeriodo] : false;
        // generar XML del envío
        $xmlEnvio = (new \sasco\LibreDTE\XML())->generate([
            'LibroCompraVenta' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => $this->simplificado ? 'http://www.sii.cl/SiiDte LibroCVS_v10.xsd' : 'http://www.sii.cl/SiiDte LibroCV_v10.xsd',
                    'version' => '1.0',
                ],
                'EnvioLibro' => [
                    '@attributes' => [
                        'ID' => $this->id,
                    ],
                    'Caratula' => $this->caratula,
                    'ResumenPeriodo' => $ResumenPeriodo,
                    'Detalle' => $incluirDetalle ? $this->detalles : false,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ],
            ]
        ])->saveXML();
        // firmar XML del envío y entregar
        $this->xml_data = (!$this->simplificado and $this->Firma) ? $this->Firma->signXML($xmlEnvio, '#'.$this->id, 'EnvioLibro', true) : $xmlEnvio;
        return $this->xml_data;
    }

    /**
     * Método que obtiene los datos para generar los tags TotalesPeriodo
     * @return Arreglo con los datos para generar los tags TotalesPeriodo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-29
     */
    private function getTotalesPeriodo()
    {
        $totales = [];
        foreach ($this->detalles as &$d) {
            if (!isset($totales[$d['TpoDoc']])) {
                $totales[$d['TpoDoc']] = [
                    'TpoDoc' => $d['TpoDoc'],
                    'TotDoc' => 0,
                    'TotAnulado' => false,
                    'TotMntExe' => 0,
                    'TotMntNeto' => 0,
                    'TotMntIVA' => 0,
                    'TotMntActivoFijo' => false,
                    'TotMntIVAActivoFijo' => false,
                    'TotIVANoRec' => false,
                    'TotIVAUsoComun' => false,
                    'FctProp' => false,
                    'TotCredIVAUsoComun' => false,
                    'TotOtrosImp' => false,
                    'TotImpSinCredito' => false,
                    'TotMntTotal' => 0,
                    'TotIVANoRetenido' => false,
                ];
            }
            // contabilizar cantidad de documentos y montos (exento, neto, iva y total)
            $totales[$d['TpoDoc']]['TotDoc']++;
            $totales[$d['TpoDoc']]['TotMntExe'] += $d['MntExe'];
            $totales[$d['TpoDoc']]['TotMntNeto'] += $d['MntNeto'];
            if (!empty($d['MntIVA'])) {
                $totales[$d['TpoDoc']]['TotMntIVA'] += $d['MntIVA'];
            }
            $totales[$d['TpoDoc']]['TotMntTotal'] += $d['MntTotal'];
            // contabilizar documentos anulados
            if (!empty($d['Anulado']) and $d['Anulado']=='A')
                $totales[$d['TpoDoc']]['TotAnulado']++;
            // si hay activo fijo se contabiliza
            if (!empty($d['MntActivoFijo']))
                $totales[$d['TpoDoc']]['TotMntActivoFijo'] += $d['MntActivoFijo'];
            if (!empty($d['MntIVAActivoFijo']))
                $totales[$d['TpoDoc']]['TotMntIVAActivoFijo'] += $d['MntIVAActivoFijo'];
            // si hay iva no recuperable se contabiliza
            if (!empty($d['IVANoRec'])) {
                foreach ($d['IVANoRec'] as $IVANoRec) {
                    if (!isset($totales[$d['TpoDoc']]['TotIVANoRec'][$IVANoRec['CodIVANoRec']])) {
                        $totales[$d['TpoDoc']]['TotIVANoRec'][$IVANoRec['CodIVANoRec']] = [
                            'CodIVANoRec' => $IVANoRec['CodIVANoRec'],
                            'TotOpIVANoRec' => 0,
                            'TotMntIVANoRec' => 0,
                        ];
                    }
                    $totales[$d['TpoDoc']]['TotIVANoRec'][$IVANoRec['CodIVANoRec']]['TotOpIVANoRec']++;
                    $totales[$d['TpoDoc']]['TotIVANoRec'][$IVANoRec['CodIVANoRec']]['TotMntIVANoRec'] += $IVANoRec['MntIVANoRec'];
                }
            }
            // si hay IVA de uso común se contabiliza
            if (!empty($d['FctProp'])) {
                $totales[$d['TpoDoc']]['TotIVAUsoComun'] += $d['IVAUsoComun'];
                $totales[$d['TpoDoc']]['FctProp'] = $d['FctProp']/100;
                $totales[$d['TpoDoc']]['TotCredIVAUsoComun'] += round($d['IVAUsoComun'] * ($d['FctProp']/100));
                unset($d['FctProp']); // se quita el factor de proporcionalidad del detalle ya que no es parte del XML
            }
            // si hay otro tipo de impuesto se contabiliza
            if (!empty($d['OtrosImp'])) {
                foreach ($d['OtrosImp'] as $OtrosImp) {
                    if (!isset($totales[$d['TpoDoc']]['TotOtrosImp'][$OtrosImp['CodImp']])) {
                        $totales[$d['TpoDoc']]['TotOtrosImp'][$OtrosImp['CodImp']] = [
                            'CodImp' => $OtrosImp['CodImp'],
                            'TotMntImp' => 0,
                        ];
                    }
                    $totales[$d['TpoDoc']]['TotOtrosImp'][$OtrosImp['CodImp']]['TotMntImp'] += $OtrosImp['MntImp'];
                }
            }
            // contabilizar impuesto sin derecho a crédito
            if (!empty($d['MntSinCred']))
                $totales[$d['TpoDoc']]['TotImpSinCredito'] += $d['MntSinCred'];
            // contabilizar IVA no retenido
            if (!empty($d['IVANoRetenido']))
                $totales[$d['TpoDoc']]['TotIVANoRetenido'] += $d['IVANoRetenido'];
        }
        return $totales;
    }

    /**
     * Método que obtiene los datos de las compras en el formato que se usa en
     * el archivo CSV
     * @return Arreglo con los datos de las compras
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-29
     */
    public function getCompras()
    {
        $detalle = [];
        foreach ($this->detalles as $d) {
            $detalle[] = [
                (int)$d['TpoDoc'],
                (int)$d['NroDoc'],
                $d['RUTDoc'],
                (int)$d['TasaImp'],
                $d['RznSoc'],
                $d['TpoImp']!==false ? $d['TpoImp'] : 1,
                $d['FchDoc'],
                $d['Anulado']!=false ? $d['Anulado'] : null,
                $d['MntExe']!=false ? $d['MntExe'] : null,
                $d['MntNeto']!=false ? $d['MntNeto'] : null,
                (int)$d['MntIVA'],
                (is_array($d['IVANoRec']) and $d['IVANoRec'][0]['CodIVANoRec']!=false) ? $d['IVANoRec'][0]['CodIVANoRec'] : null,
                (is_array($d['IVANoRec']) and $d['IVANoRec'][0]['MntIVANoRec']!=false) ? $d['IVANoRec'][0]['MntIVANoRec'] : null,
                $d['IVAUsoComun']!=false ? $d['IVAUsoComun'] : null,
                (isset($d['FctProp']) and $d['FctProp']!=false) ? $d['FctProp'] : null,
                (is_array($d['OtrosImp']) and $d['OtrosImp'][0]['CodImp']!=false) ? $d['OtrosImp'][0]['CodImp'] : null,
                (is_array($d['OtrosImp']) and $d['OtrosImp'][0]['CodImp']!=false) ? $d['OtrosImp'][0]['TasaImp'] : null,
                (is_array($d['OtrosImp']) and $d['OtrosImp'][0]['CodImp']!=false) ? $d['OtrosImp'][0]['MntImp'] : null,
                $d['MntTotal']!=false ? $d['MntTotal'] : null,
                $d['MntSinCred']!=false ? $d['MntSinCred'] : null,
                $d['MntActivoFijo']!=false ? $d['MntActivoFijo'] : null,
                $d['MntIVAActivoFijo']!=false ? $d['MntIVAActivoFijo'] : null,
                $d['IVANoRetenido']!=false ? $d['IVANoRetenido'] : null,
                $d['CdgSIISucur']!=false ? $d['CdgSIISucur'] : null,
            ];
        }
        return $detalle;
    }

}
