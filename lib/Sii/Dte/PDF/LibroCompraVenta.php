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

namespace sasco\LibreDTE\Sii\Dte\PDF;

/**
 * Clase para generar el PDF de la IECV
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-07-26
 */
class LibroCompraVenta extends \sasco\LibreDTE\PDF
{

    private $dte_tipo_operacion = [
        'suma' => [30, 32, 33, 34, 35, 38, 39, 41, 48, 55, 56, 110, 111, 914],
        'resta' => [45, 46, 60, 61, 112],
    ]; ///< Tipos de operaciones si suman o restan los DTE al vender

    /**
     * Constructor de la clase
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-09
     */
    public function __construct()
    {
        parent::__construct('L');
        $this->SetTitle('IECV');
    }

    /**
     * Método que agrega un libro al PDF
     * @param libro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-10-06
     */
    public function agregar(array $libro)
    {
        $this->startPageGroup();
        $this->AddPage();
        if (isset($libro['LibroCompraVenta']))
            $libro = $libro['LibroCompraVenta'];
        // título del libro
        $this->SetFont('helvetica', 'B', 16);
        $this->Texto('Libro de '.ucfirst(strtolower($libro['EnvioLibro']['Caratula']['TipoOperacion'])), null, null, 'C');
        $this->Ln();
        $this->Ln();
        // carátula
        $this->SetFont('helvetica', 'B', 12);
        $this->Texto('I.- Carátula');
        $this->Ln();
        $this->Ln();
        $this->SetFont('helvetica', '', 9);
        $titulos = ['Emisor', 'Firma', 'Período', 'Resolución', 'N°', 'Operación', 'Tipo de libro', 'Tipo de envio', 'Aut. rectific.'];
        $this->addTable($titulos, [$libro['EnvioLibro']['Caratula']]);
        $this->Ln();
        // resumenes
        $this->SetFont('helvetica', 'B', 12);
        $this->Texto('II.- Resumen');
        $this->Ln();
        $this->Ln();
        $this->SetFont('helvetica', '', 9);
        if (isset($libro['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'])) {
            // agregar resumen
            if (!isset($libro['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'][0])) {
                $libro['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'] = [$libro['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo']];
            }
            $resumen = [];
            $total_resumen = [
                    'TotDoc' => 0,
                    'TotMntExe' => 0,
                    'TotMntNeto' => 0,
                    'TotMntIVA' => 0,
                    'CodImp' => 0,
                    'TotMntImp' => 0,
                    'TotIVARetParcial' => 0,
                    'TotIVARetTotal' => 0,
                    'TotIVANoRetenido' => 0,
                    'TotMntTotal' => 0,
            ];
            foreach ($libro['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'] as $total) {
                // contabilizar totales del resumen
                $total_resumen['TotDoc'] += $total['TotDoc'];
                if (in_array($total['TpoDoc'], $this->dte_tipo_operacion['suma'])) {
                    $total_resumen['TotMntExe'] += !empty($total['TotMntExe']) ? $total['TotMntExe'] : 0;
                    $total_resumen['TotMntNeto'] += !empty($total['TotMntNeto']) ? $total['TotMntNeto'] : 0;
                    $total_resumen['TotMntIVA'] += !empty($total['TotMntIVA']) ? $total['TotMntIVA'] : 0;
                    $total_resumen['TotMntImp'] += !empty($total['TotOtrosImp']['TotMntImp']) ? $total['TotOtrosImp']['TotMntImp'] : 0;
                    $total_resumen['TotIVARetParcial'] += !empty($total['TotIVARetParcial']) ? $total['TotIVARetParcial'] : 0;
                    $total_resumen['TotIVARetTotal'] += !empty($total['TotIVARetTotal']) ? $total['TotIVARetTotal'] : 0;
                    $total_resumen['TotIVANoRetenido'] += !empty($total['TotIVANoRetenido']) ? $total['TotIVANoRetenido'] : 0;
                    $total_resumen['TotMntTotal'] += $total['TotMntTotal'];
                }
                else if (in_array($total['TpoDoc'], $this->dte_tipo_operacion['resta'])) {
                    $total_resumen['TotMntExe'] -= !empty($total['TotMntExe']) ? $total['TotMntExe'] : 0;
                    $total_resumen['TotMntNeto'] -= !empty($total['TotMntNeto']) ? $total['TotMntNeto'] : 0;
                    $total_resumen['TotMntIVA'] -= !empty($total['TotMntIVA']) ? $total['TotMntIVA'] : 0;
                    $total_resumen['TotMntImp'] -= !empty($total['TotOtrosImp']['TotMntImp']) ? $total['TotOtrosImp']['TotMntImp'] : 0;
                    $total_resumen['TotIVARetParcial'] -= !empty($total['TotIVARetParcial']) ? $total['TotIVARetParcial'] : 0;
                    $total_resumen['TotIVARetTotal'] -= !empty($total['TotIVARetTotal']) ? $total['TotIVARetTotal'] : 0;
                    $total_resumen['TotIVANoRetenido'] -= !empty($total['TotIVANoRetenido']) ? $total['TotIVANoRetenido'] : 0;
                    $total_resumen['TotMntTotal'] -= $total['TotMntTotal'];
                }
                // agregar al resumen
                $resumen[] = [
                    $total['TpoDoc'],
                    num($total['TotDoc']),
                    !empty($total['TotMntExe']) ? num($total['TotMntExe']) : '',
                    !empty($total['TotMntNeto']) ? num($total['TotMntNeto']) : '',
                    !empty($total['TotMntIVA']) ? num($total['TotMntIVA']) : '',
                    !empty($total['TotOtrosImp']['CodImp']) ? $total['TotOtrosImp']['CodImp'] : '',
                    !empty($total['TotOtrosImp']['TotMntImp']) ? num($total['TotOtrosImp']['TotMntImp']) : '',
                    !empty($total['TotIVARetParcial']) ? num($total['TotIVARetParcial']) : '',
                    !empty($total['TotIVARetTotal']) ? num($total['TotIVARetTotal']) : '',
                    !empty($total['TotIVANoRetenido']) ? num($total['TotIVANoRetenido']) : '',
                    num($total['TotMntTotal']),
                ];
            }
            // agregar totales
            $resumen[] = [
                '',
                num($total_resumen['TotDoc']),
                !empty($total_resumen['TotMntExe']) ? num($total_resumen['TotMntExe']) : '',
                !empty($total_resumen['TotMntNeto']) ? num($total_resumen['TotMntNeto']) : '',
                !empty($total_resumen['TotMntIVA']) ? num($total_resumen['TotMntIVA']) : '',
                '',
                !empty($total_resumen['TotMntImp']) ? num($total_resumen['TotMntImp']) : '',
                !empty($total_resumen['TotIVARetParcial']) ? num($total_resumen['TotIVARetParcial']) : '',
                !empty($total_resumen['TotIVARetTotal']) ? num($total_resumen['TotIVARetTotal']) : '',
                !empty($total_resumen['TotIVANoRetenido']) ? num($total_resumen['TotIVANoRetenido']) : '',
                num($total_resumen['TotMntTotal']),
            ];
            // agregar tabla
            $titulos = ['DTE', 'Total', 'Exento', 'Neto', 'IVA', 'Imp', 'Monto', 'Ret parc.', 'Ret tot.', 'No reten.', 'Total'];
            $this->addTable($titulos, $resumen, ['width'=>[10, 19, 26, 27, 26, 10, 26, 26, 26, 26, 27]]);
        } else {
            $this->Texto('Sin movimientos');
            $this->Ln();
            $this->Ln();
        }
        // detalle
        $this->SetFont('helvetica', 'B', 12);
        $this->Texto('III.- Detalle');
        $this->Ln();
        $this->Ln();
        $this->SetFont('helvetica', '', 9);
        if (isset($libro['EnvioLibro']['Detalle'])) {
            if (!isset($libro['EnvioLibro']['Detalle'][0])) {
                $libro['EnvioLibro']['Detalle'] = [$libro['EnvioLibro']['Detalle']];
            }
            $detalle = [];
            foreach ($libro['EnvioLibro']['Detalle'] as $d) {
                // impuesto adicional
                if (!empty($d['OtrosImp'])) {
                    if (!isset($d['OtrosImp'][0]))
                        $d['OtrosImp'] = [$d['OtrosImp']];
                    $n_OtrosImp = count($d['OtrosImp']);
                } else $n_OtrosImp = 0;
                // se agrega detalle
                $detalle[] = [
                    $d['TpoDoc'],
                    $d['NroDoc'],
                    !empty($d['FchDoc']) ? $d['FchDoc'] : ((!empty($d['Anulado']) && $d['Anulado']=='A') ? 'ANULADO' : ''),
                    !empty($d['RUTDoc']) ? $d['RUTDoc'] : '',
                    !empty($d['MntExe']) ? num($d['MntExe']) : '',
                    !empty($d['MntNeto']) ? num($d['MntNeto']) : '',
                    !empty($d['MntIVA']) ? num($d['MntIVA']) : '',
                    $n_OtrosImp ? $d['OtrosImp'][0]['CodImp'] : '',
                    $n_OtrosImp ? $d['OtrosImp'][0]['TasaImp'] : '',
                    $n_OtrosImp ? num($d['OtrosImp'][0]['MntImp']) : '',
                    !empty($d['IVARetParcial']) ? num($d['IVARetParcial']) : '',
                    !empty($d['IVARetTotal']) ? num($d['IVARetTotal']) : '',
                    !empty($d['IVANoRetenido']) ? num($d['IVANoRetenido']) : '',
                    isset($d['MntTotal']) ? num($d['MntTotal']) : '',
                ];
                // agregar otros impuestos adicionales
                if ($n_OtrosImp>1) {
                    for ($i=1; $i<$n_OtrosImp; $i++) {
                        $detalle[] = [
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $d['OtrosImp'][$i]['CodImp'],
                            $d['OtrosImp'][$i]['TasaImp'],
                            num($d['OtrosImp'][$i]['MntImp']),
                            '',
                            '',
                            '',
                            '',
                        ];
                    }
                }
            }
            $titulos = ['DTE', 'Folio', 'Emisión', 'RUT', 'Exento', 'Neto', 'IVA', 'Imp', 'Tasa', 'Monto', 'Ret parc.', 'Ret tot.', 'No reten.', 'Total'];
            $this->addTable($titulos, $detalle, ['fontsize'=>9, 'width'=>[10, 19, 20, 20, 20, 20, 20, 10, 10, 20, 20, 20, 20, 20]], false);
        } else {
            $this->Texto('No hay detalle de documentos');
            $this->Ln();
            $this->Ln();
        }
        // firma
        $this->SetFont('helvetica', 'B', 12);
        $this->Texto('IV.- Firma electrónica');
        $this->Ln();
        $this->Ln();
        $this->SetFont('helvetica', '', 9);
        $titulos = ['Fecha y hora', 'Digest'];
        $this->addTable($titulos, [[
            str_replace('T', ' ', $libro['EnvioLibro']['TmstFirma']),
            isset($libro['Signature']) ? $libro['Signature']['SignedInfo']['Reference']['DigestValue'] : 'Sin firma',
        ]]);
    }

}
