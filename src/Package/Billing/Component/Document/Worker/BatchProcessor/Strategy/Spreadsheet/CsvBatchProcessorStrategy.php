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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessor\Strategy\Spreadsheet;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Support\Csv;
use Derafu\Support\Date;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BatchProcessorStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBatchInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaMoneda;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;

/**
 * Estrategia "billing.document.batch_processor.strategy:spreadsheet.csv".
 *
 * Procesa en lote los documentos tributarios de un archivo CSV con el formato
 * estándar de LibreDTE.
 */
#[Strategy(name: 'spreadsheet.csv', worker: 'batch_processor', component: 'document', package: 'billing')]
class CsvBatchProcessorStrategy extends AbstractStrategy implements BatchProcessorStrategyInterface
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
     * {@inheritDoc}
     */
    public function process(DocumentBatchInterface $batch): array
    {
        // Cargar archivo CSV y obtener los datos.
        $data = Csv::read($batch->getFile());
        $n_data = count($data);
        $documentos = [];
        $documento = [];

        // Procesar cada fila del archivo.
        for ($i = 1; $i < $n_data; $i++) {
            // Si la fila corresponde a un documento nuevo.
            if (!empty($data[$i][0])) {
                // Agregar el documento actual al listado si existe.
                if ($documento) {
                    $documentos[] = $documento;
                }
                // Crear un nuevo documento.
                $documento = $this->createDocument($data[$i]);
            } else {
                // Si la fila no corresponde a un documento nuevo, agregar
                // detalles al documento actual.
                if (!empty($data[$i][13])) {
                    $dataItem = array_merge(
                        // Datos originales del item (vienen juntos en el
                        // archivo).
                        array_slice($data[$i], 11, 8),

                        // Datos adicionales del item (vienen después del item,
                        // "al final", porque se añadieron después de los
                        // previos al archivo).
                        [
                            // CodImpAdic.
                            !empty($data[$i][38]) ? $data[$i][38] : null,
                        ]
                    );

                    $this->addItem($documento, $dataItem);
                }

                // Agregar referencias al documento.
                $this->addReference($documento, array_slice($data[$i], 28, 5));
            }
        }

        // Agregar el último documento procesado al listado.
        $documentos[] = $documento;

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
     *   - 7: Contacto del receptor (opcional).
     *   - 8: Correo del receptor (opcional, validado si se proporciona).
     *   - 9: Dirección del receptor (obligatoria si no es boleta).
     *   - 10: Comuna del receptor (obligatoria si no es boleta).
     *   - 33: Tipo de moneda (opcional, por defecto USD si aplica).
     *   - 34: Número de identificación del receptor extranjero (opcional).
     *   - 35: Descuento global (opcional, porcentaje o monto).
     *   - 36: Nombre del PDF (opcional).
     *   - 37: Forma de pago (opcional, 1, 2 o 3).
     *   - 38: Código de impuesto adicional (opcional).
     * @return array Estructura del documento generado.
     * @throws BatchProcessorException Si faltan datos mínimos o son inválidos.
     */
    private function createDocument(array $data): array
    {
        // Verificar datos mínimos obligatorios.
        if (empty($data[0])) {
            throw new BatchProcessorException('Falta tipo de documento.');
        }
        if (empty($data[1])) {
            throw new BatchProcessorException('Falta folio del documento.');
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

        // Procesar descuentos globales.
        if (!empty($data[35])) {
            if (strpos($data[35], '%')) {
                $TpoValor_global = '%';
                $ValorDR_global = (float)substr($data[35], 0, -1);
            } else {
                $TpoValor_global = '$';
                $ValorDR_global = (float)$data[35];
            }
            $documento['DscRcgGlobal'][] = [
                'TpoMov' => 'D',
                'TpoValor' => $TpoValor_global,
                'ValorDR' => $ValorDR_global,
                'IndExeDR' => 1,
            ];
        }

        // Asignar el nombre del PDF si se proporciona.
        // Esto permite asociar un archivo PDF específico al documento.
        if (!empty($data[36])) {
            $documento['LibreDTE']['pdf']['nombre'] = $data[36];
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
        $this->addTransport($documento, array_slice($data, 22, 6));
        $this->addReference($documento, array_slice($data, 28, 5));

        return $documento;
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
                        ? mb_substr(trim($data[7]), 0, 80)
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
     *   - 4: Cantidad del ítem (obligatorio).
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
        if (empty($item[4])) {
            throw new BatchProcessorException(
                'Falta cantidad del item.'
            );
        }
        if (empty($item[6])) {
            throw new BatchProcessorException(
                'Falta precio del item.'
            );
        }

        // Crear el detalle del ítem.
        $detalle = [
            'NmbItem' => mb_substr(trim($item[2]), 0, 80),
            'QtyItem' => (float)str_replace(',', '.', $item[4]),
            'PrcItem' => (float)str_replace(',', '.', $item[6]),
        ];

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
            if (strpos($item[7], '%')) {
                $detalle['DescuentoPct'] = (float)substr($item[7], 0, -1);
            } else {
                $detalle['DescuentoMonto'] = (float)$item[7];
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
     * patente, transportista, chofer y destino.
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
     * @return void Modifica el documento directamente.
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
        if ($transporte[2] && $transporte[3]) {
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
        if (empty($referencia[1])) {
            throw new BatchProcessorException(
                'Folio del documento de referencia no puede estar vacío.'
            );
        }
        $Referencia['FolioRef'] = mb_substr(trim($referencia[1]), 0, 18);

        if (
            empty($referencia[2])
            && Date::validateAndConvert($referencia[2], 'Y-m-d') !== null
        ) {
            throw new BatchProcessorException(
                'Fecha del documento de referencia debe ser en formato AAAA-MM-DD.'
            );
        }
        $Referencia['FchRef'] = $referencia[2];
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
