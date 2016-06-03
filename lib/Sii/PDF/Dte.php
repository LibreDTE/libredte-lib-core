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
 * @version 2016-06-03
 */
class Dte extends \sasco\LibreDTE\PDF
{

    private $logo; ///< Ubicación del logo del emisor que se incluirá en el pdf
    private $resolucion; ///< Arreglo con los datos de la resolución (índices: NroResol y FchResol)
    private $cedible = false; ///< Por defecto DTEs no son cedibles
    protected $papelContinuo = false; ///< Indica si se usa papel continuo o no
    private $sinAcuseRecibo = [39, 41, 56, 61, 111, 112]; ///< Boletas, notas de crédito y notas de débito no tienen acuse de recibo
    private $web_verificacion = 'www.sii.cl'; ///< Página web para verificar el documento
    private $ecl = 8; ///< error correction level para PHP >= 7.0.0

    private $tipos = [
        33 => 'FACTURA ELECTRÓNICA',
        34 => 'FACTURA NO AFECTA O EXENTA ELECTRÓNICA',
        39 => 'BOLETA ELECTRÓNICA',
        41 => 'BOLETA NO AFECTA O EXENTA ELECTRÓNICA',
        43 => 'LIQUIDACIÓN FACTURA ELECTRÓNICA',
        46 => 'FACTURA DE COMPRA ELECTRÓNICA',
        52 => 'GUÍA DE DESPACHO ELECTRÓNICA',
        56 => 'NOTA DE DÉBITO ELECTRÓNICA',
        61 => 'NOTA DE CRÉDITO ELECTRÓNICA',
        110 => 'FACTURA DE EXPORTACIÓN ELECTRÓNICA',
        111 => 'NOTA DE DÉBITO DE EXPORTACIÓN ELECTRÓNICA',
        112 => 'NOTA DE CRÉDITO DE EXPORTACIÓN ELECTRÓNICA',
        807 => 'DUS',
        809 => 'AWB',
        810 => 'MIC (MANIFIESTO INTERNACIONAL)',
        812 => 'RESOLUCION SNA',
        813 => 'PASAPORTE',
    ]; ///< Glosas para los tipos de documentos (DTE y otros)

    private $formas_pago = [
        1 => 'Contado',
        2 => 'Crédito',
        3 => 'Sin costo (entrega gratuita)',
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
        'QtyItem' => ['title'=>'Cant.', 'align'=>'right', 'width'=>15],
        'UnmdItem' => ['title'=>'Unidad', 'align'=>'left', 'width'=>22],
        'PrcItem' => ['title'=>'P. unitario', 'align'=>'right', 'width'=>22],
        'DescuentoMonto' => ['title'=>'Descuento', 'align'=>'right', 'width'=>22],
        'RecargoMonto' => ['title'=>'Recargo', 'align'=>'right', 'width'=>22],
        'MontoItem' => ['title'=>'Total item', 'align'=>'right', 'width'=>22],
    ]; ///< Nombres de columnas detalle, alineación y ancho

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

    /**
     * Constructor de la clase
     * @param papelContinuo =true indica que el PDF se generará en formato papel continuo (si se pasa un número será el ancho del PDF en mm)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-10
     */
    public function __construct($papelContinuo = false)
    {
        parent::__construct();
        $this->SetTitle('Documento Tributario Electrónico (DTE) de Chile');
        $this->papelContinuo = $papelContinuo === true ? 80 : $papelContinuo;
    }

    /**
     * Método que asigna la ubicación del logo de la empresa
     * @param logo URI del logo (puede ser local o en una URL)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-08
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
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
     * Método que agrega un documento tributario, ya sea en formato de una
     * página o papel contínuo según se haya indicado en el constructor
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-11-28
     */
    public function agregar(array $dte, $timbre)
    {
        if ($this->papelContinuo) {
            $this->agregarContinuo($dte, $timbre, $this->papelContinuo);
        } else {
            $this->agregarNormal($dte, $timbre);
        }
    }

    /**
     * Método que agrega una página con el documento tributario
     * @param dte Arreglo con los datos del XML (tag Documento)
     * @param timbre String XML con el tag TED del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-11
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
        $this->agregarFechaEmision($dte['Encabezado']['IdDoc']['FchEmis']);
        $this->agregarCondicionVenta($dte['Encabezado']['IdDoc']);
        $this->agregarReceptor($dte['Encabezado']['Receptor']);
        $this->agregarTraslado(
            !empty($dte['Encabezado']['IdDoc']['IndTraslado']) ? $dte['Encabezado']['IdDoc']['IndTraslado'] : null,
            !empty($dte['Encabezado']['Transporte']) ? $dte['Encabezado']['Transporte'] : null
        );
        if (!empty($dte['Referencia']))
            $this->agregarReferencia($dte['Referencia']);
        $this->agregarDetalle($dte['Detalle']);
        if (!empty($dte['DscRcgGlobal']))
            $this->agregarDescuentosRecargos($dte['DscRcgGlobal']);
        $this->agregarTotales($dte['Encabezado']['Totales']);
        // agregar observaciones
        $this->agregarObservacion($dte['Encabezado']['IdDoc']);
        // agregar timbre
        $this->agregarTimbre($timbre);
        // agregar acuse de recibo y leyenda cedible
        if ($this->cedible) {
            if (!in_array($dte['Encabezado']['IdDoc']['TipoDTE'], $this->sinAcuseRecibo)) {
                $this->agregarAcuseRecibo();
            }
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
     * @version 2016-04-11
     */
    private function agregarContinuo(array $dte, $timbre, $width)
    {
        $this->logo = null;
        // determinar alto de la página y agregarla
        $height = 145;
        $n_detalle = count($dte['Detalle']);
        if ($n_detalle>1) $height += $n_detalle*20;
        if ($this->cedible) $height += 50;
        $this->AddPage('P',array($height, $width));
        // agregar cabecera del documento
        $y = $this->agregarFolio(
            $dte['Encabezado']['Emisor']['RUTEmisor'],
            $dte['Encabezado']['IdDoc']['TipoDTE'],
            $dte['Encabezado']['IdDoc']['Folio'],
            $dte['Encabezado']['Emisor']['CmnaOrigen'],
            3, 3, 68, 10
        );
        $y = $this->agregarEmisor($dte['Encabezado']['Emisor'], 2, $y+2, 75, 8, 9);
        // datos del documento
        $this->SetY($y);
        $this->Ln();
        $this->setFont('', '', 8);
        $this->agregarFechaEmision($dte['Encabezado']['IdDoc']['FchEmis'], 2, 14, false);
        $this->agregarCondicionVenta($dte['Encabezado']['IdDoc'], 2, 14, false);
        $this->agregarReceptor($dte['Encabezado']['Receptor'], 2, 14);
        $this->agregarTraslado(
            !empty($dte['Encabezado']['IdDoc']['IndTraslado']) ? $dte['Encabezado']['IdDoc']['IndTraslado'] : null,
            !empty($dte['Encabezado']['Transporte']) ? $dte['Encabezado']['Transporte'] : null,
            2, 14
        );
        if (!empty($dte['Referencia'])) {
            $this->agregarReferencia($dte['Referencia'], 2, 14);
        }
        $this->Ln();
        $this->agregarDetalleContinuo($dte['Detalle']);
        if (!empty($dte['DscRcgGlobal'])) {
            $this->Ln();
            $this->Ln();
            $this->agregarDescuentosRecargos($dte['DscRcgGlobal'], 2);
        }
        $this->agregarTotales($dte['Encabezado']['Totales'], $this->y+6, 23, 17);
        // agregar acuse de recibo y leyenda cedible
        if ($this->cedible) {
            if (!in_array($dte['Encabezado']['IdDoc']['TipoDTE'], $this->sinAcuseRecibo)) {
                $this->agregarAcuseReciboContinuo(3, $this->y+6, 68, 34);
            }
            $this->agregarLeyendaDestino($dte['Encabezado']['IdDoc']['TipoDTE'], $this->y+6, 8);
        }
        // agregar timbre
        $y = $this->agregarObservacion($dte['Encabezado']['IdDoc'], 3, $this->y+6);
        $this->agregarTimbre($timbre, 13, 3, $y+6, 70, 6);
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
     * @version 2016-05-28
     */
    private function agregarEmisor(array $emisor, $x = 10, $y = 15, $w = 75, $w_img = 30, $font_size = null)
    {
        // logo máximo 1/5 del tamaño del documento
        if (isset($this->logo)) {
            $this->Image($this->logo, $x, $y, $w_img, 0, 'PNG', (isset($emisor['url'])?$emisor['url']:''), 'T');
            $x = $this->x+3;
        } else {
            $this->y = $y-2;
            $w += 40;
        }
        // agregar datos del emisor
        $this->setFont('', 'B', $font_size ? $font_size : 14);
        $this->SetTextColorArray([32, 92, 144]);
        $this->MultiTexto(!empty($emisor['RznSoc']) ? $emisor['RznSoc'] : $emisor['RznSocEmisor'], $x, $this->y+2, 'L', $w);
        $this->setFont('', 'B', $font_size ? $font_size : 9);
        $this->SetTextColorArray([0,0,0]);
        $this->MultiTexto(!empty($emisor['GiroEmis']) ? $emisor['GiroEmis'] : $emisor['GiroEmisor'], $x, $this->y, 'L', $w);
        $this->MultiTexto($emisor['DirOrigen'].', '.$emisor['CmnaOrigen'], $x, $this->y, 'L', $w);
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
     * @version 2016-06-03
     */
    private function agregarFolio($rut, $tipo, $folio, $sucursal_sii = null, $x = 130, $y = 15, $w = 70, $font_size = null)
    {
        $color = $tipo==52 ? [0,172,140] : [255,0,0];
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
        $this->Texto('S.I.I. - '.\sasco\LibreDTE\Sii::getDireccionRegional($sucursal_sii), $x, $this->getY()+4, 'C', $w);
        $this->SetTextColorArray([0,0,0]);
        $this->Ln();
        return $this->y;
    }

    /**
     * Método que entrega la glosa del tipo de documento
     * @param tipo Código del tipo de documento
     * @return Glosa del tipo de documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-08
     */
    private function getTipo($tipo)
    {
        if (!is_numeric($tipo))
            return $tipo;
        return isset($this->tipos[$tipo]) ? strtoupper($this->tipos[$tipo]) : 'DTE '.$tipo;
    }

    /**
     * Método que agrega la fecha de emisión de la factura
     * @param date Fecha de emisión de la boleta en formato AAAA-MM-DD
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-28
     */
    private function agregarFechaEmision($date, $x = 10, $offset = 22, $mostrar_dia = true)
    {
        $this->Texto('Emisión', $x);
        $this->Texto(':', $x+$offset);
        $this->MultiTexto($this->date($date, $mostrar_dia), $x+$offset+2);
    }

    /**
     * Método que agrega la condición de venta del documento
     * @param IdDoc Información general del documento
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-28
     */
    private function agregarCondicionVenta($IdDoc, $x = 10, $offset = 22, $mostrar_dia = true)
    {
        // forma de pago nacional
        if (!empty($IdDoc['FmaPago'])) {
            $this->Texto('Venta', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($this->formas_pago[$IdDoc['FmaPago']], $x+$offset+2);
        }
        // forma de pago exportación
        if (!empty($IdDoc['FmaPagExp'])) {
            $this->Texto('Venta', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($this->formas_pago_exportacion[$IdDoc['FmaPagExp']], $x+$offset+2);
        }
        // pago anticicado
        if (!empty($IdDoc['FchCancel'])) {
            $this->Texto('Pagado el', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($this->date($IdDoc['FchCancel'], $mostrar_dia), $x+$offset+2);
        }
        // fecha vencimiento
        if (!empty($IdDoc['FchVenc'])) {
            $this->Texto('Vencimiento', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($this->date($IdDoc['FchVenc'], $mostrar_dia), $x+$offset+2);
        }
    }

    /**
     * Método que agrega los datos del receptor
     * @param receptor Arreglo con los datos del receptor (tag Receptor del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-29
     */
    private function agregarReceptor(array $receptor, $x = 10, $offset = 22)
    {
        if (!empty($receptor['RznSocRecep'])) {
            $this->Texto('Señor(es)', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($receptor['RznSocRecep'], $x+$offset+2);
        }
        if (!empty($receptor['RUTRecep']) and $receptor['RUTRecep']!='66666666-6') {
            list($rut, $dv) = explode('-', $receptor['RUTRecep']);
            $this->Texto('R.U.T.', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($this->num($rut).'-'.$dv, $x+$offset+2);
        }
        if (!empty($receptor['GiroRecep'])) {
            $this->Texto('Giro', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($receptor['GiroRecep'], $x+$offset+2);
        }
        if (!empty($receptor['DirRecep'])) {
            $this->Texto('Dirección', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($receptor['DirRecep'].(!empty($receptor['CmnaRecep'])?(', '.$receptor['CmnaRecep']):''), $x+$offset+2);
        }
        $contacto = [];
        if (!empty($receptor['Contacto']))
            $contacto[] = $receptor['Contacto'];
        if (!empty($receptor['CorreoRecep']))
            $contacto[] = $receptor['CorreoRecep'];
        if (!empty($contacto)) {
            $this->Texto('Contacto', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto(implode(' / ', $contacto), $x+$offset+2);
        }
    }

    /**
     * Método que agrega los datos del traslado
     * @param IndTraslado
     * @param Transporte
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function agregarTraslado($IndTraslado, array $Transporte = null, $x = 10, $offset = 22)
    {
        // agregar tipo de traslado
        if ($IndTraslado) {
            $this->Texto('Traslado', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($this->traslados[$IndTraslado], $x+$offset+2);
        }
        // agregar información de transporte
        if ($Transporte) {
            $transporte = '';
            if (!empty($Transporte['DirDest']) and !empty($Transporte['CmnaDest'])) {
                $transporte .= 'a '.$Transporte['DirDest'].', '.$Transporte['CmnaDest'].' ';
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
                $this->Texto('Transporte', $x);
                $this->Texto(':', $x+$offset);
                $this->MultiTexto(ucfirst(trim($transporte)), $x+$offset+2);
            }
        }
        // agregar información de aduana
        if (!empty($Transporte['Aduana']) and is_array($Transporte['Aduana'])) {
            $col = 0;
            foreach ($Transporte['Aduana'] as $tag => $codigo) {
                $glosa = \sasco\LibreDTE\Sii\Aduana::getGlosa($tag);
                $valor = \sasco\LibreDTE\Sii\Aduana::getValor($tag, $codigo);
                if ($glosa!==false and $valor!==false) {
                    $this->Texto($glosa, $x+$col);
                    $this->Texto(':', $x+$offset+$col);
                    $this->Texto($valor, $x+$offset+2+$col);
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
     * @version 2016-04-05
     */
    private function agregarReferencia($referencias, $x = 10, $offset = 22)
    {
        if (!isset($referencias[0]))
            $referencias = [$referencias];
        foreach($referencias as $r) {
            $texto = $r['NroLinRef'].' - '.$this->getTipo($r['TpoDocRef']).' N° '.$r['FolioRef'].' del '.$r['FchRef'];
            if (isset($r['RazonRef']) and $r['RazonRef']!==false)
                $texto = $texto.': '.$r['RazonRef'];
            $this->Texto('Referencia', $x);
            $this->Texto(':', $x+$offset);
            $this->MultiTexto($texto, $x+$offset+2);
        }
    }

    /**
     * Método que agrega el detalle del documento
     * @param detalle Arreglo con el detalle del documento (tag Detalle del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @param y Posición vertical de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-25
     */
    private function agregarDetalle($detalle, $x = 10)
    {
        if (!isset($detalle[0]))
            $detalle = [$detalle];
        // titulos
        $titulos = [];
        $titulos_keys = array_keys($this->detalle_cols);
        foreach ($this->detalle_cols as $key => $info) {
            $titulos[$key] = $info['title'];
        }
        // normalizar cada detalle
        foreach ($detalle as &$item) {
            // quitar columnas
            foreach ($item as $col => $valor) {
                if ($col=='DscItem' and !empty($item['DscItem'])) {
                    $item['NmbItem'] .= '<br/><span style="font-size:0.7em">'.$item['DscItem'].'</span>';
                }
                if (!in_array($col, $titulos_keys))
                    unset($item[$col]);
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
     * @version 2016-03-10
     */
    private function agregarDetalleContinuo($detalle, $x = 3,$y=64)
    {
        $pageWidth    = $this->getPageWidth();
        $pageMargins  = $this->getMargins();
        $headerMargin = $pageMargins['header'];
        $px2          = $pageWidth - $headerMargin;
        $this->SetY($this->getY()+1);
        $p1x = 3;
        $p1y = $this->y;
        $p2x = 71;
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
            $this->Texto($d['NmbItem'], $x+1, $this->y+4, ucfirst($this->detalle_cols['NmbItem']['align'][0]), $this->detalle_cols['NmbItem']['width']);
            $this->Texto(number_format($d['PrcItem'],0,',','.'), $x+15, $this->y+3, ucfirst($this->detalle_cols['PrcItem']['align'][0]), $this->detalle_cols['PrcItem']['width']);
            $this->Texto($this->num($d['QtyItem']), $x+35, $this->y, ucfirst($this->detalle_cols['QtyItem']['align'][0]), $this->detalle_cols['QtyItem']['width']);
            $this->Texto($this->num($d['MontoItem']), $x+45, $this->y, ucfirst($this->detalle_cols['MontoItem']['align'][0]), $this->detalle_cols['MontoItem']['width']);
        }
        $this->Line($p1x, $this->y+4, $p2x, $this->y+4, $style);
    }

    /**
     * Método que agrega los descuentos y/o recargos globales del documento
     * @param descuentosRecargos Arreglo con los descuentos y/o recargos del documento (tag DscRcgGlobal del XML)
     * @param x Posición horizontal de inicio en el PDF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    private function agregarDescuentosRecargos(array $descuentosRecargos, $x = 10)
    {
        if (!isset($descuentosRecargos[0]))
            $descuentosRecargos = [$descuentosRecargos];
        foreach($descuentosRecargos as $dr) {
            $tipo = $dr['TpoMov']=='D' ? 'Descuento' : 'Recargo';
            $valor = $dr['TpoValor']=='%' ? $dr['ValorDR'].'%' : '$'.$this->num($dr['ValorDR']).'.-';
            $this->Texto($tipo.' global de '.$valor, $x);
            $this->Ln();
        }
    }

    /**
     * Método que agrega los totales del documento
     * @param totales Arreglo con los totales (tag Totales del XML)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-30
     */
    private function agregarTotales(array $totales, $y = 190, $x = 145, $offset = 25)
    {
        // normalizar totales
        $totales = array_merge([
            'TpoMoneda' => false,
            'MntNeto' => false,
            'MntExe' => false,
            'TasaIVA' => false,
            'IVA' => false,
            'CredEC' => false,
            'MntTotal' => false,
        ], $totales);
        // glosas
        $glosas = [
            'TpoMoneda' => 'Moneda',
            'MntNeto' => 'Neto $',
            'MntExe' => 'Exento $',
            'IVA' => 'IVA ('.$totales['TasaIVA'].'%) $',
            'CredEC' => 'Desc. 65% IVA $',
            'MntTotal' => 'Total $',
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
                $glosas['ImptoReten_'.$i['TipoImp']] = \sasco\LibreDTE\Sii\ImpuestosAdicionales::getGlosa($i['TipoImp']).' ('.$i['TasaImp'].'%) $';
            }
            $totales['MntTotal'] = $MntTotal;
        }
        // agregar cada uno de los totales
        $this->setY($y);
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
    }

    /**
     * Método que coloca las diferentes observaciones que puede tener el documnto
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-11
     */
    private function agregarObservacion($IdDoc, $x = 10, $y = 175)
    {
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
     * @version 2016-03-10
     */
    private function agregarTimbre($timbre, $x_timbre = 20, $x = 20, $y = 190, $w = 70, $font_size = 8)
    {
        $style = [
            'border' => false,
            'vpadding' => 0,
            'hpadding' => 0,
            'fgcolor' => [0,0,0],
            'bgcolor' => false, // [255,255,255]
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        ];
        $ecl = version_compare(phpversion(), '7.0.0', '<') ? -1 : $this->ecl;
        $this->write2DBarcode($timbre, 'PDF417,,'.$ecl, $x_timbre, $y, $w, 0, $style, 'B');
        $this->setFont('', 'B', $font_size);
        $this->Texto('Timbre Electrónico SII', $x, null, 'C', $w);
        $this->Ln();
        $this->Texto('Resolución '.$this->resolucion['NroResol'].' de '.explode('-', $this->resolucion['FchResol'])[0], $x, null, 'C', $w);
        $this->Ln();
        $this->Texto('Verifique documento: '.$this->web_verificacion, $x, null, 'C', $w);
    }

    /**
     * Método que agrega el acuse de rebido
     * @param x Posición horizontal de inicio en el PDF
     * @param y Posición vertical de inicio en el PDF
     * @param w Ancho del acuse de recibo
     * @param h Alto del acuse de recibo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-08
     */
    private function agregarAcuseRecibo($x = 93, $y = 190, $w = 50, $h = 40)
    {
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
     * @version 2016-03-10
     */
    private function agregarLeyendaDestino($tipo, $y = 245, $font_size = 10)
    {
        $this->setFont('', 'B', $font_size);
        $this->Texto('CEDIBLE'.($tipo==52?' CON SU FACTURA':''), null, $y, 'R');
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
