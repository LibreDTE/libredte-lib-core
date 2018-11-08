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
 * Clase que representa el envío de un Libro de Compra o Venta
 *  - Libros simplificados: https://www.sii.cl/DJI/DJI_Formato_XML.html
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-11-27
 */
class LibroCompraVenta extends \sasco\LibreDTE\Sii\Base\Libro
{

    private $simplificado = false; ///< Indica si el libro es simplificado o no
    private $datos = null; ///< Arreglo con los datos del XML del libro

    private $total_default = [
        'TpoDoc' => null,
        'TotDoc' => 0,
        'TotAnulado' => false,
        'TotOpExe' => false,
        'TotMntExe' => 0,
        'TotMntNeto' => 0,
        'TotMntIVA' => 0,
        'TotIVAPropio' => false,
        'TotIVATerceros' => false,
        'TotLey18211' => false,
        'TotMntActivoFijo' => false,
        'TotMntIVAActivoFijo' => false,
        'TotIVANoRec' => false,
        'TotIVAUsoComun' => false,
        'FctProp' => false,
        'TotCredIVAUsoComun' => false,
        'TotIVAFueraPlazo' => false,
        'TotOtrosImp' => false,
        'TotIVARetTotal' => false,
        'TotIVARetParcial' => false,
        'TotImpSinCredito' => false,
        'TotMntTotal' => 0,
        'TotIVANoRetenido' => false,
        'TotMntNoFact' => false,
        'TotMntPeriodo' => false,
        'TotPsjNac' => false,
        'TotPsjInt' => false,
        'TotTabPuros' => false,
        'TotTabCigarrillos' => false,
        'TotTabElaborado' => false,
        'TotImpVehiculo' => false,
    ]; ///< Campos para totales

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
     * Método que permite obtener el ID del libro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-28
     */
    public function getID()
    {
        if ($this->datos===null) {
            $this->datos = $this->toArray();
        }
        return !empty($this->datos['LibroCompraVenta']['EnvioLibro']['@attributes']['ID']) ? $this->datos['LibroCompraVenta']['EnvioLibro']['@attributes']['ID'] : false;
    }

    /**
     * Método que agrega un detalle al listado que se enviará
     * @param detalle Arreglo con el resumen del DTE que se desea agregar
     * @return =true si se pudo agregar el detalle o =false si no se agregó por exceder el límite del libro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-03
     */
    public function agregar(array $detalle, $normalizar = true)
    {
        if ($normalizar)
            $this->normalizarDetalle($detalle);
        if (!$detalle['TpoDoc'])
            return false;
        $this->detalles[] = $detalle;
        return true;
    }

    /**
     * Método que normaliza un detalle del libro de compra o venta
     * @param detalle Arreglo con el resumen del DTE que se desea agregar
     * @return Arreglo con el detalle normalizado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-01-26
     */
    private function normalizarDetalle(array &$detalle)
    {
        // agregar nodos (esto para mantener orden del XML)
        $detalle = array_merge([
            'TpoDoc' => false,
            'Emisor' => false,
            'IndFactCompra' => false,
            'NroDoc' => false,
            'Anulado' => false,
            'Operacion' => false,
            'TpoImp' => 1,
            'TasaImp' => false,
            'NumInt' => false,
            'IndServicio' => false,
            'IndSinCosto' => false,
            'FchDoc' => false,
            'CdgSIISucur' => false,
            'RUTDoc' => false,
            'RznSoc' => false,
            'Extranjero' => false,
            'TpoDocRef' => false,
            'FolioDocRef' => false,
            'MntExe' => false,
            'MntNeto' => false,
            'MntIVA' => false,
            'MntActivoFijo' => false,
            'MntIVAActivoFijo' => false,
            'IVANoRec' => false,
            'IVAUsoComun' => false,
            'IVAFueraPlazo' => false,
            'IVAPropio' => false,
            'IVATerceros' => false,
            'Ley18211' => false,
            'OtrosImp' => false,
            'MntSinCred' => false,
            'IVARetTotal' => false,
            'IVARetParcial' => false,
            'CredEC' => false,
            'DepEnvase' => false,
            'Liquidaciones' => false,
            'MntTotal' => false,
            'IVANoRetenido' => false,
            'MntNoFact' => false,
            'MntPeriodo' => false,
            'PsjNac' => false,
            'PsjInt' => false,
            'TabPuros' => false,
            'TabCigarrillos' => false,
            'TabElaborado' => false,
            'ImpVehiculo' => false,
        ], $detalle);
        // si el caso está anulado se genera sólo lo mínimo pedido por el SII
        if (!empty($detalle['Anulado']) and $detalle['Anulado']=='A') {
            $detalle = [
                'TpoDoc' => $detalle['TpoDoc'],
                'NroDoc' => $detalle['NroDoc'],
                'Anulado' => $detalle['Anulado']
            ];
            return;
        }
        // largo campos
        if ($detalle['RznSoc']) {
            $detalle['RznSoc'] = mb_substr($detalle['RznSoc'], 0, 50);
        }
        // calcular valores que no se hayan entregado
        if (isset($detalle['FctProp'])) {
            if ($detalle['IVAUsoComun']===false)
                $detalle['IVAUsoComun'] = round($detalle['MntNeto'] * ($detalle['TasaImp']/100));
        } else if (!$detalle['MntIVA'] and !is_array($detalle['IVANoRec']) and $detalle['TasaImp'] and $detalle['MntNeto']) {
            $detalle['MntIVA'] = round($detalle['MntNeto'] * ($detalle['TasaImp']/100));
        }
        // si el monto total es 0 pero no se asigno neto ni exento se coloca
        if ($detalle['MntExe']===false and $detalle['MntNeto']===false and $detalle['MntTotal']===0) {
            $detalle['MntNeto'] = 0;
        }
        // colocar montos que falten en 0 si es solo exento y no neto
        if ($detalle['MntNeto']===false and $detalle['MntExe']) {
            $detalle['TasaImp'] = \sasco\LibreDTE\Sii::getIVA();
            $detalle['MntNeto'] = 0;
            $detalle['MntIVA'] = 0;
        }
        // normalizar IVA no recuperable
        if (!empty($detalle['IVANoRec'])) {
            if (!isset($detalle['IVANoRec'][0]))
                $detalle['IVANoRec'] = [$detalle['IVANoRec']];
            // si son múltiples iva no recuperable se arma arreglo real
            if (strpos($detalle['IVANoRec'][0]['CodIVANoRec'], ',')) {
                $CodIVANoRec = explode(',', $detalle['IVANoRec'][0]['CodIVANoRec']);
                $MntIVANoRec = explode(',', $detalle['IVANoRec'][0]['MntIVANoRec']);
                $detalle['IVANoRec'] = [];
                $n_inr = count($CodIVANoRec);
                for ($i=0; $i<$n_inr; $i++) {
                    $detalle['IVANoRec'][] = [
                        'CodIVANoRec' => $CodIVANoRec[$i],
                        'MntIVANoRec' => $MntIVANoRec[$i],
                    ];
                }
            }
        }
        // normalizar otros impuestos
        if (!empty($detalle['OtrosImp'])) {
            if (!isset($detalle['OtrosImp'][0]))
                $detalle['OtrosImp'] = [$detalle['OtrosImp']];
            // si son múltiples impuestos se arma arreglo real
            if (strpos($detalle['OtrosImp'][0]['CodImp'], ',')) {
                $CodImp = explode(',', $detalle['OtrosImp'][0]['CodImp']);
                $TasaImp = explode(',', $detalle['OtrosImp'][0]['TasaImp']);
                $MntImp = explode(',', $detalle['OtrosImp'][0]['MntImp']);
                $detalle['OtrosImp'] = [];
                $n_impuestos = count($CodImp);
                for ($i=0; $i<$n_impuestos; $i++) {
                    $detalle['OtrosImp'][] = [
                        'CodImp' => $CodImp[$i],
                        'TasaImp' => !empty($TasaImp[$i]) ? $TasaImp[$i] : false,
                        'MntImp' => $MntImp[$i],
                    ];
                }
            }
            // calcular y agregar IVA no retenido si corresponde
            $retenido = ImpuestosAdicionales::getRetenido($detalle['OtrosImp']);
            if ($retenido) {
                // si el iva retenido es total
                if ($retenido == $detalle['MntIVA']) {
                    $detalle['IVARetTotal'] = $retenido;
                }
                // si el iva retenido es parcial
                else {
                    $detalle['IVARetParcial'] = $retenido;
                    $detalle['IVANoRetenido'] = $detalle['MntIVA'] - $retenido;
                }
            }
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
                    if (ImpuestosAdicionales::getTipo($OtrosImp['CodImp'])=='R') {
                        $detalle['MntTotal'] -= $OtrosImp['MntImp'];
                    }
                }
            }
            // agregar otro montos e impuestos al total
            $detalle['MntTotal'] += (int)$detalle['MntSinCred'] + (int)$detalle['TabPuros'] + (int)$detalle['TabCigarrillos'] + (int)$detalle['TabElaborado'] + (int)$detalle['ImpVehiculo'];
        }
        // si no hay no hay monto neto, no se crean campos para IVA
        if ($detalle['MntNeto']===false) {
            $detalle['MntNeto'] = $detalle['TasaImp'] = $detalle['MntIVA'] = false;
        }
        // si el código de sucursal no existe se pone a falso, esto básicamente
        // porque algunos sistemas podrían usar 0 cuando no hay CdgSIISucur
        if (!$detalle['CdgSIISucur']) {
            $detalle['CdgSIISucur'] = false;
        }
    }

    /**
     * Método que agrega el detalle del libro de compras a partir de un archivo
     * CSV.
     *
     * Formato del archivo (desde la columna A):
     *   0: TpoDoc
     *   1: NroDoc
     *   2: RUTDoc
     *   3: TasaImp
     *   4: RznSoc (opcional)
     *   5: TpoImp (opcional, por defecto 1)
     *   6: FchDoc
     *   7: Anulado (opcional, 'A' sólo para folios anulados, no anulados con NC o ND)
     *   8: MntExe (opcional)
     *   9: MntNeto (opcional)
     *   10: MntIVA (calculable a partir de MntNeto * TasaImp, si no hay es 0)
     *   IVANoRec: (opcional)
     *     11: CodIVANoRec
     *     12: MntIVANoRec (calculable)
     *   13: IVAUsoComun (calculable a partir de FctProp)
     *   OtrosImp: (opcional)
     *     14: CodImp
     *     15: TasaImp
     *     16: MntImp (calculable a partir de TasaImp)
     *   17: MntSinCred (opcional)
     *   18: MntActivoFijo (opcional)
     *   19: MntIVAActivoFijo (opcional)
     *   20: IVANoRetenido (opcional)
     *   21: TabPuros (opcional)
     *   22: TabCigarrillos (opcional)
     *   23: TabElaborado (opcional)
     *   24: ImpVehiculo (opcional)
     *   25: CdgSIISucur (opcional)
     *   26: NumInt (opcional)
     *   27: Emisor (opcional, '1' sólo si es NC o ND de FC emitida por el emisor del libro)
     *   28: MntTotal -> 18 (calculable: MntExe + MntNeto + MntIVA + MntIVANoRec + IVAUsoComun + MntImp + MntSinCred + TabPuros + TabCigarrillos + TabElaborado + ImpVehiculo)
     *   29: FctProp -> 14 (permite calcular el valor IVAUsoComun, no es parte del detalle real)
     *
     * @param archivo  Ruta al archivo que se desea cargar
     * @param separador Separador de campos del archivo CSV
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-09-22
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
                'IVANoRec' => false, // 11 y 12
                'IVAUsoComun' => !empty($data[$i][13]) ? $data[$i][13] : false,
                'OtrosImp' => false, // 14 al 16
                'MntSinCred' => !empty($data[$i][17]) ? $data[$i][17] : false,
                'MntActivoFijo' => !empty($data[$i][18]) ? $data[$i][18] : false,
                'MntIVAActivoFijo' => !empty($data[$i][19]) ? $data[$i][19] : false,
                'IVANoRetenido' => !empty($data[$i][20]) ? $data[$i][20] : false,
                'TabPuros' => !empty($data[$i][21]) ? $data[$i][21] : false,
                'TabCigarrillos' => !empty($data[$i][22]) ? $data[$i][22] : false,
                'TabElaborado' => !empty($data[$i][23]) ? $data[$i][23] : false,
                'ImpVehiculo' => !empty($data[$i][24]) ? $data[$i][24] : false,
                'CdgSIISucur' => !empty($data[$i][25]) ? $data[$i][25] : false,
                'NumInt' => !empty($data[$i][26]) ? $data[$i][26] : false,
                'Emisor' => !empty($data[$i][27]) ? $data[$i][27] : false,
                //'MntTotal' => !empty($data[$i][28]) ? $data[$i][28] : false,
                //'FctProp' => !empty($data[$i][29]) ? $data[$i][29] : false,
            ];
            // agregar código y monto de iva no recuperable si existe
            if (!empty($data[$i][11])) {
                $detalle['IVANoRec'] = [
                    'CodIVANoRec' => $data[$i][11],
                    'MntIVANoRec' => !empty($data[$i][12]) ? $data[$i][12] : round($detalle['MntNeto'] * ($detalle['TasaImp']/100)),
                ];
            }
            // agregar código y monto de otros impuestos
            if (!empty($data[$i][14]) and (!empty($data[$i][15]) or !empty($data[$i][16]))) {
                $detalle['OtrosImp'] = [
                    'CodImp' => $data[$i][14],
                    'TasaImp' => !empty($data[$i][15]) ? $data[$i][15] : 0,
                    'MntImp' => !empty($data[$i][16]) ? $data[$i][16] : round($detalle['MntNeto'] * ($data[$i][15]/100)),
                ];
            }
            // si hay monto total se agrega
            if (!empty($data[$i][28])) {
                $detalle['MntTotal'] = $data[$i][28];
            }
            // si hay factor de proporcionalidad se agrega
            if (!empty($data[$i][29])) {
                $detalle['FctProp'] = $data[$i][29];
            }
            // agregar a los detalles
            $this->agregar($detalle);
        }
    }

    /**
     * Método que agrega el detalle del libro de ventas a partir de un archivo
     * CSV.
     *
     * Formato del archivo (desde la columna A):
     *   0: TpoDoc
     *   1: NroDoc
     *   2: RUTDoc
     *   3: TasaImp
     *   4: RznSoc (opcional)
     *   5: FchDoc
     *   6: Anulado (opcional, 'A' sólo para folios anulados, no anulados con NC o ND)
     *   7: MntExe (opcional)
     *   8: MntNeto (opcional)
     *   9: MntIVA (calculable)
     *   10: IVAFueraPlazo
     *   OtrosImp (opcional):
     *     11: CodImp
     *     12: TasaImp
     *     13: MntImp (calculable)
     *   14: IVAPropio
     *   15: IVATerceros
     *   16: IVARetTotal
     *   17: IVARetParcial
     *   18: IVANoRetenido
     *   19: Ley18211
     *   20: CredEC
     *   21: TpoDocRef
     *   22: FolioDocRef
     *   23: DepEnvase
     *   24: MntNoFact
     *   25: MntPeriodo
     *   26: PsjNac
     *   27: PsjInt
     *   Extranjero (sólo DTE de exportación):
     *     28: NumId
     *     29: Nacionalidad
     *   30: IndServicio (=1 servicios periodicos domiciliarios, =2 otros servicios periodicos, =3 servicios no periodicos)
     *   31: IndSinCosto
     *   Liquidaciones (opcional):
     *     32: RutEmisor
     *     33: ValComNeto
     *     34: ValComExe
     *     35: ValComIVA
     *   36: CdgSIISucur (opcional)
     *   37: NumInt
     *   38: Emisor
     *   39: MntTotal (calculable)
     *
     * @param archivo Ruta al archivo que se desea cargar
     * @param separador Separador de campos del archivo CSV
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-06
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
                'RUTDoc' => !empty($data[$i][2]) ? $data[$i][2] : false,
                'TasaImp' => !empty($data[$i][3]) ? $data[$i][3] : false,
                'RznSoc' => !empty($data[$i][4]) ? $data[$i][4] : false,
                'FchDoc' => !empty($data[$i][5]) ? $data[$i][5] : false,
                'Anulado' => !empty($data[$i][6]) ? 'A' : false,
                'MntExe' => !empty($data[$i][7]) ? $data[$i][7] : false,
                'MntNeto' => !empty($data[$i][8]) ? $data[$i][8] : false,
                'MntIVA' => !empty($data[$i][9]) ? $data[$i][9] : 0,
                'IVAFueraPlazo' => !empty($data[$i][10]) ? $data[$i][10] : false,
                'IVAPropio' => !empty($data[$i][14]) ? $data[$i][14] : false,
                'IVATerceros' => !empty($data[$i][15]) ? $data[$i][15] : false,
                'IVARetTotal' => !empty($data[$i][16]) ? $data[$i][16] : false,
                'IVARetParcial' => !empty($data[$i][17]) ? $data[$i][17] : false,
                'IVANoRetenido' => !empty($data[$i][18]) ? $data[$i][18] : false,
                'Ley18211' => !empty($data[$i][19]) ? $data[$i][19] : false,
                'CredEC' => !empty($data[$i][20]) ? $data[$i][20] : false,
                'TpoDocRef' => !empty($data[$i][21]) ? $data[$i][21] : false,
                'FolioDocRef' => !empty($data[$i][22]) ? $data[$i][22] : false,
                'DepEnvase' => !empty($data[$i][23]) ? $data[$i][23] : false,
                'MntNoFact' => !empty($data[$i][24]) ? $data[$i][24] : false,
                'MntPeriodo' => !empty($data[$i][25]) ? $data[$i][25] : false,
                'PsjNac' => !empty($data[$i][26]) ? $data[$i][26] : false,
                'PsjInt' => !empty($data[$i][27]) ? $data[$i][27] : false,
                'IndServicio' => !empty($data[$i][30]) ? $data[$i][30] : false,
                'IndSinCosto' => !empty($data[$i][31]) ? $data[$i][31] : false,
                'CdgSIISucur' => !empty($data[$i][36]) ? $data[$i][36] : false,
                'NumInt' => !empty($data[$i][37]) ? $data[$i][37] : false,
                'Emisor' => !empty($data[$i][38]) ? 1 : false,
            ];
            // agregar código y monto de otros impuestos
            if (!empty($data[$i][11])) {
                $detalle['OtrosImp'] = [
                    'CodImp' => $data[$i][11],
                    'TasaImp' => !empty($data[$i][12]) ? $data[$i][12] : false,
                    'MntImp' => !empty($data[$i][13]) ? $data[$i][13] : round($detalle['MntNeto'] * ($data[$i][12]/100)),
                ];
            }
            // agregar datos extranjeros
            if (!empty($data[$i][28]) or !empty($data[$i][29])) {
                $detalle['Extranjero'] = [
                    'NumId' => !empty($data[$i][28]) ? $data[$i][28] : false,
                    'Nacionalidad' => !empty($data[$i][29]) ? $data[$i][29] : false,
                ];
            }
            // agregar datos de liquidaciones
            if (!empty($data[$i][32])) {
                $detalle['Liquidaciones'] = [
                    'RutEmisor' => $data[$i][32],
                    'ValComNeto' => !empty($data[$i][33]) ? $data[$i][33] : false,
                    'ValComExe' => !empty($data[$i][34]) ? $data[$i][34] : false,
                    'ValComIVA' => !empty($data[$i][35]) ? $data[$i][35] : false,
                ];
            }
            // si hay monto total se agrega
            if (!empty($data[$i][39])) {
                $detalle['MntTotal'] = $data[$i][39];
            }
            // agregar a los detalles
            $this->agregar($detalle);
        }
    }

    /**
     * Método para asignar la caratula
     * @param caratula Arreglo con datos del envío: RutEnvia, FchResol y NroResol
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-06
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
        $this->id = 'LibreDTE_LIBRO_'.$this->caratula['TipoOperacion'].'_'.str_replace('-', '', $this->caratula['RutEmisorLibro']).'_'.str_replace('-', '', $this->caratula['PeriodoTributario']).'_'.date('U');
    }

    /**
     * Método que genera el XML del libro IECV para el envío al SII
     * @param incluirDetalle =true no se incluirá el detalle de los DTEs (sólo se usará para calcular totales)
     * @return XML con el envio del libro de compra y venta firmado o =false si no se pudo generar o firmar el envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-02-12
     */
    public function generar($incluirDetalle = true)
    {
        // si ya se había generado se entrega directamente
        if ($this->xml_data)
            return $this->xml_data;
        // generar totales de DTE y sus montos
        $TotalesPeriodo = $this->getResumen();
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
                    'Detalle' => $incluirDetalle ? $this->getDetalle() : false,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ],
            ]
        ])->saveXML();
        // firmar XML del envío y entregar
        $this->xml_data = (!$this->simplificado and $this->Firma) ? $this->Firma->signXML($xmlEnvio, '#'.$this->id, 'EnvioLibro', true) : $xmlEnvio;
        return $this->xml_data;
    }

    /**
     * Método que permite agregar sólo resumen al libro (sin detalle), esto para
     * poder agregar, por ejemplo, el resumen de las boletas en papel sin tener
     * que agregar la totalidad al detalle
     * @param resumen Arreglo con índice el DTE y valor arreglo con el resumen de ese DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-02-12
     */
    public function setResumen($resumen)
    {
        // verificar que se haya pasado el tipo de documento y total como mínimo
        foreach ($resumen as $tipo) {
            if (!isset($tipo['TpoDoc']) or !isset($tipo['TotDoc'])) {
                return false;
            }
        }
        // asignar resumen
        $this->resumen = [];
        foreach ($resumen as $tipo) {
            $this->resumen[$tipo['TpoDoc']] = $tipo;
        }
    }

    /**
     * Método que obtiene los datos para generar los tags TotalesPeriodo
     * @return Arreglo con los datos para generar los tags TotalesPeriodo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-11-27
     */
    public function getResumen()
    {
        $totales = [];
        // agregar resumen de detalles
        foreach ($this->detalles as &$d) {
            if (!isset($totales[$d['TpoDoc']])) {
                $totales[$d['TpoDoc']] = array_merge($this->total_default, ['TpoDoc'=>$d['TpoDoc']]);
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
            if (!empty($d['Anulado']) and $d['Anulado']=='A') {
                $totales[$d['TpoDoc']]['TotAnulado']++;
            }
            // si hay activo fijo se contabiliza
            if (!empty($d['MntActivoFijo'])) {
                $totales[$d['TpoDoc']]['TotMntActivoFijo'] += $d['MntActivoFijo'];
            }
            if (!empty($d['MntIVAActivoFijo'])) {
                $totales[$d['TpoDoc']]['TotMntIVAActivoFijo'] += $d['MntIVAActivoFijo'];
            }
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
            // contabilizar IVA fuera de plazo
            if (!empty($d['IVAFueraPlazo'])) {
                $totales[$d['TpoDoc']]['TotIVAFueraPlazo'] += $d['IVAFueraPlazo'];
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
            if (!empty($d['MntSinCred'])) {
                $totales[$d['TpoDoc']]['TotImpSinCredito'] += $d['MntSinCred'];
            }
            // contabilidad IVA retenido total
            if (!empty($d['IVARetTotal'])) {
                $totales[$d['TpoDoc']]['TotIVARetTotal'] += $d['IVARetTotal'];
            }
            // contabilizar IVA retenido parcial
            if (!empty($d['IVARetParcial'])) {
                $totales[$d['TpoDoc']]['TotIVARetParcial'] += $d['IVARetParcial'];
            }
            // contabilizar IVA no retenido
            if (!empty($d['IVANoRetenido'])) {
                $totales[$d['TpoDoc']]['TotIVANoRetenido'] += $d['IVANoRetenido'];
            }
            // contabilidar impuesto vehículos
            if (!empty($d['ImpVehiculo'])) {
                $totales[$d['TpoDoc']]['TotImpVehiculo'] += $d['ImpVehiculo'];
            }
        }
        // agregar resumenes pasados que no se hayan generado por los detalles
        foreach ($this->resumen as $tipo => $resumen) {
            if (!isset($totales[$tipo])) {
                $totales[$tipo] = array_merge($this->total_default, $resumen);
            }
        }
        // entregar resumen
        ksort($totales);
        return $totales;
    }

    /**
     * Método que entrega el resumen manual, de los totales registrados en el
     * XML del libro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-10-12
     */
    public function getResumenManual()
    {
        $manual = [];
        if (isset($this->toArray()['LibroCompraVenta']['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'])) {
            $totales = $this->toArray()['LibroCompraVenta']['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'];
            if (!isset($totales[0]))
                $totales = [$totales];
            foreach ($totales as $total) {
                if (isset($total['TpoDoc']) and in_array($total['TpoDoc'], [35, 38, 48])) {
                    $manual[$total['TpoDoc']] = array_merge($this->total_default, $total);
                }
            }
        }
        return $manual;
    }

    /**
     * Método que entrega el resumen de las boletas electrónicas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-10-12
     */
    public function getResumenBoletas()
    {
        $manual = [];
        if (isset($this->toArray()['LibroCompraVenta']['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'])) {
            $totales = $this->toArray()['LibroCompraVenta']['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'];
            if (!isset($totales[0]))
                $totales = [$totales];
            foreach ($totales as $total) {
                if (in_array($total['TpoDoc'], [39, 41])) {
                    $manual[$total['TpoDoc']] = array_merge($this->total_default, $total);
                }
            }
        }
        return $manual;
    }

    /**
     * Método que entrega el detalle a incluir en XML, en el libro de ventas no
     * se incluyen ciertos documentos (como boletas), por eso se usa este método
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-02-12
     */
    public function getDetalle()
    {
        if ($this->caratula['TipoOperacion']=='VENTA') {
            $omitir = [35, 38, 39, 41, 105, 500, 501, 919, 920, 922, 924];
            $detalles = [];
            foreach ($this->detalles as $d) {
                if (!in_array($d['TpoDoc'], $omitir)) {
                    $detalles[] = $d;
                }
            }
            return $detalles;
        }
        return $this->detalles;
    }

    /**
     * Método que obtiene los datos de las compras en el formato que se usa en
     * el archivo CSV
     * @return Arreglo con los datos de las compras
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-10-06
     */
    public function getCompras()
    {
        $detalle = [];
        foreach ($this->detalles as $d) {
            // armar iva no recuperable
            $iva_no_recuperable_codigo = [];
            $iva_no_recuperable_monto = [];
            foreach ((array)$d['IVANoRec'] as $inr) {
                $iva_no_recuperable_codigo[] = $inr['CodIVANoRec'];
                $iva_no_recuperable_monto[] = $inr['MntIVANoRec'];
            }
            // armar impuestos adicionales
            $impuesto_adicional_codigo = [];
            $impuesto_adicional_tasa = [];
            $impuesto_adicional_monto = [];
            foreach ((array)$d['OtrosImp'] as $ia) {
                $impuesto_adicional_codigo[] = $ia['CodImp'];
                $impuesto_adicional_tasa[] = $ia['TasaImp'];
                $impuesto_adicional_monto[] = $ia['MntImp'];
            }
            // armar detalle
            $detalle[] = [
                (int)$d['TpoDoc'],
                (int)$d['NroDoc'],
                $d['RUTDoc'],
                (int)$d['TasaImp'],
                $d['RznSoc'],
                $d['TpoImp']!==false ? $d['TpoImp'] : 1,
                $d['FchDoc'],
                $d['Anulado']!==false ? $d['Anulado'] : null,
                $d['MntExe']!==false ? $d['MntExe'] : null,
                $d['MntNeto']!==false ? $d['MntNeto'] : null,
                (int)$d['MntIVA'],
                $iva_no_recuperable_codigo ? implode(',', $iva_no_recuperable_codigo) : null,
                $iva_no_recuperable_monto ? implode(',', $iva_no_recuperable_monto) : null,
                $d['IVAUsoComun']!==false ? $d['IVAUsoComun'] : null,
                $impuesto_adicional_codigo ? implode(',', $impuesto_adicional_codigo) : null,
                $impuesto_adicional_tasa ? implode(',', $impuesto_adicional_tasa) : null,
                $impuesto_adicional_monto ? implode(',', $impuesto_adicional_monto) : null,
                $d['MntSinCred']!==false ? $d['MntSinCred'] : null,
                $d['MntActivoFijo']!==false ? $d['MntActivoFijo'] : null,
                $d['MntIVAActivoFijo']!==false ? $d['MntIVAActivoFijo'] : null,
                $d['IVANoRetenido']!==false ? $d['IVANoRetenido'] : null,
                $d['TabPuros']!==false ? $d['TabPuros'] : null,
                $d['TabCigarrillos']!==false ? $d['TabCigarrillos'] : null,
                $d['TabElaborado']!==false ? $d['TabElaborado'] : null,
                $d['ImpVehiculo']!==false ? $d['ImpVehiculo'] : null,
                $d['CdgSIISucur']!==false ? $d['CdgSIISucur'] : null,
                $d['NumInt']!==false ? $d['NumInt'] : null,
                $d['Emisor']!==false ? $d['Emisor'] : null,
                $d['MntTotal']!==false ? $d['MntTotal'] : null,
                (isset($d['FctProp']) and $d['FctProp']!==false) ? $d['FctProp'] : null,
            ];
        }
        return $detalle;
    }

}
