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
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-05
 */
class LibroCompraVenta
{

    private $detalles = []; ///< Arreglos con el detalle de los DTEs que se reportarán
    private $xml_data; ///< String con el documento XML

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
     * @version 2015-09-06
     */
    private function normalizarDetalle(array &$detalle)
    {
        // agregar nodos (esto para mantener orden del XML)
        $detalle = array_merge([
            'TpoDoc' => false,
            'NroDoc' => false,
            'TasaImp' => 0,
            'FchDoc' => false,
            'RUTDoc' => false,
            'RznSoc' => false,
            'MntExe' => false,
            'MntNeto' => false,
            'MntIVA' => 0,
            'IVANoRec' => false,
            'IVAUsoComun' => false,
            'OtrosImp' => false,
            'MntTotal' => false,
        ], $detalle);
        // calcular valores que no se hayan entregado
        if (isset($detalle['FctProp'])) {
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
        if (!$detalle['MntTotal']) {
            // calcular monto total inicial
            $detalle['MntTotal'] = $detalle['MntExe'] + $detalle['MntNeto'] + $detalle['MntIVA'];
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
    }

    /**
     * Método que realiza el envío del libro IECV al SII
     * @param caratula Arreglo con datos de la carátula del libro de compra y venta
     * @param Firma Objeto con la firma electrónica
     * @return Track ID del envío o =false si hubo algún problema al enviar el documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-04
     */
    public function enviar(array $caratula, \sasco\LibreDTE\FirmaElectronica $Firma)
    {
        // generar XML que se enviará
        if (!$this->xml_data)
            $this->xml_data = $this->generar($caratula, $Firma);
        if (!$this->xml_data)
            return false;
        // solicitar token
        $token = Autenticacion::getToken($Firma);
        if (!$token)
            return false;
        // enviar DTE
        $result = \sasco\LibreDTE\Sii::enviar($this->caratula['RutEnvia'], $this->caratula['RutEmisorLibro'], $this->xml_data, $token);
        if ($result===false)
            return false;
        if (!is_numeric((string)$result->TRACKID))
            return false;
        return (int)(string)$result->TRACKID;
    }

    /**
     * Método que genera el XML del libro IECV para el envío al SII
     * @param caratula Arreglo con datos de la carátula del libro de compra y venta
     * @param Firma Objeto con la firma electrónica
     * @param incluirDetalle =true no se incluirá el detalle de los DTEs (sólo se usará para calcular totales)
     * @return XML con el envio del libro de compra y venta firmado o =false si no se pudo generar o firmar el envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-06
     */
    public function generar(array $caratula, \sasco\LibreDTE\FirmaElectronica $Firma = null, $incluirDetalle = true)
    {
        // si ya se había generado se entrega directamente
        if ($this->xml_data)
            return $this->xml_data;
        // generar totales de DTE y sus montos
        $TotalesPeriodo = $this->getTotalesPeriodo();
        // armar caratula
        $this->caratula = array_merge([
            'RutEmisorLibro' => false,
            'RutEnvia' => false,
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
        // generar XML del envío
        $ID = 'LIBRO_'.$this->caratula['TipoOperacion'].'_'.str_replace('-', '', $this->caratula['PeriodoTributario']).'_'.date('U');
        $xmlEnvio = (new \sasco\LibreDTE\XML())->generate([
            'LibroCompraVenta' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte LibroCVS_v10.xsd',
                    'version' => '1.0',
                ],
                'EnvioLibro' => [
                    '@attributes' => [
                        'ID' => $ID,
                    ],
                    'Caratula' => $this->caratula,
                    'ResumenPeriodo' => [
                        'TotalesPeriodo' => $TotalesPeriodo,
                    ],
                    'Detalle' => $incluirDetalle ? $this->detalles : false,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ],
            ]
        ])->saveXML();
        // firmar XML del envío y entregar
        $this->xml_data = $Firma ? $Firma->signXML($xmlEnvio, '#'.$ID, 'EnvioLibro', true) : $xmlEnvio;
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
