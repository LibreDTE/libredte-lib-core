<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Form;

use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Contract\EntityComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Estrategia "billing.document.parser.strategy:form.estandar".
 *
 * Transforma los datos recibidos a través de un formulario de la vista estándar
 * de emisión de DTE a un arreglo PHP con la estructura oficial del SII.
 */
class EstandarParserStrategy extends AbstractStrategy implements ParserStrategyInterface
{
    /**
     * Constructor de la estrategia con sus dependencias.
     *
     * @param EntityComponentInterface $entityComponent
     */
    public function __construct(
        private EntityComponentInterface $entityComponent
    ) {
    }

    /**
     * Realiza la transformación de los datos del documento.
     *
     * @param string|array $data Datos de entrada del formulario.
     * @return array Arreglo transformado a la estructura oficial del SII.
     */
    public function parse(string|array $data): array
    {
        // Decodificar datos si vienen en formato YAML.
        $data = $this->decodeData($data);

        // Validar datos mínimos requeridos.
        $this->validateMinimalData($data);

        // Inicializar los datos del DTE.
        $dte = [];
        $this->setInitialDTE($data, $dte);

        // Procesar los datos adicionales del DTE.
        $this->processDTEData($data, $dte);

        // Retornar el DTE procesado.
        return $dte;
    }

    /**
     * Decodifica datos YAML o los retorna directamente si ya son un arreglo.
     *
     * Verifica si los datos son una cadena YAML y los decodifica a un arreglo
     * asociativo. Si los datos ya son un arreglo, los retorna sin cambios.
     *
     * @param string|array $data Datos a procesar. Puede ser YAML o un arreglo.
     * @return array Datos decodificados como arreglo asociativo.
     * @throws ParserException Si la cadena YAML proporcionada no es válida.
     */
    private function decodeData(string|array $data): array
    {
        // Retornar los datos directamente si ya son un arreglo.
        if (is_array($data)) {
            return $data;
        }

        // Parsar los datos en string como una cadena YAML.
        try {
            $decoded = Yaml::parse($data);
        } catch (ParseException $e) {
            throw new ParserException(sprintf(
                'El YAML proporcionado es inválido: %s',
                $e->getMessage()
            ));
        }

        return $decoded;
    }

    /**
     * Procesa los datos de un DTE y los agrega a la estructura proporcionada.
     *
     * Este método realiza diversas operaciones sobre los datos del DTE, como la
     * adición de pagos programados, datos de traslado, detalles y referencias.
     *
     * @param array $data Datos del DTE a procesar.
     * @param array &$dte Referencia al arreglo del DTE donde se agregarán los
     * datos.
     *
     * @return void
     */
    private function processDTEData(array $data, array &$dte): void
    {
        // Agregar pagos programados.
        $this->addScheduledPayment($data, $dte);

        // Agregar datos de traslado.
        $this->addTransferData($data, $dte);

        // Agregar indicador de servicio.
        $this->addServiceIndicator($data, $dte);

        // Agregar datos de exportación.
        $this->addExportData($data, $dte);

        // Agregar detalles y obtener valores afectos y exentos.
        [$n_itemAfecto, $n_itemExento] = $this->addDetails($data, $dte);

        // Procesar impuestos adicionales.
        $this->addAdditionalTaxes($data, $dte);

        // Procesar empresa constructora.
        $this->addConstructionCompany($data, $dte);

        // Agregar descuentos globales con valores afectos y exentos.
        $this->addGlobalDiscounts($data, $dte, $n_itemAfecto, $n_itemExento);

        // Agregar referencias.
        $this->addReferences($data, $dte);
    }

    /**
     * Valida los datos mínimos requeridos para procesar el documento.
     *
     * Este método verifica que se proporcionen los datos mínimos necesarios
     * para generar el documento. Si falta algún dato obligatorio, lanza una
     * excepción.
     *
     * Reglas de validación:
     *
     *   - Si los datos están vacíos, no se permite acceso directo.
     *   - El tipo de documento (`TpoDoc`) es obligatorio.
     *   - Los campos mínimos varían según el tipo de documento.
     *
     * @param array $data Datos del formulario enviados para procesar el
     * documento.
     * @throws ParserException Si falta algún dato obligatorio o el acceso es
     * directo.
     */
    private function validateMinimalData(array $data): void
    {
        // Si no se proporcionan datos, lanza una excepción indicando.
        if (empty($data)) {
            throw new ParserException(
                'No puede acceder de forma directa a la previsualización.'
            );
        }

        // Verificar que se haya indicado el tipo de documento.
        if (empty($data['TpoDoc'])) {
            throw new ParserException(
                'Debe indicar el tipo de documento a emitir.'
            );
        }

        // Definir los campos mínimos requeridos.
        $datos_minimos = [
            'FchEmis',
            'GiroEmis',
            'Acteco',
            // 'DirOrigen', // Nota: Se de debe pasar en los datos.
            // 'CmnaOrigen', // Nota: Se de debe pasar en los datos.
            'RUTRecep',
            'RznSocRecep',
            'DirRecep',
            'NmbItem',
        ];

        // Para ciertos tipos de documento, se requieren campos adicionales.
        if (!in_array($data['TpoDoc'], [56, 61, 110, 111, 112])) {
            $datos_minimos[] = 'GiroRecep';
            $datos_minimos[] = 'CmnaRecep';
        }

        // Validar que todos los campos mínimos estén presentes.
        foreach ($datos_minimos as $attr) {
            if (empty($data[$attr])) {
                throw new ParserException(sprintf(
                    'Error al recibir campos mínimos, falta: %s.',
                    $attr
                ));
            }
        }
    }

    /**
     * Crea la estructura inicial del DTE.
     *
     * Este método genera un arreglo con la estructura inicial del DTE,
     * incluyendo información del encabezado, emisor, receptor y otros datos
     * generales que se requieren para procesar el documento.
     *
     * Estructura del DTE:
     * - Encabezado:
     *   - IdDoc: Información del documento, como tipo y folio.
     *   - Emisor: Detalles del emisor, como RUT y dirección.
     *   - Receptor: Detalles del receptor, como RUT y razón social.
     *   - RUTSolicita: Información del solicitante, si aplica.
     *
     * @param array $data Datos de entrada para generar la estructura del DTE.
     * @param array &$dte Referencia donde se almacenará la estructura generada.
     *
     * @return void
     */
    private function setInitialDTE(array $data, array &$dte): void
    {
        // Crear la estructura base del DTE.
        $dte = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => $data['TpoDoc'],
                    'Folio' => !empty($data['Folio'])
                        ? $data['Folio']
                        : false
                    ,
                    'FchEmis' => $data['FchEmis'],
                    'TpoTranCompra' => !empty($data['TpoTranCompra'])
                        ? $data['TpoTranCompra']
                        : false
                    ,
                    'TpoTranVenta' => !empty($data['TpoTranVenta'])
                        ? $data['TpoTranVenta']
                        : false
                    ,
                    'FmaPago' => !empty($data['FmaPago'])
                        ? $data['FmaPago']
                        : false
                    ,
                    'FchCancel' => $data['FchVenc'] < $data['FchEmis']
                        ? $data['FchVenc']
                        : false
                    ,
                    'PeriodoDesde' => !empty($data['PeriodoDesde'])
                        ? $data['PeriodoDesde']
                        : false
                    ,
                    'PeriodoHasta' => !empty($data['PeriodoHasta'])
                        ? $data['PeriodoHasta']
                        : false
                    ,
                    'MedioPago' => !empty($data['MedioPago'])
                        ? $data['MedioPago']
                        : false
                    ,
                    'TpoCtaPago' => !empty($data['TpoCtaPago'])
                        ? $data['TpoCtaPago']
                        : false
                    ,
                    'NumCtaPago' => !empty($data['NumCtaPago'])
                        ? $data['NumCtaPago']
                        : false
                    ,
                    'BcoPago' => !empty($data['BcoPago'])
                        ? $data['BcoPago']
                        : false
                    ,
                    'TermPagoGlosa' => !empty($data['TermPagoGlosa'])
                        ? $data['TermPagoGlosa']
                        : false
                    ,
                    'FchVenc' => $data['FchVenc'] > $data['FchEmis']
                        ? $data['FchVenc']
                        : false
                    ,
                ],
                'Emisor' => [
                    'RUTEmisor' => !empty($data['RUTEmisor'])
                        ? $data['RUTEmisor']
                        : false
                    ,
                    'RznSoc' => (
                        $data['RznSoc']
                        ?? $data['RznSocEmisor']
                        ?? null
                    ) ?: false,
                    'GiroEmis' => (
                        $data['GiroEmis']
                        ?? $data['GiroEmisor']
                        ?? null
                    ) ?: false,
                    'Telefono' => !empty($data['TelefonoEmisor'])
                        ? $data['TelefonoEmisor']
                        : false
                    ,
                    'CorreoEmisor' => !empty($data['CorreoEmisor'])
                        ? $data['CorreoEmisor']
                        : false
                    ,
                    'Acteco' => $data['Acteco'],
                    'CdgSIISucur' => $data['CdgSIISucur']
                        ? $data['CdgSIISucur']
                        : false
                    ,
                    'DirOrigen' => !empty($data['DirOrigen'])
                        ? $data['DirOrigen']
                        : false
                    ,
                    'CmnaOrigen' => !empty($data['CmnaOrigen'])
                        ? $data['CmnaOrigen']
                        : false
                    ,
                    'CdgVendedor' => $data['CdgVendedor']
                        ? $data['CdgVendedor']
                        : false
                    ,
                ],
                'Receptor' => [
                    'RUTRecep' => !empty($data['RUTRecep'])
                        ? $data['RUTRecep']
                        : false
                    ,
                    'CdgIntRecep' => !empty($data['CdgIntRecep'])
                        ? $data['CdgIntRecep']
                        : false
                    ,
                    'RznSocRecep' => !empty($data['RznSocRecep'])
                        ? $data['RznSocRecep']
                        : false
                    ,
                    'GiroRecep' => !empty($data['GiroRecep'])
                        ? $data['GiroRecep']
                        : false
                    ,
                    'Contacto' => !empty($data['Contacto'])
                        ? $data['Contacto']
                        : false
                    ,
                    'CorreoRecep' => !empty($data['CorreoRecep'])
                        ? $data['CorreoRecep']
                        : false
                    ,
                    'DirRecep' => !empty($data['DirRecep'])
                        ? $data['DirRecep']
                        : false
                    ,
                    'CmnaRecep' => !empty($data['CmnaRecep'])
                        ? $data['CmnaRecep']
                        : false
                    ,
                    'CiudadRecep' => !empty($data['CiudadRecep'])
                        ? $data['CiudadRecep']
                        : false
                    ,
                ],
                'RUTSolicita' => !empty($data['RUTSolicita'])
                    ? str_replace('.', '', $data['RUTSolicita'])
                    : false
                ,
            ],
        ];
    }

    /**
     * Agrega información de pagos programados al DTE.
     *
     * Este método verifica si la forma de pago es una venta a crédito
     * (`FmaPago == 2`) y, si no es una boleta, añade información de los
     * pagos programados al DTE.
     *
     * Comportamiento:
     *
     *   - Si no hay pagos definidos, usa la fecha de vencimiento (`FchVenc`) y
     *     añade una glosa indicando que la fecha de pago es igual al
     *     vencimiento.
     *   - Si hay pagos definidos, procesa las fechas, montos y glosas de los
     *     pagos.
     *
     * @param array $data Datos de entrada que incluyen información de pagos.
     * @param array $dte  Arreglo del DTE en el cual se agregarán los pagos.
     *
     * @return void
     */
    private function addScheduledPayment(array $data, array &$dte): void
    {
        // Agregar pagos programados si es venta a crédito y no es boleta.
        if (
            $data['FmaPago'] == 2
            && !in_array($dte['Encabezado']['IdDoc']['TipoDTE'], [39, 41])
        ) {
            // Si no hay pagos explícitos se copia la fecha de vencimiento y el
            // monto total se determinará en el proceso de normalización
            if (empty($data['FchPago'])) {
                if ($data['FchVenc'] > $data['FchEmis']) {
                    $dte['Encabezado']['IdDoc']['MntPagos'] = [
                        'FchPago' => $data['FchVenc'],
                        'GlosaPagos' => 'Fecha de pago igual al vencimiento',
                    ];
                }
            }
            // Si hay montos a pagar programados explícitamente.
            else {
                $dte['Encabezado']['IdDoc']['MntPagos'] = [];
                $n_pagos = count($data['FchPago']);
                for ($i = 0; $i < $n_pagos; $i++) {
                    $dte['Encabezado']['IdDoc']['MntPagos'][] = [
                        'FchPago' => $data['FchPago'][$i],
                        'MntPago' => $data['MntPago'][$i],
                        'GlosaPagos' => !empty($data['GlosaPagos'][$i])
                            ? $data['GlosaPagos'][$i]
                            : false,
                    ];
                }
            }
        }
    }

    /**
     * Agrega datos de traslado al DTE.
     *
     * Verifica si el documento es una guía de despacho (`TipoDTE == 52`).
     * Si es así, añade detalles de traslado, como patente, transportista,
     * chofer y destino.
     *
     * Comportamiento:
     *
     *   - Si se especifica `IndTraslado`, este se agrega al DTE.
     *   - Si hay información de transporte (patente, transportista, chofer o
     *     destino), esta se incluye en la sección de transporte del DTE.
     *
     * @param array $data Datos de entrada que incluyen información de traslado.
     * @param array $dte  Arreglo del DTE al cual se agregará la información.
     *
     * @return void
     */
    private function addTransferData(array $data, array &$dte): void
    {
        // Si no es guía de despacho no se agregan los datos de transporte.
        if ($dte['Encabezado']['IdDoc']['TipoDTE'] != 52) {
            return;
        }

        // Si no hay información relevante de transporte se retorna.
        $dte['Encabezado']['IdDoc']['IndTraslado'] = $data['IndTraslado'];
        if (!(
            !empty($data['Patente'])
            || !empty($data['RUTTrans'])
            || (!empty($data['RUTChofer']) && !empty($data['NombreChofer']))
            || !empty($data['DirDest'])
            || !empty($data['CmnaDest'])
        )) {
            return;
        }

        // Añadir la información de transporte.
        $dte['Encabezado']['Transporte'] = [
            'Patente' => !empty($data['Patente'])
                ? $data['Patente']
                : false,
            'RUTTrans' => !empty($data['RUTTrans'])
                ? str_replace('.', '', $data['RUTTrans'])
                : false,
            'Chofer' => (
                !empty($data['RUTChofer']) && !empty($data['NombreChofer'])
            ) ? [
                    'RUTChofer' => str_replace('.', '', $data['RUTChofer']),
                    'NombreChofer' => $data['NombreChofer'],
                ]
                : false,
            'DirDest' => !empty($data['DirDest'])
                ? $data['DirDest']
                : false,
            'CmnaDest' => !empty($data['CmnaDest'])
                ? $data['CmnaDest']
                : false,
        ];
    }

    /**
     * Agrega el indicador de servicio al DTE.
     *
     * Procesa `IndServicio` para determinar si debe incluirse en el DTE,
     * ajustándolo según el tipo de documento (`TipoDTE`) y validando su valor.
     *
     * Comportamiento:
     *
     *   - Si el DTE es una boleta (39 o 41), invierte los valores 1 y 2 de
     *     `IndServicio`.
     *   - Valida el valor de `IndServicio` según el tipo de documento
     *     (`TipoDTE`).
     *   - Asigna el indicador de servicio al DTE solo si el valor es válido.
     *
     * @param array $data Datos de entrada que incluyen el indicador de
     * servicio.
     * @param array $dte  Arreglo del DTE al cual se agregará el indicador.
     *
     * @return void
     */
    private function addServiceIndicator(array $data, array &$dte): void
    {
        // Si no hay indicador de servicio se retorna.
        if (empty($data['IndServicio'])) {
            return;
        }

        // Cambiar el tipo de indicador en boletas
        // (Valores invertidos respecto a facturas).
        if (in_array($dte['Encabezado']['IdDoc']['TipoDTE'], [39, 41])) {
            if ($data['IndServicio'] == 1) {
                $data['IndServicio'] = 2;
            } elseif ($data['IndServicio'] == 2) {
                $data['IndServicio'] = 1;
            }
        }

        // Quitar indicador de servicio si se pasó para un tipo de documento que
        // no corresponde.
        if (in_array($dte['Encabezado']['IdDoc']['TipoDTE'], [39, 41])) {
            if (!in_array($data['IndServicio'], [1, 2, 3, 4])) {
                $data['IndServicio'] = false;
            }
        } elseif (
            in_array($dte['Encabezado']['IdDoc']['TipoDTE'], [110, 111, 112])
        ) {
            if (!in_array($data['IndServicio'], [1, 3, 4, 5])) {
                $data['IndServicio'] = false;
            }
        } else {
            if (!in_array($data['IndServicio'], [1, 2, 3])) {
                $data['IndServicio'] = false;
            }
        }

        // Asignar el indicador de servicio al DTE si es válido.
        if ($data['IndServicio']) {
            $dte['Encabezado']['IdDoc']['IndServicio'] = $data['IndServicio'];
        }
    }

    /**
     * Agrega información de exportación al DTE.
     *
     * Verifica si el tipo de documento es de exportación (`TipoDTE` 110, 111 o
     * 112).
     *
     * Si corresponde, añade datos como la identificación del receptor
     * extranjero, moneda y tipo de cambio.
     *
     * Comportamiento:
     *
     *   - Añade el número de identificación (`NumId`) y nacionalidad del
     *     receptor.
     *   - Establece el tipo de moneda (`TpoMoneda`) y, si aplica, el tipo de
     *     cambio (`TpoCambio`).
     *
     * @param array $data Datos que incluyen información de exportación.
     * @param array $dte  Estructura del DTE donde se agregarán los datos.
     *
     * @return void
     */
    private function addExportData(array $data, array &$dte): void
    {
        // Si no es documento de exportación se retorna.
        if (!in_array($dte['Encabezado']['IdDoc']['TipoDTE'], [110, 111, 112])) {
            return;
        }

        // Agregar datos de exportación.
        if (!empty($data['NumId'])) {
            $dte['Encabezado']['Receptor']['Extranjero']['NumId'] =
                $data['NumId']
            ;
        }
        if (!empty($data['Nacionalidad'])) {
            $dte['Encabezado']['Receptor']['Extranjero']['Nacionalidad'] =
                $data['Nacionalidad']
            ;
        }
        $dte['Encabezado']['Totales']['TpoMoneda'] = $data['TpoMoneda'];
        if (!empty($data['TpoCambio'])) {
            $dte['Encabezado']['OtraMoneda'] = [
                'TpoMoneda' => 'PESO CL',
                'TpoCambio' => (float)$data['TpoCambio'],
            ];
        }
    }

    /**
     * Agrega detalles al DTE.
     *
     * Procesa y agrega los detalles del documento, como información de los
     * ítems, precios, impuestos, descuentos, entre otros. Clasifica los ítems
     * en afectos o exentos según sus características.
     *
     * Comportamiento:
     *
     *   - Procesa cada ítem verificando códigos, impuestos y descuentos.
     *   - Si el documento es una boleta (`TipoDTE == 39`), ajusta precios y
     *     descuentos incluyendo el IVA.
     *   - Valida que las boletas no contengan impuestos adicionales.
     *   - Clasifica los ítems como afectos o exentos.
     *
     * @param array $data Datos que incluyen los detalles de los ítems.
     * @param array $dte  Estructura del DTE donde se agregarán los detalles.
     *
     * @return array Arreglo con:
     *   - El número de ítems afectos.
     *   - El número de ítems exentos.
     *
     * @throws ParserException Si se detectan impuestos adicionales en una
     * boleta.
     */
    private function addDetails(array $data, array &$dte): array
    {
        // Inicializar contadores y lista de detalles.
        $n_detalles = count($data['NmbItem']);
        $dte['Detalle'] = [];
        $n_itemAfecto = 0;
        $n_itemExento = 0;

        // Obtener el IVA.
        $iva_sii = $this->getTax($data['TpoDoc']);

        // Procesar cada ítem.
        for ($i = 0; $i < $n_detalles; $i++) {
            $detalle = [];

            // Agregar código del ítem.
            if (!empty($data['VlrCodigo'][$i])) {
                if (!empty($data['TpoCodigo'][$i])) {
                    $TpoCodigo = $data['TpoCodigo'][$i];
                } else {
                    $TpoCodigo = 'INT1';
                }
                $detalle['CdgItem'] = [
                    'TpoCodigo' => $TpoCodigo,
                    'VlrCodigo' => $data['VlrCodigo'][$i],
                ];
            }

            // Agregar otros datos del ítem.
            $datos = [
                'IndExe',
                'NmbItem',
                'DscItem',
                'QtyItem',
                'UnmdItem',
                'PrcItem',
                'CodImpAdic',
            ];
            foreach ($datos as $d) {
                if (isset($data[$d][$i])) {
                    $valor = trim((string) $data[$d][$i]);
                    if (!empty($valor)) {
                        $detalle[$d] = is_numeric($valor)
                            ? (float) $valor
                            : $valor
                        ;
                    }
                }
            }

            // Si es boleta y el ítem no es exento, agregar IVA al precio.
            if (
                $dte['Encabezado']['IdDoc']['TipoDTE'] == 39
                && (!isset($detalle['IndExe']) || $detalle['IndExe'] == false)
            ) {
                // IVA.
                $iva = round((float) $detalle['PrcItem'] * ($iva_sii / 100));

                // Impuesto adicional (no se permiten impuestos adicionales en
                // boletas).
                if (!empty($detalle['CodImpAdic'])) {
                    throw new ParserException(
                        'No es posible generar una boleta que tenga impuestos '.
                        'adicionales mediante LibreDTE. '.
                        'Este es un caso de uso no considerado.',
                    );
                } else {
                    $adicional = 0;
                }

                // Agregar al precio.
                assert(is_numeric($detalle['PrcItem']));
                $detalle['PrcItem'] += $iva + $adicional;
            }

            // Agregar descuentos.
            if (!empty($data['ValorDR'][$i]) && !empty($data['TpoValor'][$i])) {
                if ($data['TpoValor'][$i] == '%') {
                    $detalle['DescuentoPct'] = round($data['ValorDR'][$i], 2);
                } else {
                    $detalle['DescuentoMonto'] = $data['ValorDR'][$i];
                    // Si es boleta y el item no es exento se le agrega el IVA
                    // al descuento.
                    if (
                        $dte['Encabezado']['IdDoc']['TipoDTE'] == 39
                        && (!isset($detalle['IndExe']) || !$detalle['IndExe'])
                    ) {
                        $iva_descuento = round(
                            $detalle['DescuentoMonto'] * ($iva_sii / 100)
                        );
                        $detalle['DescuentoMonto'] += $iva_descuento;
                    }
                }
            }

            // Agregar detalle al listado.
            $dte['Detalle'][] = $detalle;

            // Contabilizar item afecto o exento.
            if (empty($detalle['IndExe'])) {
                $n_itemAfecto++;
            } elseif ($detalle['IndExe'] == 1) {
                $n_itemExento++;
            }
        }

        return [$n_itemAfecto, $n_itemExento];
    }

    /**
     * Agrega información de impuestos adicionales al DTE.
     *
     * Identifica impuestos adicionales en los detalles del documento
     * (`Detalle`) y los agrega a los totales (`Totales`) del DTE con su código
     * y tasa.
     *
     * Comportamiento:
     *
     *   - Busca impuestos únicos en los ítems (`CodImpAdic`).
     *   - Obtiene la tasa de cada impuesto de los datos de entrada.
     *   - Si hay impuestos adicionales, los agrega en `ImptoReten`.
     *
     * @param array $data Datos con tasas de impuestos adicionales.
     * @param array $dte  Estructura del DTE para agregar los impuestos.
     *
     * @return void
     */
    private function addAdditionalTaxes(array $data, array &$dte): void
    {
        // Si hay impuestos adicionales se copian los datos a totales para que
        // se calculen los montos.
        $CodImpAdic = [];
        foreach ($dte['Detalle'] as $d) {
            if (
                !empty($d['CodImpAdic'])
                && !in_array($d['CodImpAdic'], $CodImpAdic)
            ) {
                $CodImpAdic[] = (int) $d['CodImpAdic'];
            }
        }

        // Crear el arreglo de impuestos retenidos con sus tasas.
        $ImptoReten = [];
        foreach ($CodImpAdic as $codigo) {
            if (!empty($data['impuesto_adicional_tasa_' . $codigo])) {
                $ImptoReten[] = [
                    'TipoImp' => $codigo,
                    'TasaImp' => $data['impuesto_adicional_tasa_' . $codigo],
                ];
            }
        }

        // Agregar impuestos adicionales a los totales si existen.
        if ($ImptoReten) {
            $dte['Encabezado']['Totales']['ImptoReten'] = $ImptoReten;
        }
    }

    /**
     * Marca el DTE como perteneciente a una empresa constructora.
     *
     * Verifica si el emisor es una empresa constructora. Si se cumplen las
     * condiciones, añade la clave `CredEC` en los totales del DTE, indicando
     * derecho a un crédito del 65%.
     *
     * Condiciones:
     *
     *   - La configuración `constructora` debe estar habilitada en los datos.
     *   - El tipo de documento (`TipoDTE`) debe ser factura, guía o nota.
     *   - El campo `CredEC` debe estar presente en los datos de entrada.
     *
     * @param array $data Datos que incluyen configuración del emisor.
     * @param array $dte  Estructura del DTE donde se marcará `CredEC`.
     *
     * @return void
     */
    private function addConstructionCompany(array $data, array &$dte): void
    {
        // Si la empresa es constructora se marca para obtener el crédito del
        // 65%.
        $config_extra_constructora = !empty($data['constructora'])
            ? $data['constructora']
            : false
        ;
        if (
            $config_extra_constructora
            && in_array($dte['Encabezado']['IdDoc']['TipoDTE'], [33, 52, 56, 61])
            && !empty($data['CredEC'])
        ) {
            $dte['Encabezado']['Totales']['CredEC'] = true;
        }
    }

    /**
     * Agrega descuentos globales al DTE.
     *
     * Procesa los descuentos globales definidos en los datos de entrada y
     * los aplica a ítems afectos, exentos o ambos.
     *
     * En boletas (`TipoDTE == 39`), ajusta valores en moneda para incluir el
     * IVA.
     *
     * Comportamiento:
     *
     *   - Valida el tipo (`TpoValor_global`) y el monto (`ValorDR_global`) del
     *     descuento.
     *   - Aplica descuentos globales a ítems afectos, exentos o ambos.
     *
     * @param array $data         Datos con información del descuento global.
     * @param array $dte          Estructura del DTE para agregar los descuentos.
     * @param int   $n_itemAfecto Número de ítems afectos en el documento.
     * @param int   $n_itemExento Número de ítems exentos en el documento.
     *
     * @return void
     */
    private function addGlobalDiscounts(
        array $data,
        array &$dte,
        int $n_itemAfecto,
        int $n_itemExento
    ): void {
        // Si no hay descuentos globales se retorna.
        if (empty($data['ValorDR_global']) || empty($data['TpoValor_global'])) {
            return;
        }

        // Obtener el IVA.
        $iva_sii = $this->getTax($data['TpoDoc']);

        // Agregar descuentos globales.
        $TpoValor_global = $data['TpoValor_global'];
        $ValorDR_global = $data['ValorDR_global'];

        // Si el descuento es porcentual, redondearlo a 2 decimales.
        if ($TpoValor_global == '%') {
            $ValorDR_global = round($ValorDR_global, 2);
        }

        // Para boletas con valor en moneda, ajustar con el IVA.
        if (
            $dte['Encabezado']['IdDoc']['TipoDTE'] == 39
            && $TpoValor_global == '$'
        ) {
            $ValorDR_global = round($ValorDR_global * (1 + $iva_sii / 100));
        }

        // Agregar descuentos globales al DTE.
        $dte['DscRcgGlobal'] = [];
        if ($n_itemAfecto) {
            $dte['DscRcgGlobal'][] = [
                'TpoMov' => 'D',
                'TpoValor' => $TpoValor_global,
                'ValorDR' => $ValorDR_global,
            ];
        }
        if ($n_itemExento) {
            $dte['DscRcgGlobal'][] = [
                'TpoMov' => 'D',
                'TpoValor' => $TpoValor_global,
                'ValorDR' => $ValorDR_global,
                'IndExeDR' => 1,
            ];
        }
    }

    /**
     * Agrega referencias al DTE.
     *
     * Procesa las referencias de los datos de entrada y las incluye en el DTE.
     * Cada referencia contiene información como tipo de documento, folio,
     * fecha, código de referencia y razón de referencia.
     *
     * Comportamiento:
     *
     *   - Recorre las referencias en los datos de entrada.
     *   - Si el folio es `0`, se marca como referencia global
     *     (`IndGlobal == 1`).
     *   - Añade las referencias procesadas al arreglo `Referencia` del DTE.
     *
     * @param array $data Datos con información de las referencias.
     * @param array $dte  Estructura del DTE donde se agregarán las referencias.
     *
     * @return void
     */
    private function addReferences(array $data, array &$dte): void
    {
        // Si no hay referencias se retorna.
        if (!isset($data['TpoDocRef'][0])) {
            return;
        }

        // Procesar cada referencia.
        $n_referencias = count($data['TpoDocRef']);
        $dte['Referencia'] = [];
        for ($i = 0; $i < $n_referencias; $i++) {
            $dte['Referencia'][] = [
                'TpoDocRef' => $data['TpoDocRef'][$i],
                'IndGlobal' => (
                    is_numeric($data['FolioRef'][$i])
                    && $data['FolioRef'][$i] == 0
                )
                    ? 1
                    : false
                ,
                'FolioRef' => $data['FolioRef'][$i],
                'FchRef' => $data['FchRef'][$i],
                'CodRef' => !empty($data['CodRef'][$i])
                    ? $data['CodRef'][$i]
                    : false
                ,
                'RazonRef' => !empty($data['RazonRef'][$i])
                    ? $data['RazonRef'][$i]
                    : false
                ,
            ];
        }
    }

    /**
     * Obtiene la tasa de impuesto predeterminada para un tipo de documento.
     *
     * Este método consulta el repositorio de tipos de documentos para obtener
     * la tasa de IVA predeterminada asociada al tipo de documento especificado.
     *
     * Comportamiento:
     *
     *   - Si no se encuentra el tipo de documento, retorna `null`.
     *   - Si se encuentra, retorna la tasa predeterminada de IVA.
     *
     * @param int $documentType ID del tipo de documento a consultar.
     * @return float|false La tasa de IVA del documento si tiene una asociada.
     * @throws ParserException Si el documento solicitado no fue encontrado.
     */
    private function getTax(int $documentType): float|false
    {
        // Buscar el documento en el repositorio.
        $result = $this->entityComponent
            ->getRepository(TipoDocumentoInterface::class)
            ->find($documentType)
        ;

        // Retornar null si no se encuentra ningún resultado.
        if (empty($result)) {
            throw new ParserException(sprintf(
                'No se pudo recuperar el IVA para el documento %d.',
                $documentType
            ));
        }

        // Retornar el tax.
        return $result->getDefaultTasaIVA();
    }
}
