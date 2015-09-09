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

namespace sasco\LibreDTE;

// Directorio para imágenes (no se asume nada)
define ('K_PATH_IMAGES', '');

/**
 * Clase para generar PDFs
 *
 * Los métodos se copiaron desde la clase \sowerphp\general\View_Helper_PDF
 * disponible en:
 *
 * <https://github.com/SowerPHP/extension-general/blob/master/View/Helper/PDF.php>
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2015-09-08
 */
class PDF extends \TCPDF
{

    /**
     * Constructor de la clase
     * @param o Orientación
     * @param u Unidad de medida
     * @param s Tipo de hoja
     * @param top Margen extra (al normal) para la parte de arriba del PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-09-08
     */
    public function __construct($o = 'P', $u = 'mm', $s = 'Letter', $top = 8)
    {
        parent::__construct($o, $u, $s);
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP+$top, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER+$top);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);
        $this->SetCreator('LibreDTE (http://libredte.cl)');
        $this->SetAuthor('LibreDTE (http://libredte.cl)');
        $this->setFont('helvetica');
    }

    /**
     * Método para evitar que se renderice el de TCPDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-09-08
     */
    public function Header()
    {
    }

    /**
     * Método que genera el footer del PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-09-08
     */
    public function Footer()
    {
        /*$style = ['width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [50, 50, 50]];
        $this->Line($this->getX(), $this->getY()-1, 201, $this->getY()-2, $style);
        $this->SetFont('', 'B', 6);
        $this->Texto('Documento generado utilizando LibreDTE by SASCO SpA');
        $this->Texto('Este documento es sólo una muestra, el real NO contendrá este pie de página ni línea', null, null, 'R');*/
    }

    /**
     * Agregar una tabla al PDF removiendo aquellas columnas donde no existen
     * datos en la columna para todas las filas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-18
     */
    public function addTableWithoutEmptyCols($titles, $data, $options = [], $html = false)
    {
        $cols_empty = [];
        foreach ($data as $row) {
            foreach ($row as $col => $value) {
                if (empty($value)) {
                    if (!array_key_exists($col, $cols_empty))
                        $cols_empty[$col] = 0;
                    $cols_empty[$col]++;
                }
            }
        }
        $n_rows = count($data);
        foreach ($cols_empty as $col => $rows) {
            if ($rows==$n_rows) {
                unset($titles[$col]);
                foreach ($data as &$row) {
                    unset($row[$col]);
                }
            }
        }
        $this->addTable($titles, $data, $options);
    }

    /**
     * Agregar una tabla generada a través de código HTML al PDF
     * @todo Utilizar las opciones para definir estilo de la tabla HTML
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-09-08
     */
    public function addTable($headers, $data, $options = [])
    {
        $w = (isset($options['width']) and is_array($options['width'])) ? $options['width'] : null;
        $a = (isset($options['align']) and is_array($options['align'])) ? $options['align'] : [];
        $buffer = '<table style="border:1px solid #333">';
        // Definir títulos de columnas
        $thead = isset($options['width']) and is_array($options['width']) and count($options['width']) == count($headers);
        if ($thead)
            $buffer .= '<thead>';
        $buffer .= '<tr>';
        $i = 0;
        foreach ($headers as &$col) {
            $width = ($w and $w[$i]!==null) ? (';width:'.$w[$i].'mm') : '';
            $align = isset($a[$i]) ? $a[$i] : 'center';
            $buffer .= '<th style="border-bottom:1px solid #333;text-align:'.$align.$width.'"><strong>'.strip_tags($col).'</strong></th>';
            $i++;
        }
        $buffer .= '</tr>';
        if ($thead)
            $buffer .= '</thead>';
        // Definir datos de la tabla
        if ($thead)
            $buffer .= '<tbody>';
        foreach ($data as &$row) {
            $buffer .= '<tr>';
            $i = 0;
            foreach ($row as &$col) {
                $width = ($w and $w[$i]!==null) ? (';width:'.$w[$i].'mm') : '';
                $align = isset($a[$i]) ? $a[$i] : 'center';
                $buffer .= '<td style="text-align:'.$align.$width.'">'.$col.'</td>';
                $i++;
            }
            $buffer .= '</tr>';
        }
        if ($thead)
            $buffer .= '</tbody>';
        // Finalizar tabla
        $buffer .= '</table>';
        // generar tabla en HTML
        $this->writeHTML($buffer, true, false, false, false, '');
    }

    /**
     * Agregar texto al PDF, es una variación del método Text que permite
     * definir un ancho al texto. Además recibe menos parámetros para ser
     * más simple (parámetros comunes solamente).
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-20
     */
    public function Texto($txt, $x=null, $y=null, $align='', $w=0, $link='', $border=0, $fill=false)
    {
        if ($x==null) $x = $this->GetX();
        if ($y==null) $y = $this->GetY();
        $textrendermode = $this->textrendermode;
        $textstrokewidth = $this->textstrokewidth;
        $this->setTextRenderingMode(0, true, false);
        $this->SetXY($x, $y);
        $this->Cell($w, 0, $txt, $border, 0, $align, $fill, $link);
        // restore previous rendering mode
        $this->textrendermode = $textrendermode;
        $this->textstrokewidth = $textstrokewidth;
    }

    /**
     * Método idéntico a Texto, pero en vez de utilizar Cell utiliza
     * MultiCell. La principal diferencia es que este método no permite
     * agregar un enlace y Texto si.
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-20
     */
    public function MultiTexto($txt, $x=null, $y=null, $align='', $w=0, $border=0, $fill=false)
    {
        if ($x==null) $x = $this->GetX();
        if ($y==null) $y = $this->GetY();
        $textrendermode = $this->textrendermode;
        $textstrokewidth = $this->textstrokewidth;
        $this->setTextRenderingMode(0, true, false);
        $this->SetXY($x, $y);
        $this->MultiCell($w, 0, $txt, $border, $align, $fill);
        // restore previous rendering mode
        $this->textrendermode = $textrendermode;
        $this->textstrokewidth = $textstrokewidth;
    }

}
