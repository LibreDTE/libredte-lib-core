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

namespace sasco\LibreDTE\Sii\Certificacion;

/**
 * Clase para parsear y procesar los casos de un set pruebas
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-07-06
 */
class SetPruebas
{

    private static $tipos = [
        'FACTURA ELECTRONICA' => 33,
        'FACTURA NO AFECTA O EXENTA ELECTRONICA' => 34,
        'FACTURA DE COMPRA ELECTRONICA' => 46,
        'GUIA DE DESPACHO' => 52,
        'NOTA DE DEBITO ELECTRONICA' => 56,
        'NOTA DE CREDITO ELECTRONICA' => 61,
        'FACTURA DE EXPORTACION ELECTRONICA' => 110,
        'NOTA DE DEBITO DE EXPORTACION ELECTRONICA' => 111,
        'NOTA DE CREDITO DE EXPORTACION ELECTRONICA' => 112,
    ]; ///< Glosas de los tipos de documentos de acuerdo a nombres en set de pruebas

    private static $item_cols = [
        'ITEM' => 'NmbItem',
        'CANTIDAD' => 'QtyItem',
        'UNIDAD MEDIDA' => 'UnmdItem',
        'VALOR UNITARIO' => 'PrcItem',
        'PRECIO UNITARIO' => 'PrcItem',
        'VALOR LINEA' => 'PrcItem',
        'DESCUENTO ITEM' => 'DescuentoPct',
        'RECARGO ITEM' => 'RecargoPct',
    ]; ///< Glosas de los detalles en el set de pruebas y su nombre en el XML

    private static $referencias = [
        'ANULA' => [
            'codigo' => 1,
            'detalle' => 1, // =1 copia todos los detalles
            'Totales' => [
                'MntNeto' => 0,
                'MntExe' => false,
                'TasaIVA' => false, // se copiará IVA de Sii::getIVA()
                'IVA' => 0,
                'MntTotal' => 0,
            ],
        ],
        'CORRIGE' => [
            'codigo' => 2,
            'detalle' => 2, // =2 copia sólo nombre del primer item
            'Totales' => [
                'MntExe' => false, // si es factura exenta se pondrá en 0
                'MntTotal' => 0,
            ],
        ],
        'MODIFICA' => [
            'codigo' => 3,
            'detalle' => false, // =false no se copia nada, ya que el detalle viene en el documento que hace la referencia
            'Totales' => [
                'MntExe' => 0,
                'MntTotal' => 0,
            ],
        ],
        'DEVOLUCION' => [
            'codigo' => 3,
            'detalle' => false, // =false no se copia nada, ya que el detalle viene en el documento que hace la referencia
            'Totales' => [
                'MntNeto' => 0,
                'MntExe' => false,
                'TasaIVA' => false, // se copiará IVA de Sii::getIVA()
                'IVA' => 0,
                'MntTotal' => 0,
            ],
        ],
    ]; ///< Detalles de qué hacer con cada uno de los tipos de referencias

    private static $TipoDespachos = [
        'EMISOR DEL DOCUMENTO AL LOCAL DEL CLIENTE' => 2,
        'CLIENTE' => 1,
    ];

    private static $IndTraslados = [
        'VENTA' => 1,
        'TRASLADO DE MATERIALES ENTRE BODEGAS DE LA EMPRESA' => 5,
    ]; ///< Indicadores para traslados en guías de despacho

    private static $formas_pago_exportacion = [
        'COB1' => 1,
        'COBRANZA' => 2,
        'ACRED' => 11,
        'CBOF' => 12,
        'SIN PAGO' => 21,
        'ANTICIPO' => 32,
    ]; ///< Códigos de forma de pago de la aduana para exportaciones

    private static $referencias_exportacion = [
        'DUS' => 807,
        'AWB' => 809,
        'MIC (MANIFIESTO INTERNACIONAL)' => 810,
        'RESOLUCION SNA' => 812,
    ]; ///< Códigos de referencias para documentos de exportación

    private static $Aduana = [
        'MODALIDAD DE VENTA' => 'CodModVenta',
        'CLAUSULA DE VENTA DE EXPORTACION' => 'CodClauVenta',
        'TOTAL CLAUSULA DE VENTA' => 'TotClauVenta',
        'VIA DE TRANSPORTE' => 'CodViaTransp',
        'PUERTO DE EMBARQUE' => 'CodPtoEmbarque',
        'PUERTO DE DESEMBARQUE' => 'CodPtoDesemb',
        'UNIDAD DE MEDIDA DE TARA' => 'CodUnidMedTara',
        'UNIDAD PESO BRUTO' => 'CodUnidPesoBruto',
        'UNIDAD PESO NETO' => 'CodUnidPesoNeto',
        'TOTAL BULTOS' => 'TotBultos',
        'TIPO DE BULTO' => 'TipoBultos',
        'FLETE (**)' => 'MntFlete',
        'SEGURO (**)' => 'MntSeguro',
        'PAIS RECEPTOR Y PAIS DESTINO' => 'CodPaisRecep',
    ]; ///< Traducción para códigos de aduana usados en set de pruebas

    /**
     * Método que procesa el arreglo con los datos del set de pruebas y crea el
     * arreglo json con los documentos listos para ser pasados a la clase Dte
     * @param archivo Contenido del archivo del set de set de pruebas
     * @param separador usado en el archivo para los casos (son los "=" debajo del título del caso)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-11-04
     */
    public static function getJSON($archivo, array $folios = [], $separador = '==============')
    {
        $documentos = [];
        $casos = self::parse($archivo, $separador);
        $referencias = [];
        foreach ($casos as $caso) {
            // determinar tipo documento y folio
            $TipoDTE = self::$tipos[$caso['documento']];
            if (!isset($folios[$TipoDTE]))
                $folios[$TipoDTE] = 1;
            $Folio = $folios[$TipoDTE];
            // crear encabezado del documento
            $documento = [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => $TipoDTE,
                        'Folio' => $Folio
                    ]
                ],
            ];
            // si es guía de despacho se agrega indicador de traslado
            if ($TipoDTE==52) {
                if (isset($caso['traslado_por'])) {
                    $documento['Encabezado']['IdDoc']['TipoDespacho'] = self::$TipoDespachos[$caso['traslado_por']];
                }
                if (isset($caso['motivo'])) {
                    $documento['Encabezado']['IdDoc']['IndTraslado'] = self::$IndTraslados[$caso['motivo']];
                }
            }
            // si es documento de exportación se agregan datos específicos para exportación
            else if (in_array($TipoDTE, [110, 111, 112])) {
                // si hay datos de exportación se agrega información de receptor más aduana
                if (isset($caso['exportacion'])) {
                    // forma de pago
                    if (!empty($caso['exportacion']['FORMA DE PAGO EXPORTACION'])) {
                        $documento['Encabezado']['IdDoc']['FmaPagExp'] = self::$formas_pago_exportacion[$caso['exportacion']['FORMA DE PAGO EXPORTACION']];
                        unset($caso['exportacion']['FORMA DE PAGO EXPORTACION']);
                    }
                    // datos del receptor
                    if (!empty($caso['exportacion']['NACIONALIDAD'])) {
                        $documento['Encabezado']['Receptor']['Extranjero'] = [
                            'Nacionalidad' => \sasco\LibreDTE\Sii\Aduana::getCodigo(
                                self::$Aduana['PAIS RECEPTOR Y PAIS DESTINO'],
                                $caso['exportacion']['NACIONALIDAD']
                            ),
                        ];
                        unset($caso['exportacion']['NACIONALIDAD']);
                    }
                    // datos de la aduana
                    $documento['Encabezado']['Transporte']['Aduana'] = [];
                    foreach ($caso['exportacion'] as $var => $val) {
                        if (isset(self::$Aduana[$var])) {
                            $tag = self::$Aduana[$var];
                            $valor = \sasco\LibreDTE\Sii\Aduana::getCodigo($tag, $val);
                            $documento['Encabezado']['Transporte']['Aduana'][$tag] = $valor;
                            unset($caso['exportacion'][$var]);
                        }
                    }
                    if (!empty($documento['Encabezado']['Transporte']['Aduana']['CodPaisRecep'])) {
                        $documento['Encabezado']['Transporte']['Aduana']['CodPaisDestin'] = $documento['Encabezado']['Transporte']['Aduana']['CodPaisRecep'];
                    }
                    // si existe tipo de bultos entonces se crea
                    if (!empty($documento['Encabezado']['Transporte']['Aduana']['TipoBultos'])) {
                        $documento['Encabezado']['Transporte']['Aduana']['TipoBultos'] = [
                            'CodTpoBultos' => $documento['Encabezado']['Transporte']['Aduana']['TipoBultos'],
                            'CantBultos' => $documento['Encabezado']['Transporte']['Aduana']['TotBultos'],
                            'Marcas' => md5($documento['Encabezado']['Transporte']['Aduana']['TipoBultos'].$documento['Encabezado']['Transporte']['Aduana']['TotBultos']),
                        ];
                        // si el bulto es contenedor entonces se colocan datos extras
                        if ($documento['Encabezado']['Transporte']['Aduana']['TipoBultos']['CodTpoBultos']==75) {
                            $documento['Encabezado']['Transporte']['Aduana']['TipoBultos']['IdContainer'] = 'ABC123';
                            $documento['Encabezado']['Transporte']['Aduana']['TipoBultos']['Sello'] = '10973348-2';
                            $documento['Encabezado']['Transporte']['Aduana']['TipoBultos']['EmisorSello'] = 'Sellos de Chile';
                        }
                    }
                    if (empty($documento['Encabezado']['Transporte']['Aduana'])) {
                        unset($documento['Encabezado']['Transporte']);
                    }
                    // agregar moneda a los totales
                    if (!empty($caso['exportacion']['MONEDA DE LA OPERACION'])) {
                        $documento['Encabezado']['Totales']['TpoMoneda'] = $caso['exportacion']['MONEDA DE LA OPERACION'];
                        unset($caso['exportacion']['MONEDA DE LA OPERACION']);
                    }
                }
                // agregar indicador de servicio
                if (isset($caso['detalle'])) {
                    foreach ($caso['detalle'] as $item) {
                        if ($item['ITEM']=='ASESORIAS Y PROYECTOS PROFESIONALES') {
                            $documento['Encabezado']['IdDoc']['IndServicio'] = 3;
                            break;
                        } else if ($item['ITEM']=='ALOJAMIENTO HABITACIONES') {
                            $documento['Encabezado']['IdDoc']['IndServicio'] = 4;
                            break;
                        }
                    }
                }
                // agregar tipo de cambio para colocar el valor en CLP
                $documento['Encabezado']['OtraMoneda'] = [
                    'TpoMoneda' => 'PESO CL',
                    'TpoCambio' => 500,
                ];
            }
            // agregar detalle del documento si fue pasado explícitamente
            if (isset($caso['detalle'])) {
                $documento['Detalle'] = [];
                foreach ($caso['detalle'] as $item) {
                    // generar detalle del item
                    $detalle = [];
                    foreach ($item as $col => $val) {
                        $col = self::$item_cols[$col];
                        // procesar cada valor de acuerdo al nombre de la columna
                        if (in_array($col, ['DescuentoPct', 'RecargoPct']))
                            $detalle[$col] = substr($val, 0, -1);
                        else
                            $detalle[$col] = utf8_encode($val); // se convierte de ISO-8859-1 a UTF-8
                    }
                    // si el item es EXENTO se agrega campo que lo indica
                    if (strpos($detalle['NmbItem'], 'EXENTO'))
                        $detalle['IndExe'] = 1;
                    // si hay una referencia se completa con los campos del
                    // detalle de la referencia que no estén en este detalle
                    if (!empty($caso['referencia'])) {
                        // buscar el caso y copiar sus columnas que no estén
                        $detalle_r = $documentos[$caso['referencia']['caso']]['Detalle'];
                        $n_detalle_r = count($detalle_r);
                        for ($i=0; $i<$n_detalle_r; $i++) {
                            if ($detalle_r[$i]['NmbItem']==$detalle['NmbItem']) {
                                foreach ($detalle_r[$i] as $attr => $val) {
                                    if (!isset($detalle[$attr]))
                                        $detalle[$attr] = $val;
                                }
                            }
                        }
                        // si la referencia es a una factura exenta y existe un
                        // precio entonces se marca como exento el item
                        if ($documentos[$caso['referencia']['caso']]['Encabezado']['IdDoc']['TipoDTE']==34 and isset($detalle['PrcItem'])) {
                            $detalle['IndExe'] = 1;
                        }
                    }
                    // si es factura de compra se agrega código de retención
                    if ($TipoDTE==46) {
                        if (strpos($detalle['NmbItem'], 'PPA')) {
                            $detalle['CdgItem'] = 3900;
                            $detalle['Retenedor'] = true;
                            $detalle['CodImpAdic'] = 39;
                        } else {
                            $detalle['CodImpAdic'] = 15;
                        }
                    }
                    // agregar detalle del item a los detalles
                    $documento['Detalle'][] = $detalle;
                }
            }
            // si no fue pasado explícitamente aun puede haber detalle: el de
            // otro caso que se esté referenciando
            else if (!empty($caso['referencia'])) {
                $referencia = self::getReferencia($caso['referencia']['razon']);
                // copiar todos los detalles
                if ($referencia['detalle']==1) {
                    $documento['Detalle'] = $documentos[$caso['referencia']['caso']]['Detalle'];
                }
                // copiar sólo el nombre del primer item
                else if ($referencia['detalle']==2) {
                    $documento['Detalle'] = [
                        [
                            'NmbItem' => $documentos[$caso['referencia']['caso']]['Detalle'][0]['NmbItem'],
                        ]
                    ];
                }
            }
            // si es documento de exportación y es referencia, se copian los datos de transporte
            if (in_array($TipoDTE, [111, 112]) and !empty($caso['referencia'])) {
                $documento['Encabezado']['Transporte'] = $documentos[$caso['referencia']['caso']]['Encabezado']['Transporte'];
            }
            // agregar descuento del documento
            $documento['DscRcgGlobal'] = [];
            if (!empty($caso['descuento'])) {
                $documento['DscRcgGlobal'][] = [
                    'TpoMov' => 'D',
                    'TpoValor' => '%',
                    'ValorDR' => substr($caso['descuento'], 0, -1),
                ];
            }
            // agregar recargo total clausula
            if (!empty($caso['recargo-total-clausula'])) {
                $documento['DscRcgGlobal'][] = [
                    'TpoMov' => 'R',
                    'TpoValor' => '$',
                    'ValorDR' => round((substr($caso['recargo-total-clausula'], 0, -1)/100) * $documento['Encabezado']['Transporte']['Aduana']['TotClauVenta'], 2),
                ];
            }
            // agregar recargo por flete y/o seguro, se agrega sólo en factura
            // ya que las notas de crédito y débito de los SETs no consideran
            // estos recargos
            if ($documento['Encabezado']['IdDoc']['TipoDTE']==110) {
                foreach (['MntFlete', 'MntSeguro'] as $recargo_aduana) {
                    if (!empty($documento['Encabezado']['Transporte']['Aduana'][$recargo_aduana])) {
                        $documento['DscRcgGlobal'][] = [
                            'TpoMov' => 'R',
                            'TpoValor' => '$',
                            'ValorDR' => $documento['Encabezado']['Transporte']['Aduana'][$recargo_aduana],
                        ];
                    }
                }
            }
            // agregar recargo del documento
            if (!empty($caso['recargo'])) {
                $documento['DscRcgGlobal'][] = [
                    'TpoMov' => 'R',
                    'TpoValor' => '%',
                    'ValorDR' => substr($caso['recargo'], 0, -1),
                ];
            }
            if (empty($documento['DscRcgGlobal']))
                unset($documento['DscRcgGlobal']);
            // agregar descuento del documento de la referencia
            else if (!empty($caso['referencia'])) {
                $referencia = self::getReferencia($caso['referencia']['razon']);
                if ($referencia['codigo']===1 and isset($documentos[$caso['referencia']['caso']]['DscRcgGlobal'])) {
                    $documento['DscRcgGlobal'] = $documentos[$caso['referencia']['caso']]['DscRcgGlobal'];
                }
            }
            // agregar referencia obligatoria
            $documento['Referencia'] = [];
            $documento['Referencia'][] = [
                'TpoDocRef' => 'SET',
                'FolioRef' => $folios[$TipoDTE],
                'RazonRef' => 'CASO '.$caso['caso'],
            ];
            // agregar referencias del caso
            if (!empty($caso['referencia'])) {
                $referencia = self::getReferencia($caso['referencia']['razon']);
                // agregar referencia
                $documento['Referencia'][] = [
                    'TpoDocRef' => $documentos[$caso['referencia']['caso']]['Encabezado']['IdDoc']['TipoDTE'],
                    'FolioRef' => $documentos[$caso['referencia']['caso']]['Encabezado']['IdDoc']['Folio'],
                    'CodRef' => $referencia['codigo'],
                    'RazonRef' => $caso['referencia']['razon'],
                ];
                // si la referencia es corrige giro se asigna automáticamente la corrección
                if (isset($documento['Referencia'][1]['RazonRef']) and strpos($documento['Referencia'][1]['RazonRef'], 'CORRIGE GIRO')===0) {
                    $documento['Detalle'][0]['NmbItem'] = 'DONDE DICE Servicios integrales de informática DEBE DECIR Informática';
                }
                // agregar totales
                $documento['Encabezado']['Totales'] = $referencia['Totales'];
                // agregar tasa de IVA si corresponde
                if (isset($documento['Encabezado']['Totales']['TasaIVA']))
                    $documento['Encabezado']['Totales']['TasaIVA'] = \sasco\LibreDTE\Sii::getIVA();
                // si el documento referenciado es factura exenta y hay MntExe
                if (isset($documento['Encabezado']['Totales']['MntExe'])) {
                    if ($documentos[$caso['referencia']['caso']]['Encabezado']['IdDoc']['TipoDTE']==34)
                        $documento['Encabezado']['Totales']['MntExe'] = 0;
                    else
                        unset($documento['Encabezado']['Totales']['MntExe']);
                }
                // si es documento de exportación se resetean los totales y se copia el tipo de moneda si no existe
                if (in_array($documento['Encabezado']['IdDoc']['TipoDTE'], [111, 112])) {
                    if (!empty($documento['Encabezado']['Totales']['TpoMoneda'])) {
                        $documento['Encabezado']['Totales'] = ['TpoMoneda'=>$documento['Encabezado']['Totales']['TpoMoneda']];
                    } else {
                        $documento['Encabezado']['Totales'] = ['TpoMoneda'=>$documentos[$caso['referencia']['caso']]['Encabezado']['Totales']['TpoMoneda']];
                    }
                }
            }
            // agregar referencia de exportación si existe
            if (!empty($caso['exportacion']['REFERENCIA'])) {
                if (!is_array($caso['exportacion']['REFERENCIA']))
                    $caso['exportacion']['REFERENCIA'] = [$caso['exportacion']['REFERENCIA']];
                foreach ($caso['exportacion']['REFERENCIA'] as $ref) {
                    $documento['Referencia'][] = [
                        'TpoDocRef' => self::$referencias_exportacion[$ref],
                        'FolioRef' => substr(md5($ref), 0, 18),
                    ];
                }
                unset($caso['exportacion']['REFERENCIA']);
            }
            // si es servicio de hotelería se crea referencia para el pasaporte
            if (isset($documento['Encabezado']['IdDoc']['IndServicio']) and $documento['Encabezado']['IdDoc']['IndServicio']==4) {
                $documento['Referencia'][] = [
                    'TpoDocRef' => 813,
                    'FolioRef' => 'E12345',
                ];
            }
            // si hay Totales pero no hay valores en los detalles entonces se cambia a sólo Totales de MntTotal = 0
            if (isset($documento['Encabezado']['Totales'])) {
                $hayValor = false;
                foreach ($documento['Detalle'] as $d) {
                    if (!empty($d['PrcItem']))
                        $hayValor = true;
                }
                if (!$hayValor) {
                    if (isset($documento['Encabezado']['Totales']['MntExe']))
                        $documento['Encabezado']['Totales'] = ['MntExe'=>0];
                    else
                        $documento['Encabezado']['Totales'] = [];
                    $documento['Encabezado']['Totales']['MntTotal'] = 0;
                }
            }
            // agregar documento a los documentos
            $documentos[$caso['caso']] = $documento;
            // pasar al siguiente folio de este tipo;
            $folios[$TipoDTE]++;
        }
        // pasar de índice "número de caso" a índice numérico, o sea
        // pasar de diccionario o hash a arreglo antes convertir a JSON
        $aux = $documentos;
        $documentos = [];
        foreach ($aux as $d) {
            $documentos[] = $d;
        }
        // retornar documentos
        return json_encode($documentos, JSON_PRETTY_PRINT);
    }

    /**
     * Método que procesa el contenido de la archivo y entrega un arreglo con
     * cada uno de los casos procesados (tipo de documento, referencias, detalle
     * y otros, como descuentos globales)
     * @param archivo Contenido del archivo del set de set de pruebas
     * @param separador usado en el archivo para los casos (son los "=" debajo del título del caso)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-04
     */
    private static function parse($archivo, $separador)
    {
        // obtener cada caso en un arreglo con su título
        $casos = explode($separador, $archivo);
        $separador_len = strlen($separador);
        $n_casos = count($casos);
        for ($i=1; $i<$n_casos; $i++) {
            $caso = trim($casos[$i]);
            $caso_anterior = trim($casos[$i-1]);
            $caso_titulo = substr($caso_anterior, -$separador_len);
            $casos[$i] = $caso_titulo."\n".$caso;
            $casos[$i-1] = trim(str_replace($caso_titulo, '', $caso_anterior));
        }
        array_shift($casos);
        // casos
        $set_pruebas = [];
        foreach ($casos as $caso) {
            $lineas = array_map('trim', explode("\n", $caso));
            $datos = [];
            // obtener número de caso
            $aux = explode(' ', $lineas[0]);
            $datos['caso'] = array_pop($aux);
            // obtener tipo de documento
            $aux = explode("\t", $lineas[1]);
            $datos['documento'] = array_pop($aux);
            // procesar datos antes de detalle si es guía de despacho
            if ($datos['documento']=='GUIA DE DESPACHO') {
                $aux = explode("\t", $lineas[2]);
                $datos['motivo'] = array_pop($aux);
                // si hay contenido en línea 4 entonces hay TRASLADO POR
                if (!empty($lineas[3])) {
                    $aux = explode("\t", $lineas[3]);
                    $datos['traslado_por'] = array_pop($aux);
                    $linea_titulos_detalles = 5;
                } else {
                    $linea_titulos_detalles = 4;
                }
            }
            // si no es guía de despacho entonces obtener referencia si existe
            // (si hay línea 3 con contenido entonces hay una referencia)
            else {
                if (!empty($lineas[2])) {
                    $aux = explode("\t", $lineas[2]);
                    $referencia = array_pop($aux);
                    $aux = explode(' ', $referencia);
                    $caso_referencia = array_pop($aux);
                    $aux = explode("\t", $lineas[3]);
                    $razon = array_pop($aux);
                    $datos['referencia'] = [
                        'caso' => $caso_referencia,
                        'razon' => $razon,
                    ];
                    $linea_titulos_detalles = 5;
                } else {
                    $linea_titulos_detalles = 3;
                }
            }
            // sólo continuar si hay más líneas, ya que serán detalles
            if (isset($lineas[$linea_titulos_detalles])) {
                // extraer titulos de detalles
                $titulos = array_slice(array_filter(explode("\t", $lineas[$linea_titulos_detalles])), 0);
                // extraer detalles
                $datos['detalle'] = [];
                $i = $linea_titulos_detalles + 1;
                while (!empty($lineas[$i])) {
                    $item = array_slice(array_filter(explode("\t", $lineas[$i])), 0);
                    $n_item = count($item);
                    $detalle = [];
                    foreach ($titulos as $key => $titulo) {
                        $detalle[$titulo] = trim($item[$key]);
                    }
                    $datos['detalle'][] = $detalle;
                    $i++;
                }
                // sólo continuar si hay más líneas, será, por ej, el descuento global
                if (isset($lineas[$i])) {
                    $n_lineas = count($lineas);
                    for ($i=$i; $i<$n_lineas; $i++) {
                        // si la línea está vacía se omite
                        if (!$lineas[$i])
                            continue;
                        // si hay descuento global se guarda
                        if (strpos($lineas[$i], 'DESCUENTO GLOBAL ITEMES AFECTOS')===0) {
                            $aux = explode("\t", $lineas[$i]);
                            $datos['descuento'] = trim(array_pop($aux));
                        }
                        // si es factura de exportación las líneas podrían ser los datos de la factura
                        if ($datos['documento']=='FACTURA DE EXPORTACION ELECTRONICA') {
                            // recargo en item formato %VAL TIPO
                            if ($lineas[$i][0]=='%') {
                                if (strpos($lineas[$i], 'RECARGO EN LA LINEA DE ITEM')) {
                                    $recargo = +substr($lineas[$i], 1, strpos($lineas[$i], ' ')-1);
                                    foreach ($datos['detalle'] as &$d) {
                                        $d['RECARGO ITEM'] = $recargo.'%';
                                    }
                                }
                            }
                            // datos de exportación formato VAR:VAL
                            else {
                                $aux = explode(':', $lineas[$i]);
                                $val = trim(array_pop($aux));
                                //$var = strtolower(str_replace(' ', '_', trim(array_pop($aux), " \t\n\r\0\x0B(*)")));
                                $var = trim(array_pop($aux));
                                // comisión en extranjero, recargo global
                                if ($var=='COMISIONES EN EL EXTRANJERO (RECARGOS GLOBALES)') {
                                    if (strpos($val, 'DEL TOTAL DE LA CLAUSULA')) {
                                        $datos['recargo-total-clausula'] = substr($val, 0, strpos($val, '%')+1);
                                    }
                                }
                                // descuento en alguna línea de detalle
                                else if (strpos($var, 'DESCUENTO LINEA #')===0) {
                                    $linea = (int)substr($var, strpos($var, '#')+1);
                                    $datos['detalle'][$linea-1]['DESCUENTO ITEM'] = $val;
                                }
                                // agregar a los datos de aduanas
                                else {
                                    if (!isset($datos['exportacion']))
                                        $datos['exportacion'] = [];
                                    if (!isset($datos['exportacion'][$var])) {
                                        $datos['exportacion'][$var] = $val;
                                    } else {
                                        if (!is_array($datos['exportacion'][$var]))
                                            $datos['exportacion'][$var] = [$datos['exportacion'][$var]];
                                        $datos['exportacion'][$var][] = $val;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $set_pruebas[$datos['caso']] = $datos;
        }
        // entregar datos set de prueba
        return $set_pruebas;
    }

    /**
     * Método que recupera los datos para crear la referencia del documento
     * @param razon Raón de la referencia con texto al inicio: ANULA, DEVOLUCON O CORRIGE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-13
     */
    private static function getReferencia($razon)
    {
        list($razon, $null) = explode(' ', $razon, 2);
        return self::$referencias[strtoupper(trim($razon))];
    }

}
