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
 * Clase para generar el PDF de un documento tributario electrónico (DTE)
 * chileno.
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2018-11-04
 */
class Dte extends \sasco\LibreDTE\PDF
{

    use \sasco\LibreDTE\Sii\Dte\Base\DteImpreso;

    protected $logo; ///< Datos del logo que se ubicará en el PDF (ruta, datos y/o posición)
    protected $papelContinuo = false; ///< Indica si se usa papel continuo o no (=0 papel carta, otro valor contínuo en PDF)
    protected $ecl = 5; ///< error correction level para PHP >= 7.0.0
    protected $papel_continuo_alto = 5000; ///< Alto exageradamente grande para autocálculo de alto en papel continuo
    protected $timbre_pie = true; ///< Indica si el timbre va al pie o no (va pegado al detalle)
    protected $item_detalle_posicion = 0; ///< Posición del detalle del item respecto al nombre
    protected $detalle_fuente = 10; ///< Tamaño de la fuente para el detalle en hoja carta

    protected $detalle_cols = [
        'CdgItem' => ['title'=>'Código', 'align'=>'left', 'width'=>20],
        'NmbItem' => ['title'=>'Item', 'align'=>'left', 'width'=>0],
        'IndExe' => ['title'=>'IE', 'align'=>'left', 'width'=>'7'],
        'QtyItem' => ['title'=>'Cant.', 'align'=>'right', 'width'=>15],
        'UnmdItem' => ['title'=>'Unidad', 'align'=>'left', 'width'=>22],
        'QtyRef' => ['title'=>'Cant. Ref.', 'align'=>'right', 'width'=>22],
        'PrcItem' => ['title'=>'P. unitario', 'align'=>'right', 'width'=>22],
        'DescuentoMonto' => ['title'=>'Descuento', 'align'=>'right', 'width'=>22],
        'RecargoMonto' => ['title'=>'Recargo', 'align'=>'right', 'width'=>22],
        'MontoItem' => ['title'=>'Total item', 'align'=>'right', 'width'=>22],
    ]; ///< Nombres de columnas detalle, alineación y ancho

    public static $papel = [
        0  => 'Hoja carta',
        57 => 'Papel contínuo 57mm',
        75 => 'Papel contínuo 75mm',
        80 => 'Papel contínuo 80mm',
        110 => 'Papel contínuo 110mm',
    ]; ///< Tamaño de papel que es soportado

    /**
     * Constructor de la clase
     * @param papelContinuo =true indica que el PDF se generará en formato papel continuo (si se pasa un número será el ancho del PDF en mm)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-10-06
     */
    public function __construct($papelContinuo = false)
    {
        parent::__construct();
        $this->SetTitle('Documento Tributario Electrónico (DTE) de Chile by LibreDTE');
        $this->papelContinuo = $papelContinuo === true ? 80 : $papelContinuo;
    }

    /**
     * Método que asigna la ubicación del logo de la empresa
     * @param logo URI del logo (puede ser local o en una URL)
     * @param posicion Posición respecto a datos del emisor (=0 izq, =1 arriba). Nota: parámetro válido sólo para formato hoja carta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-04
     */
    public function setLogo($logo, $posicion = 0)
    {
        $this->logo = [
            'uri' => $logo,
            'posicion' => (int)$posicion,
        ];
    }

    /**
     * Método que asigna la posición del detalle del Item respecto al nombre
     * Nota: método válido sólo para formato hoja carta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-05
     */
    public function setPosicionDetalleItem($posicion)
    {
        $this->item_detalle_posicion = (int)$posicion;
    }

    /**
     * Método que asigna el tamaño de la fuente para el detalle
     * Nota: método válido sólo para formato hoja carta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-03
     */
    public function setFuenteDetalle($fuente)
    {
        $this->detalle_fuente = (int)$fuente;
    }

    /**
     * Método que asigna el ancho e las columnas del detalle desde un arreglo
     * Nota: método válido sólo para formato hoja carta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-03
     */
    public function setAnchoColumnasDetalle(array $anchos)
    {
        foreach ($anchos as $col => $ancho) {
            if (isset($this->detalle_cols[$col]) and $ancho) {
                $this->detalle_cols[$col]['width'] = (int)$ancho;
            }
        }
    }

    /**
     * Método que asigna si el tumbre va al pie (por defecto) o va pegado al detalle
     * Nota: método válido sólo para formato hoja carta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-05
     */
    public function setTimbrePie($timbre_pie = true)
    {
        $this->timbre_pie = (bool)$timbre_pie;
    }

    /**
     * Método que agrega un documento tributario, ya sea en formato de una
     * página o papel contínuo según se haya indicado en el constructor
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-10-06
     */
    public function agregar(array $dte, $timbre = null)
    {
        $this->dte = $dte['Encabezado']['IdDoc']['TipoDTE'];
        $papel_tipo = (int)$this->papelContinuo;
        $method = 'agregar_papel_'.$papel_tipo;
        if (!method_exists($this, $method)) {
            $tipo = !empty(self::$papel[$papel_tipo]) ? self::$papel[$papel_tipo] : $papel_tipo;
            throw new \Exception('Papel de tipo "'.$tipo.'" no está disponible');
        }
        $this->$method($dte, $timbre);
    }

    /**
     * Método que agrega una página con el documento tributario
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-11-07
     */
    private function agregar_papel_0(array $dte, $timbre)
    {
        // agregar página para la factura
        $this->AddPage();
        // agregar cabecera del documento
        $y[] = $this->agregarEmisor($dte['Encabezado']['Emisor']);
        $y[] = $this->agregarFolio(
            $dte['Encabezado']['Emisor']['RUTEmisor'],
            $dte['Encabezado']['IdDoc']['TipoDTE'],
            $dte['Encabezado']['IdDoc']['Folio'],
            !empty($dte['Encabezado']['Emisor']['CmnaOrigen']) ? $dte['Encabezado']['Emisor']['CmnaOrigen'] : null
        );
        $this->setY(max($y));
        $this->Ln();
        // datos del documento
        $y = [];
        $y[] = $this->agregarDatosEmision($dte['Encabezado']['IdDoc'], !empty($dte['Encabezado']['Emisor']['CdgVendedor'])?$dte['Encabezado']['Emisor']['CdgVendedor']:null);
        $y[] = $this->agregarReceptor($dte['Encabezado']);
        $this->setY(max($y));
        $this->agregarTraslado(
            !empty($dte['Encabezado']['IdDoc']['IndTraslado']) ? $dte['Encabezado']['IdDoc']['IndTraslado'] : null,
            !empty($dte['Encabezado']['Transporte']) ? $dte['Encabezado']['Transporte'] : null
        );
        if (!empty($dte['Referencia'])) {
            $this->agregarReferencia($dte['Referencia']);
        }
        $this->agregarDetalle($dte['Detalle']);
        if (!empty($dte['DscRcgGlobal'])) {
            $this->agregarSubTotal($dte['Detalle']);
            $this->agregarDescuentosRecargos($dte['DscRcgGlobal']);
        }
        if (!empty($dte['Encabezado']['IdDoc']['MntPagos'])) {
            $this->agregarPagos($dte['Encabezado']['IdDoc']['MntPagos']);
        }
        // agregar observaciones
        $this->x_fin_datos = $this->getY();
        $this->agregarObservacion($dte['Encabezado']['IdDoc']);
        if (!$this->timbre_pie) {
            $this->Ln();
        }
        $this->x_fin_datos = $this->getY();
        $this->agregarTotales($dte['Encabezado']['Totales'], !empty($dte['Encabezado']['OtraMoneda']) ? $dte['Encabezado']['OtraMoneda'] : null);
        // agregar timbre
        $this->agregarTimbre($timbre);
        // agregar acuse de recibo y leyenda cedible
        if ($this->cedible and !in_array($dte['Encabezado']['IdDoc']['TipoDTE'], $this->sinAcuseRecibo)) {
            $this->agregarAcuseRecibo();
            $this->agregarLeyendaDestino($dte['Encabezado']['IdDoc']['TipoDTE']);
        }
    }

    /**
     * Método que agrega una página con el documento tributario
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-05
     */
    private function agregar_papel_57(array $dte, $timbre, $height = 0)
    {
        $width = 57;
        // determinar alto de la página y agregarla
        $this->AddPage('P', [$height ? $height : $this->papel_continuo_alto, $width]);
        $x = 1;
        $y = 5;
        $this->SetXY($x,$y);
        // agregar datos del documento
        $this->setFont('', '', 8);
        $this->MultiTexto(!empty($dte['Encabezado']['Emisor']['RznSoc']) ? $dte['Encabezado']['Emisor']['RznSoc'] : $dte['Encabezado']['Emisor']['RznSocEmisor'], $x, null, '', $width-2);
        $this->MultiTexto($dte['Encabezado']['Emisor']['RUTEmisor'], $x, null, '', $width-2);
        $this->MultiTexto('Giro: '.(!empty($dte['Encabezado']['Emisor']['GiroEmis']) ? $dte['Encabezado']['Emisor']['GiroEmis'] : $dte['Encabezado']['Emisor']['GiroEmisor']), $x, null, '', $width-2);
        $this->MultiTexto($dte['Encabezado']['Emisor']['DirOrigen'].', '.$dte['Encabezado']['Emisor']['CmnaOrigen'], $x, null, '', $width-2);
        if (!empty($dte['Encabezado']['Emisor']['Sucursal'])) {
            $this->MultiTexto('Sucursal: '.$dte['Encabezado']['Emisor']['Sucursal'], $x, null, '', $width-2);
        }
        if (!empty($this->casa_matriz)) {
            $this->MultiTexto('Casa matriz: '.$this->casa_matriz, $x, null, '', $width-2);
        }
        $this->MultiTexto($this->getTipo($dte['Encabezado']['IdDoc']['TipoDTE'], $dte['Encabezado']['IdDoc']['Folio']).' N° '.$dte['Encabezado']['IdDoc']['Folio'], $x, null, '', $width-2);
        $this->MultiTexto('Fecha: '.date('d/m/Y', strtotime($dte['Encabezado']['IdDoc']['FchEmis'])), $x, null, '', $width-2);
        // si no es boleta no nominativa se agregan datos receptor
        if ($dte['Encabezado']['Receptor']['RUTRecep']!='66666666-6') {
            $this->Ln();
            $this->MultiTexto('Receptor: '.$dte['Encabezado']['Receptor']['RUTRecep'], $x, null, '', $width-2);
            $this->MultiTexto($dte['Encabezado']['Receptor']['RznSocRecep'], $x, null, '', $width-2);
            if (!empty($dte['Encabezado']['Receptor']['GiroRecep'])) {
                $this->MultiTexto('Giro: '.$dte['Encabezado']['Receptor']['GiroRecep'], $x, null, '', $width-2);
            }
            if (!empty($dte['Encabezado']['Receptor']['DirRecep'])) {
                $this->MultiTexto($dte['Encabezado']['Receptor']['DirRecep'].', '.$dte['Encabezado']['Receptor']['CmnaRecep'], $x, null, '', $width-2);
            }
        }
        $this->Ln();
        // hay un sólo detalle
        if (!isset($dte['Detalle'][0])) {
            $this->MultiTexto($dte['Detalle']['NmbItem'].': $'.$this->num($dte['Detalle']['MontoItem']), $x, null, '', $width-2);
        }
        // hay más de un detalle
        else {
            foreach ($dte['Detalle'] as $d) {
                $this->MultiTexto($d['NmbItem'].': $'.$this->num($d['MontoItem']), $x, null, '', $width-2);
            }
            if (in_array($dte['Encabezado']['IdDoc']['TipoDTE'], [39, 41])) {
                $this->MultiTexto('TOTAL: $'.$this->num($dte['Encabezado']['Totales']['MntTotal']), $x, null, '', $width-2);
            }
        }
        // si no es boleta se coloca EXENTO, NETO, IVA y TOTAL si corresponde
        if (!in_array($dte['Encabezado']['IdDoc']['TipoDTE'], [39, 41])) {
            if (!empty($dte['Encabezado']['Totales']['MntExe'])) {
                $this->MultiTexto('EXENTO: $'.$this->num($dte['Encabezado']['Totales']['MntExe']), $x, null, '', $width-2);
            }
            if (!empty($dte['Encabezado']['Totales']['MntNeto'])) {
                $this->MultiTexto('NETO: $'.$this->num($dte['Encabezado']['Totales']['MntNeto']), $x, null, '', $width-2);
            }
            if (!empty($dte['Encabezado']['Totales']['IVA'])) {
                $this->MultiTexto('IVA: $'.$this->num($dte['Encabezado']['Totales']['IVA']), $x, null, '', $width-2);
            }
            $this->MultiTexto('TOTAL: $'.$this->num($dte['Encabezado']['Totales']['MntTotal']), $x, null, '', $width-2);
        }
        // agregar acuse de recibo y leyenda cedible
        if ($this->cedible and !in_array($dte['Encabezado']['IdDoc']['TipoDTE'], $this->sinAcuseRecibo)) {
            $this->agregarAcuseReciboContinuo(-1, $this->y+6, $width+2, 34);
            $this->agregarLeyendaDestinoContinuo($dte['Encabezado']['IdDoc']['TipoDTE']);
        }
        // agregar timbre
        if (!empty($dte['Encabezado']['IdDoc']['TermPagoGlosa'])) {
            $this->Ln();
            $this->MultiTexto('Observación: '.$dte['Encabezado']['IdDoc']['TermPagoGlosa']."\n\n", $x);
        }
        $this->agregarTimbre($timbre, -11, $x, $this->GetY()+6, 55, 6);
        // si el alto no se pasó, entonces es con autocálculo, se elimina esta página y se pasa el alto
        // que se logró determinar para crear la página con el alto correcto
        if (!$height) {
            $this->deletePage($this->PageNo());
            $this->agregar_papel_57($dte, $timbre, $this->getY()+30);
        }
    }

    /**
     * Método que agrega una página con el documento tributario en papel
     * contínuo de 75mm
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-10-06
     */
    private function agregar_papel_75(array $dte, $timbre)
    {
        $this->agregar_papel_80($dte, $timbre, 75);
    }

    /**
     * Método que agrega una página con el documento tributario en papel
     * contínuo de 80mm
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @param width Ancho del papel contínuo en mm (es parámetro porque se usa el mismo método para 75mm)
     * @author Pablo Reyes (https://github.com/pabloxp)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-10-06
     */
    private function agregar_papel_80(array $dte, $timbre, $width = 80, $height = 0)
    {
        // si hay logo asignado se usa centrado
        if (!empty($this->logo)) {
            $this->logo['posicion'] = 'C';
        }
        // determinar alto de la página y agregarla
        $x_start = 1;
        $y_start = 1;
        $offset = 14;
        // determinar alto de la página y agregarla
        $this->AddPage('P', [$height ? $height : $this->papel_continuo_alto, $width]);
        // agregar cabecera del documento
        $y = $this->agregarFolio(
            $dte['Encabezado']['Emisor']['RUTEmisor'],
            $dte['Encabezado']['IdDoc']['TipoDTE'],
            $dte['Encabezado']['IdDoc']['Folio'],
            $dte['Encabezado']['Emisor']['CmnaOrigen'],
            $x_start, $y_start, $width-($x_start*4), 10,
            [0,0,0]
        );
        $y = $this->agregarEmisor($dte['Encabezado']['Emisor'], $x_start, $y+2, $width-($x_start*45), 8, 9, [0,0,0]);
        // datos del documento
        $this->SetY($y);
        $this->Ln();
        $this->setFont('', '', 8);
        $this->agregarDatosEmision($dte['Encabezado']['IdDoc'], !empty($dte['Encabezado']['Emisor']['CdgVendedor'])?$dte['Encabezado']['Emisor']['CdgVendedor']:null, $x_start, $offset, false);
        $this->agregarReceptor($dte['Encabezado'], $x_start, $offset);
        $this->agregarTraslado(
            !empty($dte['Encabezado']['IdDoc']['IndTraslado']) ? $dte['Encabezado']['IdDoc']['IndTraslado'] : null,
            !empty($dte['Encabezado']['Transporte']) ? $dte['Encabezado']['Transporte'] : null,
            $x_start, $offset
        );
        if (!empty($dte['Referencia'])) {
            $this->agregarReferencia($dte['Referencia'], $x_start, $offset);
        }
        $this->Ln();
        $this->agregarDetalleContinuo($dte['Detalle']);
        if (!empty($dte['DscRcgGlobal'])) {
            $this->Ln();
            $this->Ln();
            $this->agregarSubTotal($dte['Detalle'], $x_start);
            $this->agregarDescuentosRecargos($dte['DscRcgGlobal'], $x_start);
        }
        if (!empty($dte['Encabezado']['IdDoc']['MntPagos'])) {
            $this->Ln();
            $this->Ln();
            $this->agregarPagos($dte['Encabezado']['IdDoc']['MntPagos'], $x_start);
        }
        $this->agregarTotales($dte['Encabezado']['Totales'], !empty($dte['Encabezado']['OtraMoneda']) ? $dte['Encabezado']['OtraMoneda'] : null, $this->y+6, 23, 17);
        // agregar acuse de recibo y leyenda cedible
        if ($this->cedible and !in_array($dte['Encabezado']['IdDoc']['TipoDTE'], $this->sinAcuseRecibo)) {
            $this->agregarAcuseReciboContinuo(3, $this->y+6, 68, 34);
            $this->agregarLeyendaDestinoContinuo($dte['Encabezado']['IdDoc']['TipoDTE']);
        }
        // agregar timbre
        $y = $this->agregarObservacion($dte['Encabezado']['IdDoc'], $x_start, $this->y+6);
        $this->agregarTimbre($timbre, -10, $x_start, $y+6, 70, 6);
        // si el alto no se pasó, entonces es con autocálculo, se elimina esta página y se pasa el alto
        // que se logró determinar para crear la página con el alto correcto
        if (!$height) {
            $this->deletePage($this->PageNo());
            $this->agregar_papel_80($dte, $timbre, $width, $this->getY()+30);
        }
    }

    /**
     * Método que agrega una página con el documento tributario en papel
     * contínuo de 110mm
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-10-06
     */
    private function agregar_papel_110(array $dte, $timbre, $height = 0)
    {
        $width = 110;
        if (!empty($this->logo)) {
            $this->logo['posicion'] = 1;
        }
        // determinar alto de la página y agregarla
        $x_start = 1;
        $y_start = 1;
        $offset = 14;
        // determinar alto de la página y agregarla
        $this->AddPage('P', [$height ? $height : $this->papel_continuo_alto, $width]);
        // agregar cabecera del documento
        $y[] = $this->agregarFolio(
            $dte['Encabezado']['Emisor']['RUTEmisor'],
            $dte['Encabezado']['IdDoc']['TipoDTE'],
            $dte['Encabezado']['IdDoc']['Folio'],
            !empty($dte['Encabezado']['Emisor']['CmnaOrigen']) ? $dte['Encabezado']['Emisor']['CmnaOrigen'] : null,
            63,
            2,
            45,
            9,
            [0,0,0]
        );
        $y[] = $this->agregarEmisor($dte['Encabezado']['Emisor'], 1, 2, 20, 30, 9, [0,0,0], $y[0]);
        $this->SetY(max($y));
        $this->Ln();
        // datos del documento
        $this->setFont('', '', 8);
        $this->agregarDatosEmision($dte['Encabezado']['IdDoc'], !empty($dte['Encabezado']['Emisor']['CdgVendedor'])?$dte['Encabezado']['Emisor']['CdgVendedor']:null, $x_start, $offset, false);
        $this->agregarReceptor($dte['Encabezado'], $x_start, $offset);
        $this->agregarTraslado(
            !empty($dte['Encabezado']['IdDoc']['IndTraslado']) ? $dte['Encabezado']['IdDoc']['IndTraslado'] : null,
            !empty($dte['Encabezado']['Transporte']) ? $dte['Encabezado']['Transporte'] : null,
            $x_start, $offset
        );
        if (!empty($dte['Referencia'])) {
            $this->agregarReferencia($dte['Referencia'], $x_start, $offset);
        }
        $this->Ln();
        $this->agregarDetalleContinuo($dte['Detalle'], 3, [1, 53, 73, 83], true);
        if (!empty($dte['DscRcgGlobal'])) {
            $this->Ln();
            $this->Ln();
            $this->agregarSubTotal($dte['Detalle'], $x_start);
            $this->agregarDescuentosRecargos($dte['DscRcgGlobal'], $x_start);
        }
        if (!empty($dte['Encabezado']['IdDoc']['MntPagos'])) {
            $this->Ln();
            $this->Ln();
            $this->agregarPagos($dte['Encabezado']['IdDoc']['MntPagos'], $x_start);
        }
        $this->agregarTotales($dte['Encabezado']['Totales'], !empty($dte['Encabezado']['OtraMoneda']) ? $dte['Encabezado']['OtraMoneda'] : null, $this->y+6, 61, 17);
        // agregar observaciones
        $y = $this->agregarObservacion($dte['Encabezado']['IdDoc'], $x_start, $this->y+6);
        // agregar timbre
        $this->agregarTimbre($timbre, 2, 2, $y+6, 60, 6, 'S');
        // agregar acuse de recibo y leyenda cedible
        if ($this->cedible and !in_array($dte['Encabezado']['IdDoc']['TipoDTE'], $this->sinAcuseRecibo)) {
            $this->agregarAcuseRecibo(63, $y+6, 45, 40, 15);
            $this->setFont('', 'B', 8);
            $this->Texto('CEDIBLE'.($dte['Encabezado']['IdDoc']['TipoDTE']==52?' CON SU FACTURA':''), $x_start, $this->y+1, 'L');
        }
        // si el alto no se pasó, entonces es con autocálculo, se elimina esta página y se pasa el alto
        // que se logró determinar para crear la página con el alto correcto
        if (!$height) {
            $this->deletePage($this->PageNo());
            $this->agregar_papel_110($dte, $timbre, $this->getY()+30);
        }
    }

    /**
     * Método que agrega los datos de la empresa
     * Orden de los datos:
     *  - Razón social del emisor
     *  - Giro del emisor (sin abreviar)
     *  - Dirección casa central del emisor
     *  - Dirección sucursales
     * @param emisor Arreglo con los datos del emisor (tag Emisor del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @param y Posición vertical de inicio en el PDF
     * @param w Ancho de la información del emisor
     * @param w_img Ancho máximo de la imagen
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-11-22
     */
    protected function agregarEmisor(array $emisor, $x = 10, $y = 15, $w = 75, $w_img = 30, $font_size = null, array $color = null, $h_folio = null, $w_all = null)
    {
        $agregarDatosEmisor = true;
        // logo del documento
        if (isset($this->logo)) {
            // logo centrado (papel continuo)
            if (!empty($this->logo['posicion']) and $this->logo['posicion'] == 'C') {
                $logo_w = null;
                $logo_y = null;
                $logo_position = 'C';
                $logo_next_pointer = 'N';
            }
            // logo a la derecha (posicion=0) o arriba (posicion=1)
            else if (empty($this->logo['posicion']) or $this->logo['posicion'] == 1) {
                $logo_w = !$this->logo['posicion'] ? $w_img : null;
                $logo_y = $this->logo['posicion'] ? $w_img/2 : null;
                $logo_position = '';
                $logo_next_pointer = 'T';
            }
            // logo completo, reemplazando datos del emisor (posicion=2)
            else {
                $logo_w = null;
                $logo_y = $w_img;
                $logo_position = '';
                $logo_next_pointer = 'T';
                $agregarDatosEmisor = false;
            }
            $this->Image(
                $this->logo['uri'],
                $x,
                $y,
                $logo_w,
                $logo_y,
                'PNG',
                (isset($emisor['url'])?$emisor['url']:''),
                $logo_next_pointer,
                2,
                300,
                $logo_position
            );
            if (!empty($this->logo['posicion']) and $this->logo['posicion'] == 'C') {
                $w += 40;
            } else {
                if ($this->logo['posicion']) {
                    $this->SetY($this->y + ($w_img/2));
                    $w += 40;
                } else {
                    $x = $this->x+3;
                }
            }
        } else {
            $this->y = $y-2;
            $w += 40;
        }
        // agregar datos del emisor
        if ($agregarDatosEmisor) {
            $this->setFont('', 'B', $font_size ? $font_size : 14);
            $this->SetTextColorArray($color===null?[32, 92, 144]:$color);
            $this->MultiTexto(!empty($emisor['RznSoc']) ? $emisor['RznSoc'] : $emisor['RznSocEmisor'], $x, $this->y+2, 'L', ($h_folio and $h_folio < $this->getY()) ? $w_all : $w);
            $this->setFont('', 'B', $font_size ? $font_size : 9);
            $this->SetTextColorArray([0,0,0]);
            $this->MultiTexto(!empty($emisor['GiroEmis']) ? $emisor['GiroEmis'] : $emisor['GiroEmisor'], $x, $this->y, 'L', ($h_folio and $h_folio < $this->getY()) ? $w_all : $w);
            $comuna = !empty($emisor['CmnaOrigen']) ? $emisor['CmnaOrigen'] : null;
            $ciudad = !empty($emisor['CiudadOrigen']) ? $emisor['CiudadOrigen'] : \sasco\LibreDTE\Chile::getCiudad($comuna);
            $this->MultiTexto($emisor['DirOrigen'].($comuna?(', '.$comuna):'').($ciudad?(', '.$ciudad):''), $x, $this->y, 'L', ($h_folio and $h_folio < $this->getY()) ? $w_all : $w);
            if (!empty($emisor['Sucursal'])) {
                $this->MultiTexto('Sucursal: '.$emisor['Sucursal'], $x, $this->y, 'L', ($h_folio and $h_folio < $this->getY()) ? $w_all : $w);
            }
            if (!empty($this->casa_matriz)) {
                $this->MultiTexto('Casa matriz: '.$this->casa_matriz, $x, $this->y, 'L', ($h_folio and $h_folio < $this->getY()) ? $w_all : $w);
            }
            $contacto = [];
            if (!empty($emisor['Telefono'])) {
                if (!is_array($emisor['Telefono'])) {
                    $emisor['Telefono'] = [$emisor['Telefono']];
                }
                foreach ($emisor['Telefono'] as $t) {
                    $contacto[] = $t;
                }
            }
            if (!empty($emisor['CorreoEmisor'])) {
                $contacto[] = $emisor['CorreoEmisor'];
            }
            if ($contacto) {
                $this->MultiTexto(implode(' / ', $contacto), $x, $this->y, 'L', ($h_folio and $h_folio < $this->getY()) ? $w_all : $w);
            }
        }
        return $this->y;
    }

    /**
     * Método que agrega el recuadro con el folio
     * Recuadro:
     *  - Tamaño mínimo 1.5x5.5 cms
     *  - En lado derecho (negro o rojo)
     *  - Enmarcado por una línea de entre 0.5 y 1 mm de espesor
     *  - Tamaño máximo 4x8 cms
     *  - Letras tamaño 10 o superior en mayúsculas y negritas
     *  - Datos del recuadro: RUT emisor, nombre de documento en 2 líneas,
     *    folio.
     *  - Bajo el recuadro indicar la Dirección regional o Unidad del SII a la
     *    que pertenece el emisor
     * @param rut RUT del emisor
     * @param tipo Código o glosa del tipo de documento
     * @param sucursal_sii Código o glosa de la sucursal del SII del Emisor
     * @param x Posición horizontal de inicio en el PDF
     * @param y Posición vertical de inicio en el PDF
     * @param w Ancho de la información del emisor
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-05
     */
    protected function agregarFolio($rut, $tipo, $folio, $sucursal_sii = null, $x = 130, $y = 15, $w = 70, $font_size = null, array $color = null)
    {
        if ($color===null) {
            $color = $tipo ? ($tipo==52 ? [0,172,140] : [255,0,0]) : [0,0,0];
        }
        $this->SetTextColorArray($color);
        // colocar rut emisor, glosa documento y folio
        list($rut, $dv) = explode('-', $rut);
        $this->setFont ('', 'B', $font_size ? $font_size : 15);
        $this->MultiTexto('R.U.T.: '.$this->num($rut).'-'.$dv, $x, $y+4, 'C', $w);
        $this->setFont('', 'B', $font_size ? $font_size : 12);
        $this->MultiTexto($this->getTipo($tipo, $folio), $x, null, 'C', $w);
        $this->setFont('', 'B', $font_size ? $font_size : 15);
        $this->MultiTexto('N° '.$folio, $x, null, 'C', $w);
        // dibujar rectángulo rojo
        $this->Rect($x, $y, $w, round($this->getY()-$y+3), 'D', ['all' => ['width' => 0.5, 'color' => $color]]);
        // colocar unidad del SII
        $this->setFont('', 'B', $font_size ? $font_size : 10);
        if ($tipo) {
            $this->Texto('S.I.I. - '.\sasco\LibreDTE\Sii::getDireccionRegional($sucursal_sii), $x, $this->getY()+4, 'C', $w);
        }
        $this->SetTextColorArray([0,0,0]);
        $this->Ln();
        return $this->y;
    }

    /**
     * Método que agrega los datos de la emisión del DTE que no son los dato del
     * receptor
     * @param IdDoc Información general del documento
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-06-15
     */
    protected function agregarDatosEmision($IdDoc, $CdgVendedor, $x = 10, $offset = 22, $mostrar_dia = true)
    {
        // si es hoja carta
        if ($x==10) {
            $y = $this->GetY();
            // fecha emisión
            $this->setFont('', 'B', null);
            $this->MultiTexto($this->date($IdDoc['FchEmis'], $mostrar_dia), $x, null, 'R');
            $this->setFont('', '', null);
            // período facturación
            if (!empty($IdDoc['PeriodoDesde']) and !empty($IdDoc['PeriodoHasta'])) {
                $this->MultiTexto('Período del '.date('d/m/y', strtotime($IdDoc['PeriodoDesde'])).' al '.date('d/m/y', strtotime($IdDoc['PeriodoHasta'])), $x, null, 'R');
            }
            // pago anticicado
            if (!empty($IdDoc['FchCancel'])) {
                $this->MultiTexto('Pagado el '.$this->date($IdDoc['FchCancel'], false), $x, null, 'R');
            }
            // fecha vencimiento
            if (!empty($IdDoc['FchVenc'])) {
                $this->MultiTexto('Vence el '.$this->date($IdDoc['FchVenc'], false), $x, null, 'R');
            }
            // forma de pago nacional
            if (!empty($IdDoc['FmaPago'])) {
                $this->MultiTexto('Venta: '.strtolower($this->formas_pago[$IdDoc['FmaPago']]), $x, null, 'R');
            }
            // forma de pago exportación
            if (!empty($IdDoc['FmaPagExp'])) {
                $this->MultiTexto('Venta: '.strtolower($this->formas_pago_exportacion[$IdDoc['FmaPagExp']]), $x, null, 'R');
            }
            // vendedor
            if (!empty($CdgVendedor)) {
                $this->MultiTexto('Vendedor: '.$CdgVendedor, $x, null, 'R');
            }
            $y_end = $this->GetY();
            $this->SetY($y);
        }
        // papel contínuo
        else {
            // fecha de emisión
            $this->setFont('', 'B', null);
            $this->Texto('Emisión', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($this->date($IdDoc['FchEmis'], $mostrar_dia), $x+$offset+2);
            // forma de pago nacional
            if (!empty($IdDoc['FmaPago'])) {
                $this->setFont('', 'B', null);
                $this->Texto('Venta', $x);
                $this->Texto(':', $x+$offset);
                $this->setFont('', '', null);
                $this->MultiTexto($this->formas_pago[$IdDoc['FmaPago']], $x+$offset+2);
            }
            // forma de pago exportación
            if (!empty($IdDoc['FmaPagExp'])) {
                $this->setFont('', 'B', null);
                $this->Texto('Venta', $x);
                $this->Texto(':', $x+$offset);
                $this->setFont('', '', null);
                $this->MultiTexto($this->formas_pago_exportacion[$IdDoc['FmaPagExp']], $x+$offset+2);
            }
            // pago anticicado
            if (!empty($IdDoc['FchCancel'])) {
                $this->setFont('', 'B', null);
                $this->Texto('Pagado el', $x);
                $this->Texto(':', $x+$offset);
                $this->setFont('', '', null);
                $this->MultiTexto($this->date($IdDoc['FchCancel'], $mostrar_dia), $x+$offset+2);
            }
            // fecha vencimiento
            if (!empty($IdDoc['FchVenc'])) {
                $this->setFont('', 'B', null);
                $this->Texto('Vence el', $x);
                $this->Texto(':', $x+$offset);
                $this->setFont('', '', null);
                $this->MultiTexto($this->date($IdDoc['FchVenc'], $mostrar_dia), $x+$offset+2);
            }
            $y_end = $this->GetY();
        }
        return $y_end;
    }

    /**
     * Método que agrega los datos del receptor
     * @param receptor Arreglo con los datos del receptor (tag Receptor del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-10-06
     */
    protected function agregarReceptor(array $Encabezado, $x = 10, $offset = 22)
    {
        $receptor = $Encabezado['Receptor'];
        if (!empty($receptor['RUTRecep']) and $receptor['RUTRecep']!='66666666-6') {
            list($rut, $dv) = explode('-', $receptor['RUTRecep']);
            $this->setFont('', 'B', null);
            $this->Texto(in_array($this->dte, [39, 41]) ? 'R.U.N.' : 'R.U.T.', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($this->num($rut).'-'.$dv, $x+$offset+2);
        }
        if (!empty($receptor['RznSocRecep'])) {
            $this->setFont('', 'B', null);
            $this->Texto(in_array($this->dte, [39, 41]) ? 'Nombre' : ($x==10?'Razón social':'Razón soc.'), $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($receptor['RznSocRecep'], $x+$offset+2, null, '', $x==10?105:0);
        }
        if (!empty($receptor['GiroRecep'])) {
            $this->setFont('', 'B', null);
            $this->Texto('Giro', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($receptor['GiroRecep'], $x+$offset+2);
        }
        if (!empty($receptor['DirRecep'])) {
            $this->setFont('', 'B', null);
            $this->Texto('Dirección', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $ciudad = !empty($receptor['CiudadRecep']) ? $receptor['CiudadRecep'] : (
                !empty($receptor['CmnaRecep']) ? \sasco\LibreDTE\Chile::getCiudad($receptor['CmnaRecep']) : ''
            );
            $this->MultiTexto($receptor['DirRecep'].(!empty($receptor['CmnaRecep'])?(', '.$receptor['CmnaRecep']):'').($ciudad?(', '.$ciudad):''), $x+$offset+2);
        }
        if (!empty($receptor['Extranjero']['Nacionalidad'])) {
            $this->setFont('', 'B', null);
            $this->Texto('Nacionalidad', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto(\sasco\LibreDTE\Sii\Aduana::getNacionalidad($receptor['Extranjero']['Nacionalidad']), $x+$offset+2);
        }
        if (!empty($receptor['Extranjero']['NumId'])) {
            $this->setFont('', 'B', null);
            $this->Texto('N° ID extranj.', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($receptor['Extranjero']['NumId'], $x+$offset+2);
        }
        $contacto = [];
        if (!empty($receptor['Contacto']))
            $contacto[] = $receptor['Contacto'];
        if (!empty($receptor['CorreoRecep']))
            $contacto[] = $receptor['CorreoRecep'];
        if (!empty($contacto)) {
            $this->setFont('', 'B', null);
            $this->Texto('Contacto', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto(implode(' / ', $contacto), $x+$offset+2);
        }
        if (!empty($Encabezado['RUTSolicita'])) {
            list($rut, $dv) = explode('-', $Encabezado['RUTSolicita']);
            $this->setFont('', 'B', null);
            $this->Texto('RUT solicita', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($this->num($rut).'-'.$dv, $x+$offset+2);
        }
        if (!empty($receptor['CdgIntRecep'])) {
            $this->setFont('', 'B', null);
            $this->Texto('Cód. recep.', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($receptor['CdgIntRecep'], $x+$offset+2, null, '', $x==10?105:0);
        }
        return $this->GetY();
    }

    /**
     * Método que agrega los datos del traslado
     * @param IndTraslado
     * @param Transporte
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-03
     */
    protected function agregarTraslado($IndTraslado, array $Transporte = null, $x = 10, $offset = 22)
    {
        // agregar tipo de traslado
        if ($IndTraslado) {
            $this->setFont('', 'B', null);
            $this->Texto('Tipo oper.', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($this->traslados[$IndTraslado], $x+$offset+2);
        }
        // agregar información de transporte
        if ($Transporte) {
            $transporte = '';
            if (!empty($Transporte['DirDest']) and !empty($Transporte['CmnaDest'])) {
                $transporte .= 'a '.$Transporte['DirDest'].', '.$Transporte['CmnaDest'];
            }
            if (!empty($Transporte['RUTTrans']))
                $transporte .= ' por '.$Transporte['RUTTrans'];
            if (!empty($Transporte['Patente']))
                $transporte .= ' en vehículo '.$Transporte['Patente'];
            if (isset($Transporte['Chofer']) and is_array($Transporte['Chofer'])) {
                if (!empty($Transporte['Chofer']['NombreChofer'])) {
                    $transporte .= ' con chofer '.$Transporte['Chofer']['NombreChofer'];
                }
                if (!empty($Transporte['Chofer']['RUTChofer'])) {
                    $transporte .= ' ('.$Transporte['Chofer']['RUTChofer'].')';
                }
            }
            if ($transporte) {
                $this->setFont('', 'B', null);
                $this->Texto('Traslado', $x);
                $this->Texto(':', $x+$offset);
                $this->setFont('', '', null);
                $this->MultiTexto(ucfirst(trim($transporte)), $x+$offset+2);
            }
        }
        // agregar información de aduana
        if (!empty($Transporte['Aduana']) and is_array($Transporte['Aduana'])) {
            $col = 0;
            foreach ($Transporte['Aduana'] as $tag => $codigo) {
                if ($codigo===false) {
                    continue;
                }
                $glosa = \sasco\LibreDTE\Sii\Aduana::getGlosa($tag);
                $valor = \sasco\LibreDTE\Sii\Aduana::getValor($tag, $codigo);
                if ($glosa!==false and $valor!==false) {
                    if ($tag=='TipoBultos' and $col) {
                        $col = abs($col-110);
                        $this->Ln();
                    }
                    /*if (in_array($tag, ['CodClauVenta', 'CodViaTransp', 'CodPtoEmbarque', 'Tara', 'MntFlete', 'CodPaisRecep']) and $col) {
                        $col = 0;
		    }*/
                    $this->setFont('', 'B', null);
                    $this->Texto($glosa, $x+$col);
                    $this->Texto(':', $x+$offset+$col);
                    $this->setFont('', '', null);
                    $this->Texto($valor, $x+$offset+2+$col);
                    if ($tag=='TipoBultos') {
                        $col = abs($col-110);
                    }
                    if ($col) {
                        $this->Ln();
                    }
                    $col = abs($col-110);
                }
            }
            if ($col) {
                $this->Ln();
            }
        }
    }

    /**
     * Método que agrega las referencias del documento
     * @param referencias Arreglo con las referencias del documento (tag Referencia del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-25
     */
    protected function agregarReferencia($referencias, $x = 10, $offset = 22)
    {
        if (!isset($referencias[0]))
            $referencias = [$referencias];
        foreach($referencias as $r) {
            $texto = $r['NroLinRef'].' - ';
            if (!empty($r['TpoDocRef'])) {
                $texto .= $this->getTipo($r['TpoDocRef']).' ';
            }
            if (!empty($r['FolioRef'])) {
                if (is_numeric($r['FolioRef'])) {
                    $texto .= ' N° '.$r['FolioRef'].' ';
                } else {
                    $texto .= ' '.$r['FolioRef'].' ';
                }
            }
            if (!empty($r['FchRef'])) {
                $texto .= 'del '.date('d/m/Y', strtotime($r['FchRef']));
            }
            if (isset($r['RazonRef']) and $r['RazonRef']!==false) {
                $texto = $texto.': '.$r['RazonRef'];
            }
            $this->setFont('', 'B', null);
            $this->Texto('Referencia', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($texto, $x+$offset+2);
        }
    }

    /**
     * Método que agrega el detalle del documento
     * @param detalle Arreglo con el detalle del documento (tag Detalle del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @param y Posición vertical de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-09
     */
    protected function agregarDetalle($detalle, $x = 10, $html = true)
    {
        if (!isset($detalle[0])) {
            $detalle = [$detalle];
        }
        $this->setFont('', '', $this->detalle_fuente);
        // titulos
        $titulos = [];
        $titulos_keys = array_keys($this->detalle_cols);
        foreach ($this->detalle_cols as $key => $info) {
            $titulos[$key] = $info['title'];
        }
        // normalizar cada detalle
        $dte_exento = in_array($this->dte, [34, 110, 111, 112]);
        foreach ($detalle as &$item) {
            // quitar columnas
            foreach ($item as $col => $valor) {
                if ($col=='DscItem' and !empty($item['DscItem'])) {
                    $item['NmbItem'] .= !$this->item_detalle_posicion ? ($html?'<br/>':"\n") : ': ';
                    if ($html) {
                        $item['NmbItem'] .= '<span style="font-size:0.7em">'.$item['DscItem'].'</span>';
                    } else {
                        $item['NmbItem'] .= $item['DscItem'];
                    }
                }
                if ($col=='Subcantidad' and !empty($item['Subcantidad'])) {
                    //$item['NmbItem'] .= $html ? '<br/>' : "\n";
                    if (!isset($item['Subcantidad'][0])) {
                        $item['Subcantidad'] = [$item['Subcantidad']];
                    }
                    foreach ($item['Subcantidad'] as $Subcantidad) {
                        if ($html) {
                            $item['NmbItem'] .= '<br/><span style="font-size:0.7em">  - Subcantidad: '.$Subcantidad['SubQty'].' '.$Subcantidad['SubCod'].'</span>';
                        } else {
                            $item['NmbItem'] .= "\n".'  - Subcantidad: '.$Subcantidad['SubQty'].' '.$Subcantidad['SubCod'];
                        }
                    }
                }
                if ($col=='UnmdRef' and !empty($item['UnmdRef']) and !empty($item['QtyRef'])) {
                    $item['QtyRef'] .= ' '.$item['UnmdRef'];
                }
                if ($col=='DescuentoPct' and !empty($item['DescuentoPct'])) {
                    $item['DescuentoMonto'] = $item['DescuentoPct'].'%';
                }
                if ($col=='RecargoPct' and !empty($item['RecargoPct'])) {
                    $item['RecargoMonto'] = $item['RecargoPct'].'%';
                }
                if (!in_array($col, $titulos_keys) or ($dte_exento and $col=='IndExe')) {
                    unset($item[$col]);
                }
            }
            // ajustes a IndExe
            if (isset($item['IndExe'])) {
                if ($item['IndExe']==1) {
                    $item['IndExe'] = 'EX';
                } else if ($item['IndExe']==2) {
                    $item['IndExe'] = 'NF';
                }
            }
            // agregar todas las columnas que se podrían imprimir en la tabla
            $item_default = [];
            foreach ($this->detalle_cols as $key => $info) {
                $item_default[$key] = false;
            }
            $item = array_merge($item_default, $item);
            // si hay código de item se extrae su valor
            if (!empty($item['CdgItem']['VlrCodigo'])){
                $item['CdgItem'] = $item['CdgItem']['VlrCodigo'];
            }
            // dar formato a números
            foreach (['QtyItem', 'PrcItem', 'DescuentoMonto', 'RecargoMonto', 'MontoItem'] as $col) {
                if ($item[$col]) {
                    $item[$col] = is_numeric($item[$col]) ? $this->num($item[$col]) : $item[$col];
                }
            }
        }
        // opciones
        $options = ['align'=>[]];
        $i = 0;
        foreach ($this->detalle_cols as $info) {
            if (isset($info['width'])) {
                $options['width'][$i] = $info['width'];
            }
            $options['align'][$i] = $info['align'];
            $i++;
        }
        // agregar tabla de detalle
        $this->Ln();
        $this->SetX($x);
        $this->addTableWithoutEmptyCols($titulos, $detalle, $options);
    }

    /**
     * Método que agrega el detalle del documento
     * @param detalle Arreglo con el detalle del documento (tag Detalle del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @param y Posición vertical de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @author Pablo Reyes (https://github.com/pabloxp)
     * @version 2019-10-06
     */
    protected function agregarDetalleContinuo($detalle, $x = 3, array $offsets = [], $descripcion = false)
    {
        if (!$offsets) {
            $offsets = [1, 15, 35, 45];
        }
        $this->SetY($this->getY()+1);
        $p1x = $x;
        $p1y = $this->y;
        $p2x = $this->getPageWidth() - 2;
        $p2y = $p1y;  // Use same y for a straight line
        $style = array('width' => 0.2,'color' => array(0, 0, 0));
        $this->Line($p1x, $p1y, $p2x, $p2y, $style);
        $this->Texto($this->detalle_cols['NmbItem']['title'], $x+$offsets[0], $this->y, ucfirst($this->detalle_cols['NmbItem']['align'][0]), $this->detalle_cols['NmbItem']['width']);
        $this->Texto($this->detalle_cols['PrcItem']['title'], $x+$offsets[1], $this->y, ucfirst($this->detalle_cols['PrcItem']['align'][0]), $this->detalle_cols['PrcItem']['width']);
        $this->Texto($this->detalle_cols['QtyItem']['title'], $x+$offsets[2], $this->y, ucfirst($this->detalle_cols['QtyItem']['align'][0]), $this->detalle_cols['QtyItem']['width']);
        $this->Texto($this->detalle_cols['MontoItem']['title'], $x+$offsets[3], $this->y, ucfirst($this->detalle_cols['MontoItem']['align'][0]), $this->detalle_cols['MontoItem']['width']);
        $this->Line($p1x, $p1y+4, $p2x, $p2y+4, $style);
        if (!isset($detalle[0])) {
            $detalle = [$detalle];
        }
        $this->SetY($this->getY()+2);
        foreach($detalle as  &$d) {
            $item = $d['NmbItem'];
            if ($descripcion and !empty($d['DscItem'])) {
                $item .= ': '.$d['DscItem'];
            }
            $this->MultiTexto($item, $x+$offsets[0], $this->y+4, ucfirst($this->detalle_cols['NmbItem']['align'][0]), $this->detalle_cols['NmbItem']['width']);
            $this->Texto(number_format($d['PrcItem'],0,',','.'), $x+$offsets[1], $this->y, ucfirst($this->detalle_cols['PrcItem']['align'][0]), $this->detalle_cols['PrcItem']['width']);
            $this->Texto($this->num($d['QtyItem']), $x+$offsets[2], $this->y, ucfirst($this->detalle_cols['QtyItem']['align'][0]), $this->detalle_cols['QtyItem']['width']);
            $this->Texto($this->num($d['MontoItem']), $x+$offsets[3], $this->y, ucfirst($this->detalle_cols['MontoItem']['align'][0]), $this->detalle_cols['MontoItem']['width']);
        }
        $this->Line($p1x, $this->y+4, $p2x, $this->y+4, $style);
    }

    /**
     * Método que agrega el subtotal del DTE
     * @param detalle Arreglo con los detalles del documentos para poder
     * calcular subtotal
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-17
     */
    protected function agregarSubTotal(array $detalle, $x = 10) {
        $subtotal = 0;
        if (!isset($detalle[0])) {
            $detalle = [$detalle];
        }
        foreach($detalle as  &$d) {
            if (!empty($d['MontoItem'])) {
                $subtotal += $d['MontoItem'];
            }
        }
        if ($this->papelContinuo) {
            $this->Texto('Subtotal: '.$this->num($subtotal), $x);
        } else {
            $this->Texto('Subtotal:', 77, null, 'R', 100);
            $this->Texto($this->num($subtotal), 177, null, 'R', 22);
        }
        $this->Ln();
    }

    /**
     * Método que agrega los descuentos y/o recargos globales del documento
     * @param descuentosRecargos Arreglo con los descuentos y/o recargos del documento (tag DscRcgGlobal del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-05-29
     */
    protected function agregarDescuentosRecargos(array $descuentosRecargos, $x = 10)
    {
        if (!isset($descuentosRecargos[0])) {
            $descuentosRecargos = [$descuentosRecargos];
        }
        foreach($descuentosRecargos as $dr) {
            $tipo = $dr['TpoMov']=='D' ? 'Descuento' : 'Recargo';
            if (!empty($dr['IndExeDR'])) {
                $tipo .= ' EX';
            }
            $valor = $dr['TpoValor']=='%' ? $dr['ValorDR'].'%' : $this->num($dr['ValorDR']);
            if ($this->papelContinuo) {
                $this->Texto($tipo.' global: '.$valor.(!empty($dr['GlosaDR'])?(' ('.$dr['GlosaDR'].')'):''), $x);
            } else {
                $this->Texto($tipo.(!empty($dr['GlosaDR'])?(' ('.$dr['GlosaDR'].')'):'').':', 77, null, 'R', 100);
                $this->Texto($valor, 177, null, 'R', 22);
            }
            $this->Ln();
        }
    }

    /**
     * Método que agrega los pagos del documento
     * @param pagos Arreglo con los pagos del documento
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-24
     */
    protected function agregarPagos(array $pagos, $x = 10)
    {
        if (!isset($pagos[0]))
            $pagos = [$pagos];
        $this->Texto('Pago(s) programado(s):', $x);
        $this->Ln();
        foreach($pagos as $p) {
            $this->Texto('  - '.$this->date($p['FchPago'], false).': $'.$this->num($p['MntPago']).'.-'.(!empty($p['GlosaPagos'])?(' ('.$p['GlosaPagos'].')'):''), $x);
            $this->Ln();
        }
    }

    /**
     * Método que agrega los totales del documento
     * @param totales Arreglo con los totales (tag Totales del XML)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-05
     */
    protected function agregarTotales(array $totales, $otra_moneda, $y = 190, $x = 145, $offset = 25)
    {
        $y = (!$this->papelContinuo and !$this->timbre_pie) ? $this->x_fin_datos : $y;
        // normalizar totales
        $totales = array_merge([
            'TpoMoneda' => false,
            'MntNeto' => false,
            'MntExe' => false,
            'TasaIVA' => false,
            'IVA' => false,
            'IVANoRet' => false,
            'CredEC' => false,
            'MntTotal' => false,
            'MontoNF' => false,
            'MontoPeriodo' => false,
            'SaldoAnterior' => false,
            'VlrPagar' => false,
        ], $totales);
        // glosas
        $glosas = [
            'TpoMoneda' => 'Moneda',
            'MntNeto' => 'Neto $',
            'MntExe' => 'Exento $',
            'IVA' => 'IVA ('.$totales['TasaIVA'].'%) $',
            'IVANoRet' => 'IVA no retenido $',
            'CredEC' => 'Desc. 65% IVA $',
            'MntTotal' => 'Total $',
            'MontoNF' => 'Monto no facturable $',
            'MontoPeriodo' => 'Monto período $',
            'SaldoAnterior' => 'Saldo anterior $',
            'VlrPagar' => 'Valor a pagar $',
        ];
        // agregar impuestos adicionales y retenciones
        if (!empty($totales['ImptoReten'])) {
            $ImptoReten = $totales['ImptoReten'];
            $MntTotal = $totales['MntTotal'];
            unset($totales['ImptoReten'], $totales['MntTotal']);
            if (!isset($ImptoReten[0])) {
                $ImptoReten = [$ImptoReten];
            }
            foreach($ImptoReten as $i) {
                $totales['ImptoReten_'.$i['TipoImp']] = $i['MontoImp'];
                if (!empty($i['TasaImp'])) {
                    $glosas['ImptoReten_'.$i['TipoImp']] = \sasco\LibreDTE\Sii\ImpuestosAdicionales::getGlosa($i['TipoImp']).' ('.$i['TasaImp'].'%) $';
                } else {
                    $glosas['ImptoReten_'.$i['TipoImp']] = \sasco\LibreDTE\Sii\ImpuestosAdicionales::getGlosa($i['TipoImp']).' $';
                }
            }
            $totales['MntTotal'] = $MntTotal;
        }
        // agregar cada uno de los totales
        $this->setY($y);
        $this->setFont('', 'B', null);
        foreach ($totales as $key => $total) {
            if ($total!==false and isset($glosas[$key])) {
                $y = $this->GetY();
                if (!$this->cedible or $this->papelContinuo) {
                    $this->Texto($glosas[$key].' :', $x, null, 'R', 30);
                    $this->Texto($this->num($total), $x+$offset, $y, 'R', 30);
                    $this->Ln();
                } else {
                    $this->MultiTexto($glosas[$key].' :', $x, null, 'R', 30);
                    $y_new = $this->GetY();
                    $this->Texto($this->num($total), $x+$offset, $y, 'R', 30);
                    $this->SetY($y_new);
                }
            }
        }
        // agregar totales en otra moneda
        if (!empty($otra_moneda)) {
            if (!isset($otra_moneda[0])) {
                $otra_moneda = [$otra_moneda];
            }
            $this->setFont('', '', null);
            $this->Ln();
            foreach ($otra_moneda as $om) {
                $y = $this->GetY();
                if (!$this->cedible or $this->papelContinuo) {
                    $this->Texto('Total '.$om['TpoMoneda'].' :', $x, null, 'R', 30);
                    $this->Texto($this->num($om['MntTotOtrMnda']), $x+$offset, $y, 'R', 30);
                    $this->Ln();
                } else {
                    $this->MultiTexto('Total '.$om['TpoMoneda'].' :', $x, null, 'R', 30);
                    $y_new = $this->GetY();
                    $this->Texto($this->num($om['MntTotOtrMnda']), $x+$offset, $y, 'R', 30);
                    $this->SetY($y_new);
                }
            }
            $this->setFont('', 'B', null);
        }
    }

    /**
     * Método que coloca las diferentes observaciones que puede tener el documnto
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-06-15
     */
    protected function agregarObservacion($IdDoc, $x = 10, $y = 190)
    {
        $y = (!$this->papelContinuo and !$this->timbre_pie) ? $this->x_fin_datos : $y;
        if (!$this->papelContinuo and $this->timbre_pie) {
            $y -= 15;
        }
        $this->SetXY($x, $y);
        if (!empty($IdDoc['TermPagoGlosa'])) {
            $this->MultiTexto('Observación: '.$IdDoc['TermPagoGlosa']);
        }
        if (!empty($IdDoc['MedioPago']) or !empty($IdDoc['TermPagoDias'])) {
            $pago = [];
            if (!empty($IdDoc['MedioPago'])) {
                $medio = 'Medio de pago: '.(!empty($this->medios_pago[$IdDoc['MedioPago']]) ? $this->medios_pago[$IdDoc['MedioPago']] : $IdDoc['MedioPago']);
                if (!empty($IdDoc['BcoPago'])) {
                    $medio .= ' a '.$IdDoc['BcoPago'];
                }
                if (!empty($IdDoc['TpoCtaPago'])) {
                    $medio .= ' en cuenta '.strtolower($IdDoc['TpoCtaPago']);
                }
                if (!empty($IdDoc['NumCtaPago'])) {
                    $medio .= ' N° '.$IdDoc['NumCtaPago'];
                }
                $pago[] = $medio;
            }
            if (!empty($IdDoc['TermPagoDias'])) {
                $pago[] = 'Días de pago: '.$IdDoc['TermPagoDias'];
            }
            $this->SetXY($x, $this->GetY());
            $this->MultiTexto(implode(' / ', $pago));
        }
        return $this->GetY();
    }

    /**
     * Método que agrega el timbre de la factura
     *  - Se imprime en el tamaño mínimo: 2x5 cms
     *  - En el lado de abajo con margen izquierdo mínimo de 2 cms
     * @param timbre String con los datos del timbre
     * @param x Posición horizontal de inicio en el PDF
     * @param y Posición vertical de inicio en el PDF
     * @param w Ancho del timbre
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-10-06
     */
    protected function agregarTimbre($timbre, $x_timbre = 10, $x = 10, $y = 190, $w = 70, $font_size = 8, $position = null)
    {
        $y = (!$this->papelContinuo and !$this->timbre_pie) ? $this->x_fin_datos : $y;
        if ($timbre!==null) {
            $style = [
                'border' => false,
                'padding' => 0,
                'hpadding' => 0,
                'vpadding' => 0,
                'module_width' => 1, // width of a single module in points
                'module_height' => 1, // height of a single module in points
                'fgcolor' => [0,0,0],
                'bgcolor' => false, // [255,255,255]
                'position' => $position === null ? ($this->papelContinuo ? 'C' : 'S') : $position,
            ];
            $ecl = version_compare(phpversion(), '7.0.0', '<') ? -1 : $this->ecl;
            $this->write2DBarcode($timbre, 'PDF417,,'.$ecl, $x_timbre, $y, $w, 0, $style, 'B');
            $this->setFont('', 'B', $font_size);
            $this->Texto('Timbre Electrónico SII', $x, null, 'C', $w);
            $this->Ln();
            $this->Texto('Resolución '.$this->resolucion['NroResol'].' de '.explode('-', $this->resolucion['FchResol'])[0], $x, null, 'C', $w);
            $this->Ln();
            if ($w>=60) {
                $this->Texto('Verifique documento: '.$this->web_verificacion, $x, null, 'C', $w);
            } else {
                $this->Texto('Verifique documento:', $x, null, 'C', $w);
                $this->Ln();
                $this->Texto($this->web_verificacion, $x, null, 'C', $w);
            }
        }
    }

    /**
     * Método que agrega el acuse de rebido
     * @param x Posición horizontal de inicio en el PDF
     * @param y Posición vertical de inicio en el PDF
     * @param w Ancho del acuse de recibo
     * @param h Alto del acuse de recibo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-10-06
     */
    protected function agregarAcuseRecibo($x = 83, $y = 190, $w = 60, $h = 40, $line = 25)
    {
        $y = (!$this->papelContinuo and !$this->timbre_pie) ? $this->x_fin_datos : $y;
        $this->SetTextColorArray([0,0,0]);
        $this->Rect($x, $y, $w, $h, 'D', ['all' => ['width' => 0.1, 'color' => [0, 0, 0]]]);
        $this->setFont('', 'B', 10);
        $this->Texto('Acuse de recibo', $x, $y+1, 'C', $w);
        $this->setFont('', 'B', 8);
        $this->Texto('Nombre', $x+2, $this->y+8);
        $this->Texto(str_repeat('_', $line), $x+18);
        $this->Texto('RUN', $x+2, $this->y+6);
        $this->Texto(str_repeat('_', $line), $x+18);
        $this->Texto('Fecha', $x+2, $this->y+6);
        $this->Texto(str_repeat('_', $line), $x+18);
        $this->Texto('Recinto', $x+2, $this->y+6);
        $this->Texto(str_repeat('_', $line), $x+18);
        $this->Texto('Firma', $x+2, $this->y+8);
        $this->Texto(str_repeat('_', $line), $x+18);
        $this->setFont('', 'B', 7);
        $this->MultiTexto('El acuse de recibo que se declara en este acto, de acuerdo a lo dispuesto en la letra b) del Art. 4°, y la letra c) del Art. 5° de la Ley 19.983, acredita que la entrega de mercaderías o servicio (s) prestado (s) ha (n) sido recibido (s).'."\n", $x, $this->y+6, 'J', $w);
    }

    /**
     * Método que agrega el acuse de rebido
     * @param x Posición horizontal de inicio en el PDF
     * @param y Posición vertical de inicio en el PDF
     * @param w Ancho del acuse de recibo
     * @param h Alto del acuse de recibo
     * @author Pablo Reyes (https://github.com/pabloxp)
     * @version 2015-11-17
     */
    protected function agregarAcuseReciboContinuo($x = 3, $y = null, $w = 68, $h = 40)
    {
        $this->SetTextColorArray([0,0,0]);
        $this->Rect($x, $y, $w, $h, 'D', ['all' => ['width' => 0.1, 'color' => [0, 0, 0]]]);
        $style = array('width' => 0.2,'color' => array(0, 0, 0));
        $this->Line($x, $y+22, $w+3, $y+22, $style);
        //$this->setFont('', 'B', 10);
        //$this->Texto('Acuse de recibo', $x, $y+1, 'C', $w);
        $this->setFont('', 'B', 6);
        $this->Texto('Nombre', $x+2, $this->y+8);
        $this->Texto('_____________________________________________', $x+12);
        $this->Texto('RUN', $x+2, $this->y+6);
        $this->Texto('________________', $x+12);
        $this->Texto('Firma', $x+32, $this->y+0.5);
        $this->Texto('___________________', $x+42.5);
        $this->Texto('Fecha', $x+2, $this->y+6);
        $this->Texto('________________', $x+12);
        $this->Texto('Recinto', $x+32, $this->y+0.5);
        $this->Texto('___________________', $x+42.5);

        $this->setFont('', 'B', 5);
        $this->MultiTexto('El acuse de recibo que se declara en este acto, de acuerdo a lo dispuesto en la letra b) del Art. 4°, y la letra c) del Art. 5° de la Ley 19.983, acredita que la entrega de mercaderías o servicio (s) prestado (s) ha (n) sido recibido (s).'."\n", $x+2, $this->y+8, 'J', $w-3);
    }

    /**
     * Método que agrega la leyenda de destino
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-09-10
     */
    protected function agregarLeyendaDestino($tipo, $y = 190, $font_size = 10)
    {
        $y = (!$this->papelContinuo and !$this->timbre_pie and $this->x_fin_datos<=$y) ? $this->x_fin_datos : $y;
        $y += 48;
        $this->setFont('', 'B', $font_size);
        $this->Texto('CEDIBLE'.($tipo==52?' CON SU FACTURA':''), null, $y, 'R');
    }

    /**
     * Método que agrega la leyenda de destino
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-05
     */
    protected function agregarLeyendaDestinoContinuo($tipo)
    {
        $this->setFont('', 'B', 8);
        $this->Texto('CEDIBLE'.($tipo==52?' CON SU FACTURA':''), null, $this->y+6, 'R');
    }

}
