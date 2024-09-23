<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Sii\Dte\Documento\Normalization;

use libredte\lib\Core\Helper\Arr;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;

/**
 * Clase que maneja la normalización de los datos de un documento.
 *
 * En el contexto de los documentos tributarios y LibreDTE, el proceso de
 * normalización realiza 2 cosas en conjunto:
 *
 *   - Estandariza la estructura de los datos.
 *   - Realiza los cálculos de campos que no se hayan especificado y se puedan
 *     calcular a partir de los datos de otros campos.
 *
 * Esta clase incluye los métodos de normalización generales (initial, final y
 * extra). Otros métodos son provistos como traits y deberán ser incluídos en
 * cada documento que necesite aplicar dichas normalizaciones auxiliares.
 */
class DocumentoNormalizer
{
    /**
     * Tipo de documento que normalizará esta instancia del normalizador.
     *
     * @var DocumentoTipo
     */
    private DocumentoTipo $tipoDocumento;

    /**
     * Undocumented variable
     *
     * @var ?callable
     */
    private $documentNormalizationCallback;

    /**
     * Constructor de la clase de normalización.
     *
     * @param DocumentoTipo $tipoDocumento Tipo de documento a normalizar.
     * @param ?callable $documentNormalizationCallback Callback al método del
     * "builder" del documento que aplicará las reglas específicas según el
     * tipo de documento.
     */
    public function __construct(
        DocumentoTipo $tipoDocumento,
        callable $documentNormalizationCallback = null
    ) {
        $this->tipoDocumento = $tipoDocumento;
        $this->documentNormalizationCallback = $documentNormalizationCallback;
    }

    /**
     * Ejecuta la normalización de los datos.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    public function normalize(array $data): array
    {
        $data = $this->applyInitialNormalization($data);
        $data = $this->applyDocumentNormalization($data);
        $data = $this->applyFinalNormalization($data);
        $data = $this->applyProNormalization($data);

        return $data;
    }

    /**
     * Aplica la normalización inicial de los datos de un documento tributario
     * electrónico.
     *
     * Esta normalización se ejecuta antes de ejecutar la normalización
     * específica del tipo de documento tributario.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    private function applyInitialNormalization(array $data): array
    {
        // Completar con campos por defecto.
        $data = Arr::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => false,
                    'Folio' => false,
                    'FchEmis' => date('Y-m-d'),
                    'IndNoRebaja' => false,
                    'TipoDespacho' => false,
                    'IndTraslado' => false,
                    'TpoImpresion' => false,
                    'IndServicio' => $this->tipoDocumento->getDefaultIndServicio(),
                    'MntBruto' => false,
                    'TpoTranCompra' => false,
                    'TpoTranVenta' => false,
                    'FmaPago' => false,
                    'FmaPagExp' => false,
                    'MntCancel' => false,
                    'SaldoInsol' => false,
                    'FchCancel' => false,
                    'MntPagos' => false,
                    'PeriodoDesde' => false,
                    'PeriodoHasta' => false,
                    'MedioPago' => false,
                    'TpoCtaPago' => false,
                    'NumCtaPago' => false,
                    'BcoPago' => false,
                    'TermPagoCdg' => false,
                    'TermPagoGlosa' => false,
                    'TermPagoDias' => false,
                    'FchVenc' => false,
                ],
                'Emisor' => [
                    'RUTEmisor' => false,
                    'RznSoc' => false,
                    'GiroEmis' => false,
                    'Telefono' => false,
                    'CorreoEmisor' => false,
                    'Acteco' => false,
                    'GuiaExport' => false,
                    'Sucursal' => false,
                    'CdgSIISucur' => false,
                    'DirOrigen' => false,
                    'CmnaOrigen' => false,
                    'CiudadOrigen' => false,
                    'CdgVendedor' => false,
                    'IdAdicEmisor' => false,
                ],
                'Receptor' => [
                    'RUTRecep' => false,
                    'CdgIntRecep' => false,
                    'RznSocRecep' => false,
                    'Extranjero' => false,
                    'GiroRecep' => false,
                    'Contacto' => false,
                    'CorreoRecep' => false,
                    'DirRecep' => false,
                    'CmnaRecep' => false,
                    'CiudadRecep' => false,
                    'DirPostal' => false,
                    'CmnaPostal' => false,
                    'CiudadPostal' => false,
                ],
                'Totales' => [
                    'TpoMoneda' => false,
                ],
            ],
            'Detalle' => false,
            'SubTotInfo' => false,
            'DscRcgGlobal' => false,
            'Referencia' => false,
            'Comisiones' => false,
        ], $data);

        // Si existe descuento o recargo global se normalizan.
        if (!empty($data['DscRcgGlobal'])) {
            if (!isset($data['DscRcgGlobal'][0])) {
                $data['DscRcgGlobal'] = [
                    $data['DscRcgGlobal'],
                ];
            }
            $NroLinDR = 1;
            foreach ($data['DscRcgGlobal'] as &$dr) {
                $dr = array_merge([
                    'NroLinDR' => $NroLinDR++,
                ], $dr);
            }
        }

        // Si existe una o más referencias se normalizan.
        if (!empty($data['Referencia'])) {
            if (!isset($data['Referencia'][0])) {
                $data['Referencia'] = [
                    $data['Referencia'],
                ];
            }
            $NroLinRef = 1;
            foreach ($data['Referencia'] as &$r) {
                $r = array_merge([
                    'NroLinRef' => $NroLinRef++,
                    'TpoDocRef' => false,
                    'IndGlobal' => false,
                    'FolioRef' => false,
                    'RUTOtr' => false,
                    'FchRef' => date('Y-m-d'),
                    'CodRef' => false,
                    'RazonRef' => false,
                ], $r);
            }
        }

        // Verificar que exista TpoTranVenta.
        if (
            $this->tipoDocumento->requiereTpoTranVenta()
            && empty($data['Encabezado']['IdDoc']['TpoTranVenta'])
        ) {
            // Se asigna "Ventas del giro" como valor por defecto.
            $data['Encabezado']['IdDoc']['TpoTranVenta'] = 1;
        }

        // Entregar los datos normalizados.
        return $data;
    }

    /**
     * Aplica la normalización de los datos específica de un tipo de documento
     * tributario electrónico.
     *
     * Esta normalización se ejecuta utilizando el callback provisto al
     * instanciar este objeto de normalización.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    private function applyDocumentNormalization(array $data): array
    {
        if (!isset($this->documentNormalizationCallback)) {
            return $data;
        }

        return ($this->documentNormalizationCallback)($data);
    }

    /**
     * Aplica la normalización final de los datos de un documento tributario
     * electrónico.
     *
     * Esta normalización se ejecuta después de ejecutar la normalización
     * específica del tipo de documento tributario.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    private function applyFinalNormalization(array $data): array
    {
        // Normalizar montos de pagos programados.
        if (is_array($data['Encabezado']['IdDoc']['MntPagos'])) {
            if (!isset($data['Encabezado']['IdDoc']['MntPagos'][0])) {
                $data['Encabezado']['IdDoc']['MntPagos'] = [
                    $data['Encabezado']['IdDoc']['MntPagos'],
                ];
            }
            foreach ($data['Encabezado']['IdDoc']['MntPagos'] as &$MntPagos) {
                $MntPagos = array_merge([
                    'FchPago' => null,
                    'MntPago' => null,
                    'GlosaPagos' => false,
                ], $MntPagos);
                if ($MntPagos['MntPago'] === null) {
                    $MntPagos['MntPago'] = $data['Encabezado']['Totales']['MntTotal'];
                }
            }
        }

        // Si existe OtraMoneda se verifican los tipos de cambio y totales.
        if (!empty($data['Encabezado']['OtraMoneda'])) {
            if (!isset($data['Encabezado']['OtraMoneda'][0])) {
                $data['Encabezado']['OtraMoneda'] = [
                    $data['Encabezado']['OtraMoneda'],
                ];
            }
            foreach ($data['Encabezado']['OtraMoneda'] as &$OtraMoneda) {
                // Colocar campos por defecto.
                $OtraMoneda = array_merge([
                    'TpoMoneda' => false,
                    'TpoCambio' => false,
                    'MntNetoOtrMnda' => false,
                    'MntExeOtrMnda' => false,
                    'MntFaeCarneOtrMnda' => false,
                    'MntMargComOtrMnda' => false,
                    'IVAOtrMnda' => false,
                    'ImpRetOtrMnda' => false,
                    'IVANoRetOtrMnda' => false,
                    'MntTotOtrMnda' => false,
                ], $OtraMoneda);

                // Si no hay tipo de cambio no seguir.
                if (!isset($OtraMoneda['TpoCambio'])) {
                    continue;
                }

                // Buscar si los valores están asignados, si no lo están se
                // asignan usando el tipo de cambio que exista para la moneda.
                foreach (['MntNeto', 'MntExe', 'IVA', 'IVANoRet'] as $monto) {
                    if (
                        empty($OtraMoneda[$monto.'OtrMnda'])
                        && !empty($data['Encabezado']['Totales'][$monto])
                    ) {
                        $OtraMoneda[$monto.'OtrMnda'] = round(
                            $data['Encabezado']['Totales'][$monto]
                                * $OtraMoneda['TpoCambio'],
                            4
                        );
                    }
                }

                // Calcular MntFaeCarneOtrMnda, MntMargComOtrMnda y
                // ImpRetOtrMnda.
                if (empty($OtraMoneda['MntFaeCarneOtrMnda'])) {
                    // TODO: Implementar cálculo de MntFaeCarneOtrMnda.
                    $OtraMoneda['MntFaeCarneOtrMnda'] = false;
                }
                if (empty($OtraMoneda['MntMargComOtrMnda'])) {
                    // TODO: Implementar cálculo de MntMargComOtrMnda.
                    $OtraMoneda['MntMargComOtrMnda'] = false;
                }
                if (empty($OtraMoneda['ImpRetOtrMnda'])) {
                    // TODO: Implementar cálculo de ImpRetOtrMnda.
                    $OtraMoneda['ImpRetOtrMnda'] = false;
                }

                // Calcular el monto total.
                if (empty($OtraMoneda['MntTotOtrMnda'])) {
                    $OtraMoneda['MntTotOtrMnda'] = 0;
                    $cols = [
                        'MntNetoOtrMnda',
                        'MntExeOtrMnda',
                        'MntFaeCarneOtrMnda',
                        'MntMargComOtrMnda',
                        'IVAOtrMnda',
                        'IVANoRetOtrMnda',
                    ];
                    foreach ($cols as $monto) {
                        if (!empty($OtraMoneda[$monto])) {
                            $OtraMoneda['MntTotOtrMnda'] += $OtraMoneda[$monto];
                        }
                    }

                    // Agregar el total de impuesto retenido de otra moneda.
                    if (!empty($OtraMoneda['ImpRetOtrMnda'])) {
                        // TODO: Agregar el total de impuesto retenido de ImpRetOtrMnda.
                    }

                    // Aproximar el total si es en pesos chilenos.
                    if ($OtraMoneda['TpoMoneda'] === 'PESO CL') {
                        $OtraMoneda['MntTotOtrMnda'] = round(
                            $OtraMoneda['MntTotOtrMnda'],
                            0
                        );
                    }
                }

                // Si el tipo de cambio es 0, se quita.
                if ($OtraMoneda['TpoCambio'] == 0) {
                    $OtraMoneda['TpoCambio'] = false;
                }
            }
        }

        // Entregar los datos normalizados.
        return $data;
    }

    /**
     * Normaliza los datos del documento utilizando funcionalidades Pro.
     *
     * Esta normalización se ejecuta después de ejecutar la normalización final
     * del documento tributario (es la última normalización).
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    private function applyProNormalization(array $data): array
    {
        // Normalizar los datos con las funcionalidades Pro de la biblioteca.
        $class = '\libredte\lib\Pro\Sii\Dte\Documento\Normalization\DocumentoNormalizer';
        if (class_exists($class)) {
            $normalizer = $class::create($this);
            $data = $normalizer->normalize($data);
        }

        // Entregar los datos normalizados.
        return $data;
    }
}
