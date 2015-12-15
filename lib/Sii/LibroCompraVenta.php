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
 * @version 2015-12-14
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
     * @version 2015-11-03
     */
    private function normalizarDetalle(array &$detalle)
    {
        // agregar nodos (esto para mantener orden del XML)
        $detalle = array_merge([
            'TpoDoc' => false,
            'NroDoc' => false,
            'TasaImp' => false,
            'FchDoc' => false,
            'CdgSIISucur' => false,
            'RUTDoc' => false,
            'RznSoc' => false,
            'MntExe' => false,
            'MntNeto' => false,
            'MntIVA' => false,
            'IVANoRec' => false,
            'IVAUsoComun' => false,
            'OtrosImp' => false,
            'MntTotal' => false,
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
     *   TasaImp -> 2
     *   FchDoc -> 3
     *   CdgSIISucur -> 4 (opcional)
     *   RUTDoc -> 5
     *   RznSoc -> 6 (opcional)
     *   MntExe -> 7
     *   MntNeto -> 8
     *   MntIVA -> 9 (calculable)
     *   IVANoRec: (opcional)
     *     CodIVANoRec -> 10
     *     MntIVANoRec -> 11 (calculable)
     *   FctProp -> 12
     *   OtrosImp: (opcional)
     *     CodImp -> 13
     *     TasaImp -> 14
     *     MntImp -> 15 (calculable)
     *   MntTotal -> 16 (calculable)
     *
     * @param archivo  Ruta al archivo que se desea cargar
     * @param separador Separador de campos del archivo CSV
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
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
                'TasaImp' => !empty($data[$i][2]) ? $data[$i][2] : false,
                'FchDoc' => $data[$i][3],
                'CdgSIISucur' => !empty($data[$i][4]) ? $data[$i][4] : false,
                'RUTDoc' => $data[$i][5],
                'RznSoc' => !empty($data[$i][6]) ? $data[$i][6] : false,
                'MntExe' => !empty($data[$i][7]) ? $data[$i][7] : false,
                'MntNeto' => !empty($data[$i][8]) ? $data[$i][8] : false,
                'MntIVA' => !empty($data[$i][9]) ? $data[$i][9] : 0,
            ];
            // agregar código y monto de iva no recuperable si existe
            if (!empty($data[$i][10])) {
                $detalle['IVANoRec'] = [
                    'CodIVANoRec' => $data[$i][10],
                    'MntIVANoRec' => !empty($data[$i][11]) ? $data[$i][11] : round($detalle['MntNeto'] * ($detalle['TasaImp']/100)),
                ];
            }
            // si hay factor de proporcionalidad se agrega
            if (!empty($data[$i][12])) {
                $detalle['FctProp'] = $data[$i][12];
            }
            // agregar código y monto de otros impuestos
            if (!empty($data[$i][13]) and !empty($data[$i][14])) {
                $detalle['OtrosImp'] = [
                    'CodImp' => $data[$i][13],
                    'TasaImp' => $data[$i][14],
                    'MntImp' => !empty($data[$i][15]) ? $data[$i][15] : round($detalle['MntNeto'] * ($data[$i][14]/100)),
                ];
            }
            // si hay monto total se agrega
            if (!empty($data[$i][16])) {
                $detalle['MntTotal'] = $data[$i][16];
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
     * @version 2015-09-06
     */
    private function getTotalesPeriodo()
    {
        $totales = [];
        foreach ($this->detalles as &$d) {
            if (!isset($totales[$d['TpoDoc']])) {
                $totales[$d['TpoDoc']] = [
                    'TpoDoc' => $d['TpoDoc'],
                    'TotDoc' => 0,
                    'TotMntExe' => 0,
                    'TotMntNeto' => 0,
                    'TotMntIVA' => 0,
                    'TotIVANoRec' => false,
                    'TotIVAUsoComun' => false,
                    'FctProp' => false,
                    'TotCredIVAUsoComun' => false,
                    'TotOtrosImp' => false,
                    'TotMntTotal' => 0,
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
        }
        return $totales;
    }

}
