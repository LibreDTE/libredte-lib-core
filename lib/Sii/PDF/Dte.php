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
 * Clase para generar el PDF de un documento tributario electrónico (DTE)
 * chileno.
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-10-05
 */
class Dte extends \sasco\LibreDTE\PDF
{

    private $dte; ///< Tipo de DTE que se está generando
    private $logo; ///< Datos del logo que se ubicará en el PDF (ruta, datos y/o posición)
    private $resolucion; ///< Arreglo con los datos de la resolución (índices: NroResol y FchResol)
    private $cedible = false; ///< Por defecto DTEs no son cedibles
    protected $papelContinuo = false; ///< Indica si se usa papel continuo o no
    private $sinAcuseRecibo = [39, 41, 56, 61, 110, 111, 112]; ///< Boletas, notas de crédito y notas de débito no tienen acuse de recibo
    private $web_verificacion = 'www.sii.cl'; ///< Página web para verificar el documento
    private $ecl = 5; ///< error correction level para PHP >= 7.0.0
    private $papel_continuo_alto = 5000; ///< Alto exageradamente grande para autocálculo de alto en papel continuo
    private $timbre_pie = true; ///< Indica si el timbre va al pie o no (va pegado al detalle)

    private $tipos = [
        // códigos oficiales SII
        29 => 'FACTURA DE INICIO',
        30 => 'FACTURA',
        32 => 'FACTURA DE VENTA BIENES Y SERVICIOS NO AFECTOS O EXENTOS DE IVA',
        33 => 'FACTURA ELECTRÓNICA',
        34 => 'FACTURA NO AFECTA O EXENTA ELECTRÓNICA',
        35 => 'BOLETA',
        38 => 'BOLETA EXENTA',
        39 => 'BOLETA ELECTRÓNICA',
        40 => 'LIQUIDACION FACTURA',
        41 => 'BOLETA NO AFECTA O EXENTA ELECTRÓNICA',
        43 => 'LIQUIDACIÓN FACTURA ELECTRÓNICA',
        45 => 'FACTURA DE COMPRA',
        46 => 'FACTURA DE COMPRA ELECTRÓNICA',
        48 => 'COMPROBANTE DE PAGO ELECTRÓNICO',
        50 => 'GUÍA DE DESPACHO',
        52 => 'GUÍA DE DESPACHO ELECTRÓNICA',
        55 => 'NOTA DE DÉBITO',
        56 => 'NOTA DE DÉBITO ELECTRÓNICA',
        60 => 'NOTA DE CRÉDITO',
        61 => 'NOTA DE CRÉDITO ELECTRÓNICA',
        101 => 'FACTURA DE EXPORTACIÓN',
        102 => 'FACTURA DE VENTA EXENTA A ZONA FRANCA PRIMARIA',
        103 => 'LIQUIDACIÓN',
        104 => 'NOTA DE DÉBITO DE EXPORTACIÓN',
        105 => 'BOLETA LIQUIDACIÓN',
        106 => 'NOTA DE CRÉDITO DE EXPORTACIÓN',
        108 => 'SOLICITUD REGISTRO DE FACTURA (SRF)',
        109 => 'FACTURA TURISTA',
        110 => 'FACTURA DE EXPORTACIÓN ELECTRÓNICA',
        111 => 'NOTA DE DÉBITO DE EXPORTACIÓN ELECTRÓNICA',
        112 => 'NOTA DE CRÉDITO DE EXPORTACIÓN ELECTRÓNICA',
        801 => 'ORDEN DE COMPRA',
        802 => 'NOTA DE PEDIDO',
        803 => 'CONTRATO',
        804 => 'RESOLUCIÓN',
        805 => 'PROCEDO CHILECOMPRA',
        806 => 'FICHA CHILECOMPRA',
        807 => 'DUS',
        808 => 'B/L (CONOCIMIENTO DE EMBARQUE)',
        809 => 'AWB',
        810 => 'MIC (MANIFIESTO INTERNACIONAL)',
        811 => 'CARTA DE PORTE',
        812 => 'RESOLUCION SNA',
        813 => 'PASAPORTE',
        814 => 'CERTIFICADO DE DEPÓSITO BOLSA PROD. CHILE',
        815 => 'VALE DE PRENDA BOLSA PROD. CHILE',
        901 => 'FACTURA DE VENTAS A EMPRESAS DEL TERRITORIO PREFERENCIAL',
        902 => 'CONOCIMIENTO DE EMBARQUE',
        903 => 'DOCUMENTO ÚNICO DE SALIDA (DUS)',
        904 => 'FACTURA DE TRASPASO',
        905 => 'FACTURA DE REEXPEDICIÓN',
        906 => 'BOLETAS VENTA MÓDULOS ZF (TODAS)',
        907 => 'FACTURAS VENTA MÓDULO ZF (TODAS)',
        909 => 'FACTURAS VENTA MÓDULO ZF',
        910 => 'SOLICITUD TRASLADO ZONA FRANCA (Z)',
        911 => 'DECLARACIÓN DE INGRESO A ZONA FRANCA PRIMARIA',
        914 => 'DECLARACIÓN DE INGRESO (DIN)',
        919 => 'RESUMEN VENTAS DE NACIONALES PASAJES SIN FACTURA',
        920 => 'OTROS REGISTROS NO DOCUMENTADOS (AUMENTA DÉBITO)',
        922 => 'OTROS REGISTROS (DISMINUYE DÉBITO)',
        924 => 'RESUMEN VENTAS DE INTERNACIONALES PASAJES SIN FACTURA',
        // códigos de LibreDTE
        0 => 'COTIZACIÓN',
        'HES' => 'HOJA DE ENTRADA DE SERVICIOS (HES)',
    ]; ///< Glosas para los tipos de documentos (DTE y otros)

    private $formas_pago = [
        1 => 'Contado',
        2 => 'Crédito',
        3 => 'Sin costo',
    ]; ///< Glosas de las formas de pago

    private $formas_pago_exportacion = [
        1 => 'Cobranza hasta 1 año',
        2 => 'Cobranza más de 1 año',
        11 => 'Acreditivo hasta 1 año',
        12 => 'Acreditivo más de 1 año',
        21 => 'Sin pago',
        32 => 'Pago anticipado a la fecha de embarque',
    ]; ///< Códigos de forma de pago (básicos) de la aduana para exportaciones

    private $detalle_cols = [
        'CdgItem' => ['title'=>'Código', 'align'=>'left', 'width'=>20],
        'NmbItem' => ['title'=>'Item', 'align'=>'left', 'width'=>0],
        'IndExe' => ['title'=>'IE', 'align'=>'left', 'width'=>'7'],
        'QtyItem' => ['title'=>'Cant.', 'align'=>'right', 'width'=>15],
        'UnmdItem' => ['title'=>'Unidad', 'align'=>'left', 'width'=>22],
        'PrcItem' => ['title'=>'P. unitario', 'align'=>'right', 'width'=>22],
        'DescuentoMonto' => ['title'=>'Descuento', 'align'=>'right', 'width'=>22],
        'RecargoMonto' => ['title'=>'Recargo', 'align'=>'right', 'width'=>22],
        'MontoItem' => ['title'=>'Total item', 'align'=>'right', 'width'=>22],
    ]; ///< Nombres de columnas detalle, alineación y ancho

    private $item_detalle_posicion = 0; ///< Posición del detalle del item respecto al nombre
    private $detalle_fuente = 10; ///< Tamaño de la fuente para el detalle en hoja carta

    private $traslados = [
        1 => 'Operación constituye venta',
        2 => 'Ventas por efectuar',
        3 => 'Consignaciones',
        4 => 'Entrega gratuita',
        5 => 'Traslados internos',
        6 => 'Otros traslados no venta',
        7 => 'Guía de devolución',
        8 => 'Traslado para exportación (no venta)',
        9 => 'Venta para exportación',
    ]; ///< Tipos de traslado para guías de despacho

    public static $papel = [
        0  => 'Hoja carta',
        57 => 'Papel contínuo 57mm',
        75 => 'Papel contínuo 75mm',
        80 => 'Papel contínuo 80mm',
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
     * @param posicion Posición respecto a datos del emisor (=0 izq, =1 arriba)
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
     * Método que asigna los datos de la resolución del SII que autoriza al
     * emisor a emitir DTEs
     * @param resolucion Arreglo con índices NroResol y FchResol
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-08
     */
    public function setResolucion(array $resolucion)
    {
        $this->resolucion = $resolucion;
    }

    /**
     * Método que asigna la página web que se debe utilizar para indicar donde
     * se puede verificar el DTE
     * @param web Página web donde se puede verificar el documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-11
     */
    public function setWebVerificacion($web)
    {
        $this->web_verificacion = $web;
    }

    /**
     * Método que indica si el documento será o no cedible
     * @param cedible =true se incorporará leyenda de destino
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-09
     */
    public function setCedible($cedible = true)
    {
        $this->cedible = $cedible;
    }

    /**
     * Método que asigna la posición del detalle del Item respecto al nombre
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-05
     */
    public function setPosicionDetalleItem($posicion)
    {
        $this->item_detalle_posicion = (int)$posicion;
    }

    /**
     * Método que asigna el tamaño de la fuente para el detalle
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-03
     */
    public function setFuenteDetalle($fuente)
    {
        $this->detalle_fuente = (int)$fuente;
    }

    /**
     * Método que asigna el ancho e las columnas del detalle desde un arreglo
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
     * @version 2017-07-13
     */
    public function agregar(array $dte, $timbre = null)
    {
        $this->dte = $dte['Encabezado']['IdDoc']['TipoDTE'];
        // papel hoja carta
        if (!$this->papelContinuo) {
            $this->agregarNormal($dte, $timbre);
        }
        // papel contínuo 57mm
        else if ($this->papelContinuo==57) {
            $this->agregarContinuo57($dte, $timbre);
        }
        // papel contínuo 75 o 80mm
        else {
            $this->agregarContinuo($dte, $timbre, $this->papelContinuo);
        }
    }

    /**
     * Método que agrega una página con el documento tributario
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-05
     */
    private function agregarNormal(array $dte, $timbre)
    {
        // agregar página para la factura
        $this->AddPage();
        // agregar cabecera del documento
        $y[] = $this->agregarEmisor($dte['Encabezado']['Emisor']);
        $y[] = $this->agregarFolio(
            $dte['Encabezado']['Emisor']['RUTEmisor'],
            $dte['Encabezado']['IdDoc']['TipoDTE'],
            $dte['Encabezado']['IdDoc']['Folio'],
            $dte['Encabezado']['Emisor']['CmnaOrigen']
        );
        // datos del documento
        $this->setY(max($y));
        $this->Ln();
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
     * Método que agrega una página con el documento tributario en papel
     * contínuo
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @param width Ancho del papel contínuo en mm
     * @author Pablo Reyes (https://github.com/pabloxp)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-05
     */
    private function agregarContinuo(array $dte, $timbre, $width, $height = 0)
    {
        // determinar alto de la página y agregarla
        $this->logo = null;
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
        $y = $this->agregarEmisor($dte['Encabezado']['Emisor'], $x_start, $y+2, 40, 8, 9, [0,0,0]);
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
        $this->agregarTimbre($timbre, 0, $x_start, $y+6, 70, 6);
        // si el alto no se pasó, entonces es con autocálculo, se elimina esta página y se pasa el alto
        // que se logró determinar para crear la página con el alto correcto
        if (!$height) {
            $this->deletePage($this->PageNo());
            $this->agregarContinuo($dte, $timbre, $width, $this->getY()+30);
        }
    }

    /**
     * Método que agrega una página con el documento tributario
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-05
     */
    private function agregarContinuo57(array $dte, $timbre, $width = 57, $height = 0)
    {
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
        $this->MultiTexto($this->getTipo($dte['Encabezado']['IdDoc']['TipoDTE']).' N° '.$dte['Encabezado']['IdDoc']['Folio'], $x, null, '', $width-2);
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
        $this->agregarTimbre($timbre, 0, $x, $this->GetY()+6, 55, 6);
        // si el alto no se pasó, entonces es con autocálculo, se elimina esta página y se pasa el alto
        // que se logró determinar para crear la página con el alto correcto
        if (!$height) {
            $this->deletePage($this->PageNo());
            $this->agregarContinuo57($dte, $timbre, $width, $this->getY()+30);
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
     * @version 2016-12-02
     */
    private function agregarEmisor(array $emisor, $x = 10, $y = 15, $w = 75, $w_img = 30, $font_size = null, array $color = null)
    {
        // logo del documento
        if (isset($this->logo)) {
            $this->Image(
                $this->logo['uri'],
                $x,
                $y,
                !$this->logo['posicion']?$w_img:null, $this->logo['posicion']?($w_img/2):null,
                'PNG',
                (isset($emisor['url'])?$emisor['url']:''),
                'T'
            );
            if ($this->logo['posicion']) {
                $this->SetY($this->y + ($w_img/2));
                $w += 40;
            } else {
                $x = $this->x+3;
            }
        } else {
            $this->y = $y-2;
            $w += 40;
        }
        // agregar datos del emisor
        $this->setFont('', 'B', $font_size ? $font_size : 14);
        $this->SetTextColorArray($color===null?[32, 92, 144]:$color);
        $this->MultiTexto(!empty($emisor['RznSoc']) ? $emisor['RznSoc'] : $emisor['RznSocEmisor'], $x, $this->y+2, 'L', $w);
        $this->setFont('', 'B', $font_size ? $font_size : 9);
        $this->SetTextColorArray([0,0,0]);
        $this->MultiTexto(!empty($emisor['GiroEmis']) ? $emisor['GiroEmis'] : $emisor['GiroEmisor'], $x, $this->y, 'L', $w);
        $ciudad = !empty($emisor['CiudadOrigen']) ? $emisor['CiudadOrigen'] : \sasco\LibreDTE\Chile::getCiudad($emisor['CmnaOrigen']);
        $this->MultiTexto($emisor['DirOrigen'].', '.$emisor['CmnaOrigen'].($ciudad?(', '.$ciudad):''), $x, $this->y, 'L', $w);
        if (!empty($emisor['Sucursal'])) {
            $this->MultiTexto('Sucursal: '.$emisor['Sucursal'], $x, $this->y, 'L', $w);
        }
        $contacto = [];
        if (!empty($emisor['Telefono'])) {
            if (!is_array($emisor['Telefono']))
                $emisor['Telefono'] = [$emisor['Telefono']];
            foreach ($emisor['Telefono'] as $t)
                $contacto[] = $t;
        }
        if (!empty($emisor['CorreoEmisor'])) {
            $contacto[] = $emisor['CorreoEmisor'];
        }
        if ($contacto) {
            $this->MultiTexto(implode(' / ', $contacto), $x, $this->y, 'L', $w);
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
     * @version 2016-12-02
     */
    private function agregarFolio($rut, $tipo, $folio, $sucursal_sii = null, $x = 130, $y = 15, $w = 70, $font_size = null, array $color = null)
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
        $this->MultiTexto($this->getTipo($tipo), $x, null, 'C', $w);
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
     * Método que entrega la glosa del tipo de documento
     * @param tipo Código del tipo de documento
     * @return Glosa del tipo de documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-11-18
     */
    private function getTipo($tipo)
    {
        if (!is_numeric($tipo) and !isset($this->tipos[$tipo]))
            return $tipo;
        return isset($this->tipos[$tipo]) ? strtoupper($this->tipos[$tipo]) : 'Documento '.$tipo;
    }

    /**
     * Método que agrega los datos de la emisión del DTE que no son los dato del
     * receptor
     * @param IdDoc Información general del documento
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-06-15
     */
    private function agregarDatosEmision($IdDoc, $CdgVendedor, $x = 10, $offset = 22, $mostrar_dia = true)
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
     * @version 2017-06-15
     */
    private function agregarReceptor(array $Encabezado, $x = 10, $offset = 22)
    {
        $receptor = $Encabezado['Receptor'];
        if (!empty($receptor['RUTRecep']) and $receptor['RUTRecep']!='66666666-6') {
            list($rut, $dv) = explode('-', $receptor['RUTRecep']);
            $this->setFont('', 'B', null);
            $this->Texto('R.U.T.', $x);
            $this->Texto(':', $x+$offset);
            $this->setFont('', '', null);
            $this->MultiTexto($this->num($rut).'-'.$dv, $x+$offset+2);
        }
        if (!empty($receptor['RznSocRecep'])) {
            $this->setFont('', 'B', null);
            $this->Texto('Señor(es)', $x);
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
    private function agregarTraslado($IndTraslado, array $Transporte = null, $x = 10, $offset = 22)
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
                if (!empty($Transporte['Chofer']['NombreChofer']))
                    $transporte .= ' con chofer '.$Transporte['Chofer']['NombreChofer'];
                if (!empty($Transporte['Chofer']['RUTChofer']))
                    $transporte .= ' ('.$Transporte['Chofer']['RUTChofer'].')';
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
                if ($codigo===false)
                    continue;
                $glosa = \sasco\LibreDTE\Sii\Aduana::getGlosa($tag);
                $valor = \sasco\LibreDTE\Sii\Aduana::getValor($tag, $codigo);
                if ($glosa!==false and $valor!==false) {
                    if ($tag=='TipoBultos' and $col) {
                        $col = abs($col-110);
                        $this->Ln();
                    }
                    $this->setFont('', 'B', null);
                    $this->Texto($glosa, $x+$col);
                    $this->Texto(':', $x+$offset+$col);
                    $this->setFont('', '', null);
                    $this->Texto($valor, $x+$offset+2+$col);
                    if ($tag=='TipoBultos')
                        $col = abs($col-110);
                    if ($col)
                        $this->Ln();
                    $col = abs($col-110);
                }
            }
            if ($col)
                $this->Ln();
        }
    }

    /**
     * Método que agrega las referencias del documento
     * @param referencias Arreglo con las referencias del documento (tag Referencia del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-25
     */
    private function agregarReferencia($referencias, $x = 10, $offset = 22)
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
     * @version 2016-08-05
     */
    private function agregarDetalle($detalle, $x = 10)
    {
        if (!isset($detalle[0]))
            $detalle = [$detalle];
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
                    $item['NmbItem'] .= !$this->item_detalle_posicion ? '<br/>' : ': ';
                    $item['NmbItem'] .= '<span style="font-size:0.7em">'.$item['DscItem'].'</span>';
                }
                if (!in_array($col, $titulos_keys) or ($dte_exento and $col=='IndExe'))
                    unset($item[$col]);
            }
            // ajustes a IndExe
            if (isset($item['IndExe'])) {
                if ($item['IndExe']==1)
                    $item['IndExe'] = 'EX';
                else if ($item['IndExe']==2)
                    $item['IndExe'] = 'NF';
            }
            // agregar todas las columnas que se podrían imprimir en la tabla
            $item_default = [];
            foreach ($this->detalle_cols as $key => $info)
                $item_default[$key] = false;
            $item = array_merge($item_default, $item);
            // si hay código de item se extrae su valor
            if ($item['CdgItem'])
                $item['CdgItem'] = $item['CdgItem']['VlrCodigo'];
            // dar formato a números
            foreach (['QtyItem', 'PrcItem', 'DescuentoMonto', 'RecargoMonto', 'MontoItem'] as $col) {
                if ($item[$col])
                    $item[$col] = $this->num($item[$col]);
            }
        }
        // opciones
        $options = ['align'=>[]];
        $i = 0;
        foreach ($this->detalle_cols as $info) {
            if (isset($info['width']))
                $options['width'][$i] = $info['width'];
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
     * @author Pablo Reyes (https://github.com/pabloxp)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-13
     */
    private function agregarDetalleContinuo($detalle, $x = 3)
    {
        $this->SetY($this->getY()+1);
        $p1x = $x;
        $p1y = $this->y;
        $p2x = $this->getPageWidth() - 2;
        $p2y = $p1y;  // Use same y for a straight line
        $style = array('width' => 0.2,'color' => array(0, 0, 0));
        $this->Line($p1x, $p1y, $p2x, $p2y, $style);
        $this->Texto($this->detalle_cols['NmbItem']['title'], $x+1, $this->y, ucfirst($this->detalle_cols['NmbItem']['align'][0]), $this->detalle_cols['NmbItem']['width']);
        $this->Texto($this->detalle_cols['PrcItem']['title'], $x+15, $this->y, ucfirst($this->detalle_cols['PrcItem']['align'][0]), $this->detalle_cols['PrcItem']['width']);
        $this->Texto($this->detalle_cols['QtyItem']['title'], $x+35, $this->y, ucfirst($this->detalle_cols['QtyItem']['align'][0]), $this->detalle_cols['QtyItem']['width']);
        $this->Texto($this->detalle_cols['MontoItem']['title'], $x+45, $this->y, ucfirst($this->detalle_cols['MontoItem']['align'][0]), $this->detalle_cols['MontoItem']['width']);
        $this->Line($p1x, $p1y+4, $p2x, $p2y+4, $style);
        if (!isset($detalle[0]))
            $detalle = [$detalle];
        $this->SetY($this->getY()+2);
        foreach($detalle as  &$d) {
            $this->MultiTexto($d['NmbItem'], $x+1, $this->y+4, ucfirst($this->detalle_cols['NmbItem']['align'][0]), $this->detalle_cols['NmbItem']['width']);
            $this->Texto(number_format($d['PrcItem'],0,',','.'), $x+15, $this->y, ucfirst($this->detalle_cols['PrcItem']['align'][0]), $this->detalle_cols['PrcItem']['width']);
            $this->Texto($this->num($d['QtyItem']), $x+35, $this->y, ucfirst($this->detalle_cols['QtyItem']['align'][0]), $this->detalle_cols['QtyItem']['width']);
            $this->Texto($this->num($d['MontoItem']), $x+45, $this->y, ucfirst($this->detalle_cols['MontoItem']['align'][0]), $this->detalle_cols['MontoItem']['width']);
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
    private function agregarSubTotal(array $detalle, $x = 10) {
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
     * @version 2016-08-17
     */
    private function agregarDescuentosRecargos(array $descuentosRecargos, $x = 10)
    {
        if (!isset($descuentosRecargos[0]))
            $descuentosRecargos = [$descuentosRecargos];
        foreach($descuentosRecargos as $dr) {
            $tipo = $dr['TpoMov']=='D' ? 'Descuento' : 'Recargo';
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
    private function agregarPagos(array $pagos, $x = 10)
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
    private function agregarTotales(array $totales, $otra_moneda, $y = 190, $x = 145, $offset = 25)
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
     * @version 2017-10-05
     */
    private function agregarObservacion($IdDoc, $x = 10, $y = 190)
    {
        $y = (!$this->papelContinuo and !$this->timbre_pie) ? $this->x_fin_datos : $y;
        if (!$this->papelContinuo and $this->timbre_pie) {
            $y -= 15;
        }
        $this->SetXY($x, $y);
        if (!empty($IdDoc['TermPagoGlosa'])) {
            $this->MultiTexto('Observación: '.$IdDoc['TermPagoGlosa']);
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
     * @version 2017-10-05
     */
    private function agregarTimbre($timbre, $x_timbre = 20, $x = 20, $y = 190, $w = 70, $font_size = 8)
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
                'position' => $this->papelContinuo ? 'C' : 'S',
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
     * @version 2017-10-05
     */
    private function agregarAcuseRecibo($x = 93, $y = 190, $w = 50, $h = 40)
    {
        $y = (!$this->papelContinuo and !$this->timbre_pie) ? $this->x_fin_datos : $y;
        $this->SetTextColorArray([0,0,0]);
        $this->Rect($x, $y, $w, $h, 'D', ['all' => ['width' => 0.1, 'color' => [0, 0, 0]]]);
        $this->setFont('', 'B', 10);
        $this->Texto('Acuse de recibo', $x, $y+1, 'C', $w);
        $this->setFont('', 'B', 8);
        $this->Texto('Nombre', $x+2, $this->y+8);
        $this->Texto('________________', $x+18);
        $this->Texto('R.U.T.', $x+2, $this->y+6);
        $this->Texto('________________', $x+18);
        $this->Texto('Fecha', $x+2, $this->y+6);
        $this->Texto('________________', $x+18);
        $this->Texto('Recinto', $x+2, $this->y+6);
        $this->Texto('________________', $x+18);
        $this->Texto('Firma', $x+2, $this->y+8);
        $this->Texto('________________', $x+18);
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
    private function agregarAcuseReciboContinuo($x = 3, $y = null, $w = 68, $h = 40)
    {
        $this->SetTextColorArray([0,0,0]);
        $this->Rect($x, $y, $w, $h, 'D', ['all' => ['width' => 0.1, 'color' => [0, 0, 0]]]);
        $style = array('width' => 0.2,'color' => array(0, 0, 0));
        $this->Line($x, $y+22, $w+3, $y+22, $style);
        //$this->setFont('', 'B', 10);
        //$this->Texto('Acuse de recibo', $x, $y+1, 'C', $w);
        $this->setFont('', 'B', 6);
        $this->Texto('Nombre:', $x+2, $this->y+8);
        $this->Texto('_____________________________________________', $x+12);
        $this->Texto('R.U.T.:', $x+2, $this->y+6);
        $this->Texto('________________', $x+12);
        $this->Texto('Firma:', $x+32, $this->y+0.5);
        $this->Texto('___________________', $x+42.5);
        $this->Texto('Fecha:', $x+2, $this->y+6);
        $this->Texto('________________', $x+12);
        $this->Texto('Recinto:', $x+32, $this->y+0.5);
        $this->Texto('___________________', $x+42.5);

        $this->setFont('', 'B', 5);
        $this->MultiTexto('El acuse de recibo que se declara en este acto, de acuerdo a lo dispuesto en la letra b) del Art. 4°, y la letra c) del Art. 5° de la Ley 19.983, acredita que la entrega de mercaderías o servicio (s) prestado (s) ha (n) sido recibido (s).'."\n", $x+2, $this->y+8, 'J', $w-3);
    }

    /**
     * Método que agrega la leyenda de destino
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-05
     */
    private function agregarLeyendaDestino($tipo, $y = 190, $font_size = 10)
    {
        $y = (!$this->papelContinuo and !$this->timbre_pie) ? $this->x_fin_datos : $y;
        $y += 64;
        $this->setFont('', 'B', $font_size);
        $this->Texto('CEDIBLE'.($tipo==52?' CON SU FACTURA':''), null, $y, 'R');
    }

    /**
     * Método que agrega la leyenda de destino
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-05
     */
    private function agregarLeyendaDestinoContinuo($tipo)
    {
        $this->setFont('', 'B', 8);
        $this->Texto('CEDIBLE'.($tipo==52?' CON SU FACTURA':''), null, $this->y+6, 'R');
    }

    /**
     * Método que formatea un número con separador de miles y decimales (si
     * corresponden)
     * @param n Número que se desea formatear
     * @return Número formateado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function num($n)
    {
        if (!is_numeric($n))
            return $n;
        $broken_number = explode('.', (string)$n);
        if (isset($broken_number[1]))
            return number_format($broken_number[0], 0, ',', '.').','.$broken_number[1];
        return number_format($broken_number[0], 0, ',', '.');
    }

    /**
     * Método que formatea una fecha en formato YYYY-MM-DD a un string
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-28
     */
    public function date($date, $mostrar_dia = true)
    {
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $unixtime = strtotime($date);
        $fecha = date(($mostrar_dia?'\D\I\A ':'').'j \d\e \M\E\S \d\e\l Y', $unixtime);
        $dia = $dias[date('w', $unixtime)];
        $mes = $meses[date('n', $unixtime)-1];
        return str_replace(array('DIA', 'MES'), array($dia, $mes), $fecha);
    }

}
