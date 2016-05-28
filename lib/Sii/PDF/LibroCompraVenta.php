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

namespace sasco\LibreDTE\Sii\PDF;

/**
 * Clase para generar el PDF de la IECV
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-03-09
 */
class LibroCompraVenta extends \sasco\LibreDTE\PDF
{

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
     * @version 2016-05-28
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
            if (!isset($libro['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'][0])) {
                $libro['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'] = [$libro['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo']];
            }
            $resumen = [];
            foreach ($libro['EnvioLibro']['ResumenPeriodo']['TotalesPeriodo'] as $total) {
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
                $detalle[] = [
                    $d['TpoDoc'],
                    $d['NroDoc'],
                    $d['FchDoc'],
                    $d['RUTDoc'],
                    !empty($d['MntExe']) ? num($d['MntExe']) : '',
                    !empty($d['MntNeto']) ? num($d['MntNeto']) : '',
                    !empty($d['MntIVA']) ? num($d['MntIVA']) : '',
                    !empty($d['OtrosImp']) ? $d['OtrosImp']['CodImp'] : '',
                    !empty($d['OtrosImp']) ? $d['OtrosImp']['TasaImp'] : '',
                    !empty($d['OtrosImp']) ? num($d['OtrosImp']['MntImp']) : '',
                    !empty($d['IVARetParcial']) ? num($d['IVARetParcial']) : '',
                    !empty($d['IVARetTotal']) ? num($d['IVARetTotal']) : '',
                    !empty($d['IVANoRetenido']) ? num($d['IVANoRetenido']) : '',
                    num($d['MntTotal']),
                ];
            }
            $titulos = ['DTE', 'Folio', 'Emisión', 'RUT', 'Exento', 'Neto', 'IVA', 'Imp', 'Tasa', 'Monto', 'Ret parc.', 'Ret tot.', 'No reten.', 'Total'];
            $this->addTable($titulos, $detalle, ['width'=>[10, 19, 20, 20, 20, 20, 20, 10, 10, 20, 20, 20, 20, 20]]);
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
            $libro['Signature']['SignedInfo']['Reference']['DigestValue']
        ]]);
    }

}
