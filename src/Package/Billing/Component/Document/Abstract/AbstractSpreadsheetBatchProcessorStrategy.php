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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Support\Date;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BatchProcessorStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBatchInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaMoneda;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;

/**
 * Base de las estrategias de "billing.document.batch_processor" que parsean un
 * lote de documentos tributarios entregado como planilla con el formato
 * estándar de LibreDTE.
 *
 * Genera el listado de los documentos en la estructura oficial del SII; no los
 * construye, eso se hace después, uno a uno, al procesar el lote.
 *
 * Cada estrategia concreta solo debe entregar las filas de la planilla
 * (`readRows()`), según su formato de archivo (CSV, XLSX, etc.). Todo lo demás
 * es común.
 *
 * Formato de la planilla:
 *
 *   - La primera fila es el encabezado y se ignora.
 *   - Una fila con `TipoDTE` crea un documento nuevo, con su primer ítem y su
 *     primera referencia (si las tiene).
 *   - Una fila sin `TipoDTE` agrega un ítem (si trae `NmbItem`) y/o una
 *     referencia al último documento creado.
 *   - El par (`TipoDTE`, `Folio`) no puede repetirse dentro del archivo.
 *   - Las filas pueden venir sin las últimas columnas, que se asumen vacías.
 *   - En los decimales se puede usar punto o coma. No se usa separador de
 *     miles.
 *   - Un archivo sin documentos entrega un arreglo vacío.
 *   - Los errores indican el número de la fila donde ocurrieron.
 *
 * Columnas (letra, nombre, largo máximo y obligatoriedad):
 *
 *   1. A `TipoDTE` (3, obligatorio): tipo de documento. Por ejemplo: 33 factura
 *      afecta, 34 factura exenta, 39 boleta afecta, 41 boleta exenta, 46
 *      factura de compra, 52 guía de despacho, 56 nota de débito, 61 nota de
 *      crédito, 110 factura de exportación, 111 nota de débito de exportación
 *      y 112 nota de crédito de exportación.
 *   2. B `Folio` (10, obligatorio): entero mayor a 0 que identifica al
 *      documento dentro del archivo. Normalmente parte en 1 y es correlativo.
 *   3. C `FchEmis` (10, opcional): fecha de emisión AAAA-MM-DD. Si falta o no
 *      es válida se usa la fecha actual.
 *   4. D `FchVenc` (10, opcional): fecha de vencimiento AAAA-MM-DD. Si no es
 *      válida se ignora.
 *   5. E `RUTRecep` (10, obligatorio): RUT del receptor sin puntos, con guión
 *      y dígito verificador.
 *   6. F `RznSocRecep` (100, obligatorio excepto en boletas): razón social del
 *      receptor.
 *   7. G `GiroRecep` (40, obligatorio excepto en boletas): giro del receptor.
 *   8. H `Telefono` (20, opcional): teléfono del receptor, se guarda en el
 *      contacto. Formato recomendado: +56 9 55443322.
 *   9. I `CorreoRecep` (80, opcional): correo del receptor. Se valida.
 *  10. J `DirRecep` (70, obligatorio excepto en boletas): dirección del
 *      receptor.
 *  11. K `CmnaRecep` (20, obligatorio excepto en boletas): comuna del
 *      receptor, sin abreviaciones.
 *  12. L `VlrCodigo` (35, opcional): código del ítem.
 *  13. M `IndExe` (1, opcional): 1 si el ítem es exento, 2 si es no
 *      facturable. Otros valores no están soportados.
 *  14. N `NmbItem` (80, obligatorio): nombre del ítem.
 *  15. O `DscItem` (1000, opcional): descripción del ítem.
 *  16. P `QtyItem` (18, obligatorio salvo que el precio sea 0): cantidad del
 *      ítem. Una cantidad vacía o 0 solo es válida si el precio también es 0;
 *      en ese caso se omiten la cantidad y el precio del ítem.
 *  17. Q `UnmdItem` (4, opcional): unidad del ítem.
 *  18. R `PrcItem` (18, obligatorio): precio del ítem. Monto bruto (con IVA)
 *      en boletas y monto neto en los demás documentos. Un precio 0 se omite.
 *  19. S `Descuento` (18, opcional): descuento del ítem, en porcentaje (por
 *      ejemplo `50%`) o en monto (por ejemplo `1000`). En boletas el monto es
 *      bruto. El porcentaje se redondea a 2 decimales y el monto a un entero.
 *      Un descuento que resulta 0 se omite.
 *  20. T `TermPagoGlosa` (100, opcional): observación del documento.
 *  21. U `PeriodoDesde` (10, opcional): inicio del período AAAA-MM-DD. Si no es
 *      válido se ignora.
 *  22. V `PeriodoHasta` (10, opcional): fin del período AAAA-MM-DD. Si no es
 *      válido se ignora.
 *  23. W `Patente` (8, opcional): patente del vehículo de despacho.
 *  24. X `RUTTrans` (10, opcional): RUT del transportista sin puntos, con
 *      guión y dígito verificador.
 *  25. Y `RUTChofer` (10, obligatorio solo si va el nombre del chofer): RUT del
 *      chofer sin puntos, con guión y dígito verificador.
 *  26. Z `NombreChofer` (30, obligatorio solo si va el RUT del chofer): nombre
 *      del chofer.
 *  27. AA `DirDest` (70, opcional): dirección de despacho.
 *  28. AB `CmnaDest` (20, opcional): comuna de despacho, sin abreviaciones.
 *  29. AC `TpoDocRef` (3, opcional): tipo del documento de referencia. Por
 *      ejemplo: 33, 34, 39, 41, 52, 801 orden de compra o HES hoja de entrada
 *      de servicios.
 *  30. AD `FolioRef` (18, obligatorio si hay referencia): folio del documento
 *      de referencia. El folio 0 indica una referencia global a un conjunto de
 *      documentos.
 *  31. AE `FchRef` (10, obligatorio si hay referencia): fecha del documento de
 *      referencia AAAA-MM-DD.
 *  32. AF `CodRef` (1, obligatorio en notas de crédito y de débito): código de
 *      referencia. 1 anula el documento, 2 corrige el texto y 3 corrige los
 *      montos.
 *  33. AG `RazonRef` (90, obligatorio en notas de crédito y de débito): motivo
 *      de la referencia.
 *  34. AH `Moneda` (3, opcional): moneda de los documentos de exportación. Por
 *      defecto USD.
 *  35. AI `NumId` (20, opcional): ID del receptor extranjero, en los
 *      documentos de exportación.
 *  36. AJ `DscGlobal Exento` (18, opcional): descuento global sobre el monto
 *      exento, en porcentaje (por ejemplo `50%`) o en monto.
 *  37. AK `Nombre PDF` (100, opcional): nombre del PDF a descargar. Se pueden
 *      usar las variables {rut}, {dv}, {dte} y {folio}.
 *  38. AL `Forma de Pago` (1, opcional): 1 contado, 2 crédito y 3 sin costo
 *      (entrega gratuita).
 *  39. AM `CodImpAdic` (3, opcional): código del impuesto adicional, recargo o
 *      retención del ítem. Por ejemplo: 15 para el IVA retenido total en
 *      facturas de compra.
 *  40. AN `DscGlobal Afecto` (18, opcional): descuento global sobre el monto
 *      afecto, en porcentaje (por ejemplo `50%`) o en monto. En boletas el
 *      monto es bruto.
 *  41. AO `PatenteCarro` (8, opcional): patente del carro o remolque.
 *  42. AP `FchSalida` (10, opcional): fecha de salida del traslado AAAA-MM-DD.
 *      Si no es válida se ignora.
 *  43. AQ `HraSalida` (8, opcional): hora de salida del traslado, HH:MM o
 *      HH:MM:SS.
 *  44. AR `FchLlegada` (10, opcional): fecha de llegada del traslado
 *      AAAA-MM-DD. Si no es válida se ignora.
 */
abstract class AbstractSpreadsheetBatchProcessorStrategy extends AbstractStrategy implements BatchProcessorStrategyInterface
{
    /**
     * Constructor de la estrategia con sus dependencias.
     *
     * @param RepositoryManagerInterface $repositoryManager
     */
    public function __construct(
        private RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * Entrega las filas de la planilla del lote, incluyendo el encabezado.
     *
     * Cada fila es un arreglo de strings con los valores de las celdas, en el
     * orden de las columnas. Las celdas vacías son `''` (nunca `null`) y los
     * valores, incluidos los números y las fechas, están como texto tal cual
     * los entregaría un formulario (por ejemplo, las fechas como AAAA-MM-DD).
     * No se deben omitir filas (ni las vacías): el índice de cada fila se usa
     * para informar dónde ocurrió un error.
     *
     * @param DocumentBatchInterface $batch
     * @return array<int,array<int,string>>
     * @throws BatchProcessorException Si no se pueden leer las filas.
     */
    abstract protected function readRows(DocumentBatchInterface $batch): array;

    /**
     * {@inheritDoc}
     */
    public function parse(DocumentBatchInterface $batch): array
    {
        $data = $this->readRows($batch);
        $n_data = count($data);
        $documentos = [];
        $documento = [];
        $folios = [];

        // Procesar cada fila del archivo.
        for ($i = 1; $i < $n_data; $i++) {
            try {
                // Si la fila corresponde a un documento nuevo.
                if (!empty($data[$i][0])) {
                    // Agregar el documento actual al listado si existe.
                    if ($documento) {
                        $documentos[] = $documento;
                    }
                    // Crear un nuevo documento.
                    $documento = $this->createDocument($data[$i]);

                    // Verificar que el folio no esté repetido para el tipo de
                    // documento.
                    $tipo = $documento['Encabezado']['IdDoc']['TipoDTE'];
                    $folio = $documento['Encabezado']['IdDoc']['Folio'];
                    if (isset($folios[$tipo][$folio])) {
                        throw new BatchProcessorException(sprintf(
                            'El folio %d del tipo de documento %d está repetido en el archivo.',
                            $folio,
                            $tipo
                        ));
                    }
                    $folios[$tipo][$folio] = true;
                } else {
                    // Si la fila no corresponde a un documento nuevo, agregar
                    // detalles al documento actual.
                    $referencia = array_slice($data[$i], 28, 5);
                    if (
                        !$documento
                        && (!empty($data[$i][13]) || array_filter($referencia))
                    ) {
                        throw new BatchProcessorException(
                            'La fila trae un ítem o una referencia, pero no hay un documento anterior al que agregarlos.'
                        );
                    }
                    if (!empty($data[$i][13])) {
                        $dataItem = array_merge(
                            // Datos originales del item (vienen juntos en el
                            // archivo).
                            array_slice($data[$i], 11, 8),

                            // Datos adicionales del item (vienen después del
                            // item, "al final", porque se añadieron después de
                            // los previos al archivo).
                            [
                                // CodImpAdic.
                                !empty($data[$i][38]) ? $data[$i][38] : null,
                            ]
                        );

                        $this->addItem($documento, $dataItem);
                    }

                    // Agregar referencias al documento.
                    $this->addReference($documento, $referencia);
                }
            } catch (BatchProcessorException $e) {
                // El número de fila es el que ve el usuario en la planilla:
                // el índice 0 es la fila 1 (el encabezado).
                throw new BatchProcessorException(
                    sprintf('Fila %d: %s', $i + 1, $e->getMessage()),
                    $e->getCode(),
                    $e
                );
            }
        }

        // Agregar el último documento procesado al listado si existe.
        if ($documento) {
            $documentos[] = $documento;
        }

        return $documentos;
    }

    /**
     * Crea un documento a partir de los datos proporcionados.
     *
     * Verifica los datos mínimos requeridos y genera la estructura base.
     *
     * También agrega ítems, transporte y referencias al documento.
     *
     * @param array $data Datos para crear el documento. Los índices corresponden a:
     *   - 0: Tipo de documento (obligatorio).
     *   - 1: Folio del documento (obligatorio).
     *   - 2: Fecha de emisión (opcional).
     *   - 3: Fecha de vencimiento (opcional).
     *   - 4: RUT del receptor (obligatorio).
     *   - 5: Razón social del receptor (obligatoria si no es boleta).
     *   - 6: Giro del receptor (obligatorio si no es boleta).
     *   - 7: Teléfono del receptor, va en Contacto (opcional, máximo 20).
     *   - 8: Correo del receptor (opcional, validado si se proporciona).
     *   - 9: Dirección del receptor (obligatoria si no es boleta).
     *   - 10: Comuna del receptor (obligatoria si no es boleta).
     *   - 33: Tipo de moneda (opcional, por defecto USD si aplica).
     *   - 34: Número de identificación del receptor extranjero (opcional).
     *   - 35: Descuento global exento (opcional, porcentaje o monto).
     *   - 36: Nombre del PDF (opcional).
     *   - 37: Forma de pago (opcional, 1, 2 o 3).
     *   - 38: Código de impuesto adicional (opcional).
     *   - 39: Descuento global afecto (opcional, porcentaje o monto).
     * @return array Estructura del documento generado.
     * @throws BatchProcessorException Si faltan datos mínimos o son inválidos.
     */
    private function createDocument(array $data): array
    {
        // Verificar datos mínimos obligatorios.
        if (empty($data[0])) {
            throw new BatchProcessorException('Falta tipo de documento.');
        }
        $folio = trim((string) ($data[1] ?? ''));
        if ($folio === '') {
            throw new BatchProcessorException('Falta folio del documento.');
        }
        if (!ctype_digit($folio) || (int) $folio < 1) {
            throw new BatchProcessorException(
                'El folio del documento debe ser un número entero mayor a 0.'
            );
        }
        if (empty($data[4])) {
            throw new BatchProcessorException('Falta RUT del receptor.');
        }

        // Verificar datos si no es boleta.
        if (!in_array($data[0], [39, 41])) {
            if (empty($data[5])) {
                throw new BatchProcessorException(
                    'Falta razón social del receptor.'
                );
            }
            if (empty($data[6])) {
                throw new BatchProcessorException(
                    'Falta giro del receptor.'
                );
            }
            if (empty($data[9])) {
                throw new BatchProcessorException(
                    'Falta dirección del receptor.'
                );
            }
            if (empty($data[10])) {
                throw new BatchProcessorException(
                    'Falta comuna del receptor.'
                );
            }
        }

        // Crear la estructura base del documento.
        $documento = $this->setInitialDTE($data);

        // Validar correo electrónico.
        if (!empty($data[8])) {
            if (!filter_var($data[8], FILTER_VALIDATE_EMAIL)) {
                throw new BatchProcessorException(sprintf(
                    'Correo electrónico %s no es válido.',
                    $data[8]
                ));
            }
            $documento['Encabezado']['Receptor']['CorreoRecep'] = mb_substr(
                trim($data[8]),
                0,
                80
            );
        }

        // Manejar tipos de moneda para documentos de exportación.
        if (in_array($documento['Encabezado']['IdDoc']['TipoDTE'], [110,111,112])) {
            // Agregar moneda.
            if (empty($data[33])) {
                $data[33] = 'USD';
            }
            $moneda = $this->getCurrency($data[33]);
            if (empty($moneda)) {
                throw new BatchProcessorException(
                    sprintf(
                        'El tipo de moneda %s no está permitido, solo: USD, EUR y CLP.',
                        $data[33]
                    )
                );
            }
            $documento['Encabezado']['Totales']['TpoMoneda'] = $moneda;

            // Agregar ID del receptor.
            if (!empty($data[34])) {
                $documento['Encabezado']['Receptor']['Extranjero']['NumId'] = mb_substr(
                    trim($data[34]),
                    0,
                    20
                );
            }
        }

        // Procesar descuentos globales. Primero el que aplica al monto afecto
        // y luego el que aplica al monto exento.
        if (!empty($data[39])) {
            $this->addGlobalDiscount($documento, $data[39], false);
        }
        if (!empty($data[35])) {
            $this->addGlobalDiscount($documento, $data[35], true);
        }

        // Asignar el nombre del PDF si se proporciona.
        // Esto permite asociar un archivo PDF específico al documento.
        if (!empty($data[36])) {
            $documento['LibreDTE']['pdf']['nombre'] = mb_substr(
                trim($data[36]),
                0,
                100
            );
        }

        // Procesar forma de pago.
        if (!empty($data[37])) {
            if (!in_array($data[37], [1, 2, 3])) {
                throw new BatchProcessorException(sprintf(
                    'Forma de pago de código %s es incorrecta, debe ser: 1 (contado), 2 (crédito) o 3 (sin costo).',
                    $data[37]
                ));
            }
            $documento['Encabezado']['IdDoc']['FmaPago'] = (int) $data[37];
        }

        // Agregar ítems, transporte y referencias.
        $dataItem = array_merge(
            // Datos originales del item (vienen juntos en el archivo).
            array_slice($data, 11, 8),

            // Datos adicionales del item (vienen después del item, "al final",
            // porque se añadieron después de los previos al archivo)
            [
                // CodImpAdic.
                !empty($data[38]) ? $data[38] : null,
            ]
        );

        $this->addItem($documento, $dataItem);
        $this->addTransport($documento, array_merge(
            // Datos originales del transporte (vienen juntos en el archivo).
            array_pad(array_slice($data, 22, 6), 6, null),

            // Datos adicionales del transporte (vienen "al final").
            [
                // PatenteCarro, FchSalida, HraSalida y FchLlegada.
                $data[40] ?? null,
                $data[41] ?? null,
                $data[42] ?? null,
                $data[43] ?? null,
            ]
        ));
        $this->addReference($documento, array_slice($data, 28, 5));

        return $documento;
    }

    /**
     * Agrega un descuento global al documento.
     *
     * @param array &$documento Documento al que se agregará el descuento.
     * Se pasa por referencia para modificarlo.
     * @param string $descuento Porcentaje (por ejemplo `10%`) o monto (por
     * ejemplo `1000`) del descuento.
     * @param bool $exento Si es `true` el descuento aplica al monto exento, si
     * es `false` aplica al monto afecto.
     * @return void Modifica el documento directamente.
     */
    private function addGlobalDiscount(
        array &$documento,
        string $descuento,
        bool $exento
    ): void {
        $descuento = str_replace(',', '.', $descuento);
        if (str_contains($descuento, '%')) {
            $TpoValor_global = '%';
            $ValorDR_global = round((float)str_replace('%', '', $descuento), 2);
        } else {
            $TpoValor_global = '$';
            $ValorDR_global = round((float)$descuento, 2);
        }

        // Un descuento que resulta 0 se omite.
        if ($ValorDR_global == 0) {
            return;
        }

        $descuentoGlobal = [
            'TpoMov' => 'D',
            'TpoValor' => $TpoValor_global,
            'ValorDR' => $ValorDR_global,
        ];
        if ($exento) {
            $descuentoGlobal['IndExeDR'] = 1;
        }

        $documento['DscRcgGlobal'][] = $descuentoGlobal;
    }

    /**
     * Genera la estructura inicial del DTE.
     *
     * Este método crea un arreglo con la estructura base del DTE, incluyendo
     * encabezado, emisor, receptor y detalles. Configura valores
     * predeterminados para los campos opcionales y procesa algunos datos de
     * entrada.
     *
     * @param array $data Datos de entrada para generar la estructura del DTE.
     * @return array Arreglo con la estructura inicial del DTE.
     */
    private function setInitialDTE(array $data): array
    {
        return [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => (int) $data[0],
                    'Folio' => (int) $data[1],
                    'FchEmis' => (
                        !empty($data[2]) && Date::validateAndConvert($data[2], 'Y-m-d') !== null
                    ) ? $data[2] : date('Y-m-d'),
                    'TpoTranCompra' => false,
                    'TpoTranVenta' => false,
                    'FmaPago' => false,
                    'FchCancel' => false,
                    'PeriodoDesde' => !empty($data[20]) && Date::validateAndConvert($data[20], 'Y-m-d') !== null
                        ? $data[20]
                        : false,
                    'PeriodoHasta' => !empty($data[21]) && Date::validateAndConvert($data[21], 'Y-m-d') !== null
                        ? $data[21]
                        : false,
                    'MedioPago' => false,
                    'TpoCtaPago' => false,
                    'NumCtaPago' => false,
                    'BcoPago' => false,
                    'TermPagoGlosa' => !empty($data[19])
                        ? mb_substr(trim($data[19]), 0, 100)
                        : false,
                    'FchVenc' => !empty($data[3]) && Date::validateAndConvert($data[3], 'Y-m-d') !== null
                        ? $data[3]
                        : false,
                ],
                'Emisor' => [
                    'RUTEmisor' => false,
                    'RznSoc' => false,
                    'GiroEmis' => false,
                    'Telefono' => false,
                    'CorreoEmisor' => false,
                    'Acteco' => false,
                    'CdgSIISucur' => false,
                    'DirOrigen' => false,
                    'CmnaOrigen' => false,
                    'CdgVendedor' => false,
                ],
                'Receptor' => [
                    'RUTRecep' => str_replace('.', '', $data[4]),
                    'CdgIntRecep' => false,
                    'RznSocRecep' => !empty($data[5])
                        ? mb_substr(trim($data[5]), 0, 100)
                        : false,
                    'GiroRecep' => !empty($data[6])
                        ? mb_substr(trim($data[6]), 0, 40)
                        : false,
                    'Contacto' => !empty($data[7])
                        ? mb_substr(trim($data[7]), 0, 20)
                        : false,
                    'CorreoRecep' => false,
                    'DirRecep' => !empty($data[9])
                        ? mb_substr(trim($data[9]), 0, 70)
                        : false,
                    'CmnaRecep' => !empty($data[10])
                        ? mb_substr(trim($data[10]), 0, 20)
                        : false,
                    'CiudadRecep' => false,
                ],
                'RUTSolicita' => false,
            ],
            'Detalle' => [],
        ];

    }

    /**
     * Agrega un ítem al documento.
     *
     * Procesa los datos de un ítem y lo agrega al arreglo de detalles. Valida
     * que los campos mínimos estén presentes y ajusta la longitud de los datos.
     *
     * @param array &$documento Documento al que se agregará el ítem. Modificado
     * directamente.
     * @param array $item  Datos del ítem. Los índices corresponden a:
     *   - 0: Código del ítem (opcional).
     *   - 1: Indicador de exención (opcional).
     *   - 2: Nombre del ítem (obligatorio).
     *   - 3: Descripción del ítem (opcional).
     *   - 4: Cantidad del ítem (obligatoria, salvo que el precio sea 0).
     *   - 5: Unidad de medida (opcional).
     *   - 6: Precio del ítem (obligatorio).
     *   - 7: Descuento (opcional, porcentaje o monto).
     *   - 8: Código de impuesto adicional (opcional).
     * @return void
     * @throws BatchProcessorException Si faltan datos obligatorios.
     */
    private function addItem(array &$documento, array $item): void
    {
        // Verificar datos mínimos obligatorios.
        if (empty($item[2])) {
            throw new BatchProcessorException(
                'Falta nombre del item.'
            );
        }
        $precioTexto = trim((string) ($item[6] ?? ''));
        if ($precioTexto === '') {
            throw new BatchProcessorException(
                'Falta precio del item.'
            );
        }
        $cantidadTexto = trim((string) ($item[4] ?? ''));
        $precio = (float)str_replace(',', '.', $precioTexto);
        $cantidad = (float)str_replace(',', '.', $cantidadTexto);

        // Una cantidad vacía o 0 solo es válida si el precio también es 0.
        if ($cantidad == 0 && $precio != 0) {
            throw new BatchProcessorException(
                $cantidadTexto === ''
                    ? 'Falta cantidad del item.'
                    : 'La cantidad del item no puede ser 0 si el precio es distinto de 0.'
            );
        }

        // Crear el detalle del ítem.
        $detalle = [
            'NmbItem' => mb_substr(trim($item[2]), 0, 80),
        ];

        // Agregar la cantidad y el precio del ítem. Una cantidad 0 o un precio 0
        // se omiten (por ejemplo, en una nota que corrige un texto).
        if ($cantidad != 0) {
            $detalle['QtyItem'] = $cantidad;
        }
        if ($precio != 0) {
            $detalle['PrcItem'] = $precio;
        }

        // Agregar código del ítem si está presente.
        if (!empty($item[0])) {
            $detalle['CdgItem'] = [
                'TpoCodigo' => 'INT1',
                'VlrCodigo' => mb_substr(trim($item[0]), 0, 35),
            ];
        }

        // Agregar indicador de exención si está presente.
        if (!empty($item[1])) {
            $detalle['IndExe'] = (int)$item[1];
        }

        // Agregar descripción del ítem si está presente.
        if (!empty($item[3])) {
            $detalle['DscItem'] = mb_substr(trim($item[3]), 0, 1000);
        }

        // Agregar unidad de medida si está presente.
        if (!empty($item[5])) {
            $detalle['UnmdItem'] = mb_substr(trim($item[5]), 0, 4);
        }


        // Procesar y agregar descuento si está presente.
        if (!empty($item[7])) {
            $descuento = str_replace(',', '.', $item[7]);
            if (str_contains($descuento, '%')) {
                $descuentoPct = round((float)str_replace('%', '', $descuento), 2);
                if ($descuentoPct != 0) {
                    $detalle['DescuentoPct'] = $descuentoPct;
                }
            } else {
                $descuentoMonto = (int) round((float)$descuento);
                if ($descuentoMonto != 0) {
                    $detalle['DescuentoMonto'] = $descuentoMonto;
                }
            }
        }

        // Agregar código de impuesto adicional si está presente.
        if (!empty($item[8])) {
            $detalle['CodImpAdic'] = (int)trim($item[8]);
        }

        // Agregar el detalle al documento.
        $documento['Detalle'][] = $detalle;
    }

    /**
     * Agrega información de transporte a un documento.
     *
     * Procesa los datos de transporte proporcionados y los agrega al arreglo
     * `Transporte` dentro del documento. Los datos incluyen información de
     * patente, transportista, chofer, destino y fechas del traslado.
     *
     * @param array &$documento Documento al que se agregará la información de
     * transporte. Se pasa por referencia para modificarlo.
     * @param array $transporte Datos de transporte a procesar. Los índices son:
     *   - 0: Patente del vehículo (opcional).
     *   - 1: RUT del transportista (opcional).
     *   - 2: RUT del chofer (opcional).
     *   - 3: Nombre del chofer (opcional).
     *   - 4: Dirección del destino (opcional).
     *   - 5: Comuna del destino (opcional).
     *   - 6: Patente del carro o remolque (opcional).
     *   - 7: Fecha de salida AAAA-MM-DD (opcional, se ignora si no es válida).
     *   - 8: Hora de salida HH:MM o HH:MM:SS (opcional).
     *   - 9: Fecha de llegada AAAA-MM-DD (opcional, se ignora si no es válida).
     * @return void Modifica el documento directamente.
     * @throws BatchProcessorException Si se indica solo el RUT o solo el
     * nombre del chofer.
     */
    private function addTransport(array &$documento, array $transporte): void
    {
        $vacios = true;

        // Verificar si todos los datos de transporte están vacíos.
        foreach ($transporte as $t) {
            if (!empty($t)) {
                $vacios = false;
            }
        }
        if ($vacios) {
            return;
        }

        // Procesar cada dato de transporte y agregarlo al documento si está
        // presente.
        if ($transporte[0]) {
            $documento['Encabezado']['Transporte']['Patente'] = mb_substr(
                trim($transporte[0]),
                0,
                8
            );
        }
        if ($transporte[1]) {
            $documento['Encabezado']['Transporte']['RUTTrans'] = mb_substr(
                str_replace('.', '', trim($transporte[1])),
                0,
                10
            );
        }
        if ($transporte[2] || $transporte[3]) {
            if (!$transporte[2]) {
                throw new BatchProcessorException('Falta RUT del chofer.');
            }
            if (!$transporte[3]) {
                throw new BatchProcessorException('Falta nombre del chofer.');
            }
            $documento['Encabezado']['Transporte']['Chofer']['RUTChofer'] =
                mb_substr(
                    str_replace('.', '', trim($transporte[2])),
                    0,
                    10
                )
            ;
            $documento['Encabezado']['Transporte']['Chofer']['NombreChofer'] =
                mb_substr(
                    trim($transporte[3]),
                    0,
                    30
                )
            ;
        }
        if ($transporte[4]) {
            $documento['Encabezado']['Transporte']['DirDest'] = mb_substr(
                trim($transporte[4]),
                0,
                70
            );
        }
        if ($transporte[5]) {
            $documento['Encabezado']['Transporte']['CmnaDest'] = mb_substr(
                trim($transporte[5]),
                0,
                20
            );
        }
        if ($transporte[6]) {
            $documento['Encabezado']['Transporte']['PatenteCarro'] = mb_substr(
                trim($transporte[6]),
                0,
                8
            );
        }
        if (
            $transporte[7]
            && Date::validateAndConvert(trim($transporte[7]), 'Y-m-d') !== null
        ) {
            $documento['Encabezado']['Transporte']['FchSalida'] = trim(
                $transporte[7]
            );
        }
        if ($transporte[8]) {
            // Si la hora viene como HH:MM se completa con los segundos.
            $documento['Encabezado']['Transporte']['HraSalida'] = preg_replace(
                '/^(\d{2}:\d{2})$/',
                '$1:00',
                trim($transporte[8])
            );
        }
        if (
            $transporte[9]
            && Date::validateAndConvert(trim($transporte[9]), 'Y-m-d') !== null
        ) {
            $documento['Encabezado']['Transporte']['FchLlegada'] = trim(
                $transporte[9]
            );
        }
    }

    /**
     * Agrega una referencia a un documento.
     *
     * Procesa los datos de referencia y los agrega al arreglo `Referencia`
     * dentro del documento. Valida los campos obligatorios y ajusta su longitud
     * si es necesario.
     *
     * @param array &$documento Documento al que se agregará la referencia.
     * Se pasa por referencia para modificarlo.
     * @param array $referencia Datos de la referencia a agregar. Los índices
     * deben ser:
     *   - 0: Tipo del documento referenciado (obligatorio).
     *   - 1: Folio del documento referenciado (obligatorio).
     *   - 2: Fecha del documento en formato AAAA-MM-DD (obligatorio).
     *   - 3: Código de referencia (opcional).
     *   - 4: Razón de la referencia (opcional).
     * @return void Modifica el documento directamente.
     * @throws BatchProcessorException Si algún campo obligatorio está vacío o
     * no es válido.
     */
    private function addReference(array &$documento, array $referencia): void
    {
        $Referencia = [];
        $vacios = true;
        foreach ($referencia as $r) {
            if (!empty($r)) {
                $vacios = false;
            }
        }
        if ($vacios) {
            return;
        }
        if (empty($referencia[0])) {
            throw new BatchProcessorException(
                'Tipo del documento de referencia no puede estar vacío.'
            );
        }
        $Referencia['TpoDocRef'] = mb_substr(trim($referencia[0]), 0, 3);
        if (trim((string) ($referencia[1] ?? '')) === '') {
            throw new BatchProcessorException(
                'Folio del documento de referencia no puede estar vacío.'
            );
        }
        $Referencia['FolioRef'] = mb_substr(trim($referencia[1]), 0, 18);

        // El folio 0 indica una referencia global a un conjunto de documentos.
        if (is_numeric($Referencia['FolioRef']) && $Referencia['FolioRef'] == 0) {
            $Referencia['IndGlobal'] = 1;
        }

        $fchRef = trim((string) ($referencia[2] ?? ''));
        if ($fchRef === '' || Date::validateAndConvert($fchRef, 'Y-m-d') === null) {
            throw new BatchProcessorException(
                'Fecha del documento de referencia debe ser en formato AAAA-MM-DD.'
            );
        }
        $Referencia['FchRef'] = $fchRef;
        if (!empty($referencia[3])) {
            $Referencia['CodRef'] = (int) $referencia[3];
        }
        if (!empty($referencia[4])) {
            $Referencia['RazonRef'] = mb_substr(trim($referencia[4]), 0, 90);
        }
        $documento['Referencia'][] = $Referencia;
    }

    /**
     * Obtiene la glosa de una moneda a partir de su código ISO.
     *
     * Este método busca en el repositorio de la entidad `AduanaMoneda` un
     * registro que coincida con el código ISO proporcionado. Si encuentra un
     * resultado, devuelve la glosa asociada; de lo contrario, retorna `null`.
     *
     * @param string $moneda Código ISO de la moneda que se desea buscar.
     * @return string|null La glosa de la moneda o `null` si no existe.
     */
    private function getCurrency(string $moneda): ?string
    {
        // Buscar la moneda a través del repositorio.
        $result = $this->repositoryManager
            ->getRepository(AduanaMoneda::class)
            ->findBy([
                'codigo_iso' => $moneda,
            ]);

        // Retornar null si no se encuentra ningún resultado.
        if (empty($result)) {
            return null;
        }

        // Retornar la glosa de la primera coincidencia.
        return $result[0]->getGlosa();
    }
}
