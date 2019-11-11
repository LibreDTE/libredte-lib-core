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

namespace sasco\LibreDTE\Sii\Dte\Base;

/**
 * Trait para las clases que generan documentos impresos (ej: PDF y ESCPOS)
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2018-11-04
 */
trait DteImpreso
{

    protected $dte; ///< Tipo de DTE que se está generando
    protected $resolucion; ///< Arreglo con los datos de la resolución (índices: NroResol y FchResol)
    protected $cedible = false; ///< Por defecto DTEs no son cedibles
    protected $casa_matriz = false; ///< Dirección de la casa matriz
    protected $web_verificacion = 'www.sii.cl'; ///< Página web para verificar el documento

    protected $sinAcuseRecibo = [39, 41, 56, 61, 110, 111, 112]; ///< Boletas, notas de crédito y notas de débito no tienen acuse de recibo

    protected $tipos = [
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
        0 => [
            0 => 'COTIZACIÓN',
            110 => 'FACTURA PROFORMA',
        ],
        'HEM' => 'HOJA DE ENTRADA DE MATERIALES (HEM)',
        'HES' => 'HOJA DE ENTRADA DE SERVICIOS (HES)',
        'EM' => 'Entrada de mercadería (EM)',
        'RDM' => 'Recepción de material/mercadería (RDM)',
    ]; ///< Glosas para los tipos de documentos (DTE y otros)

    protected $formas_pago = [
        1 => 'Contado',
        2 => 'Crédito',
        3 => 'Sin costo',
    ]; ///< Glosas de las formas de pago

    protected $formas_pago_exportacion = [
        1 => 'Cobranza hasta 1 año',
        2 => 'Cobranza más de 1 año',
        11 => 'Acreditivo hasta 1 año',
        12 => 'Acreditivo más de 1 año',
        21 => 'Sin pago',
        32 => 'Pago anticipado a la fecha de embarque',
    ]; ///< Códigos de forma de pago (básicos) de la aduana para exportaciones

    protected $traslados = [
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

    protected $medios_pago = [
        'EF' => 'Efectivo',
        'PE' => 'Depósito o transferencia',
        'TC' => 'Tarjeta de crédito o débito',
        'CH' => 'Cheque',
        'CF' => 'Cheque a fecha',
        'LT' => 'Letra',
        'OT' => 'Otro',
    ]; ///< Medio de pago disponibles

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
     * Método que indica la dirección de la casa matriz
     * @param casa_matriz Dirección de la casa matriz que emite el DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-06-15
     */
    public function setCasaMatriz($casa_matriz)
    {
        $this->casa_matriz = $casa_matriz;
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
     * Método que entrega la glosa del tipo de documento
     * @param tipo Código del tipo de documento
     * @param folio Folio del tipo de documento (usado al ser generados a partir de borradores en formato libredte)
     * @return Glosa del tipo de documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-05
     */
    protected function getTipo($tipo, $folio = 0)
    {
        // el tipo no es númerico y no está asignado como un tipo
        // probablemente se pasó el nombre del tipo de documento directamente
        // útil en referencias que no son estándares y no están listadas en
        // los tipos de documentos reconocidos por LibreDTE
        if (!is_numeric($tipo) and !isset($this->tipos[$tipo])) {
            return $tipo;
        }
        // si no está el tipo de documento en el listado se entrega un
        // nombre genérico usando el código del tipo
        if (!isset($this->tipos[$tipo])) {
            return 'Documento '.$tipo;
        }
        // si el tipo existe y es un string, entonces es el nombre del tipo de
        // documento
        if (is_string($this->tipos[$tipo])) {
            return strtoupper($this->tipos[$tipo]);
        }
        // si el tipo es 0, entonces es una cotización, la cual puede tener
        // diferente nombre según el tipo de documento, este se indica en el
        // folio del documento si existe, si no se entrega el tipo estándar
        if (!$tipo) {
            if (is_string($folio) and strpos($folio, '-')) {
                list($tipo, $folio) = explode('-', $folio);
                if (isset($this->tipos[0][$tipo])) {
                    return $this->tipos[0][$tipo];
                }
            }
            return $this->tipos[0][0];
        }
    }

    /**
     * Método que formatea un número con separador de miles y decimales (si
     * corresponden)
     * @param n Número que se desea formatear
     * @return Número formateado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    protected function num($n)
    {
        if (!is_numeric($n)) {
            return $n;
        }
        $broken_number = explode('.', (string)$n);
        if (isset($broken_number[1])) {
            return number_format($broken_number[0], 0, ',', '.').','.$broken_number[1];
        }
        return number_format($broken_number[0], 0, ',', '.');
    }

    /**
     * Método que formatea una fecha en formato YYYY-MM-DD a un string
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-28
     */
    protected function date($date, $mostrar_dia = true)
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
