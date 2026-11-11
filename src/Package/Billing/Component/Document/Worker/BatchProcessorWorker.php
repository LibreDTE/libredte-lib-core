<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Array\ArrayReader;
use Derafu\Array\ArrayWriter;
use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\HandlerInterface;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Translation\Exception\Core\TranslatableRuntimeException as RuntimeException;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BatchProcessorStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BatchProcessorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBatchInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Service\CafManager;
use Throwable;

/**
 * Clase para los procesadores de documentos en lote.
 *
 * Las estrategias (`BatchProcessorStrategyInterface`) son parsers: transforman
 * los datos de entrada del lote (un CSV, una planilla, etc.) en el listado de
 * documentos tributarios. El worker toma ese listado y crea los documentos, uno
 * a uno.
 */
#[Worker(name: 'batch_processor', component: 'document', package: 'billing')]
class BatchProcessorWorker extends AbstractWorker implements BatchProcessorWorkerInterface
{
    use StrategiesAwareTrait;

    /**
     * Esquema de las opciones.
     *
     * @var array<string, array<string, mixed>|bool>
     */
    protected array $optionsSchema = [
        '__allowUndefinedKeys' => true,
        'strategy' => [
            'types' => 'string',
            'default' => 'spreadsheet.csv',
        ],
        'complete' => [
            'types' => 'bool',
            'default' => true,
        ],
        'stamp' => [
            'types' => 'bool',
            'default' => true,
        ],
    ];

    /**
     * Constructor del worker y sus dependencias.
     *
     * @param CafFakerWorkerInterface $cafFaker
     * @param DocumentBagManagerWorkerInterface $documentBagManagerWorker
     * @param BuilderWorkerInterface $builderWorker
     * @param iterable<array-key, JobInterface> $jobs
     * @param iterable<array-key, HandlerInterface> $handlers
     * @param iterable<array-key, StrategyInterface> $strategies
     */
    public function __construct(
        private CafFakerWorkerInterface $cafFaker,
        private DocumentBagManagerWorkerInterface $documentBagManagerWorker,
        private BuilderWorkerInterface $builderWorker,
        iterable $jobs = [],
        iterable $handlers = [],
        iterable $strategies = []
    ) {
        $this->setJobs($jobs);
        $this->setHandlers($handlers);
        $this->setStrategies($strategies);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsSchema(): array
    {
        return $this->optionsSchema;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'parse',
        parameters: [
            'batch' => [
                'example' => [
                    'emisor' => [
                        'rut' => '76192083-9',
                        'razon_social' => 'SASCO SpA',
                    ],
                    'inputData' => '...',
                    'options' => [
                        'batch_processor' => [
                            'strategy' => 'spreadsheet.csv',
                            'complete' => true,
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function parse(DocumentBatchInterface $batch): DocumentBatchInterface
    {
        $emisor = $batch->getEmisor();
        $options = $this->resolveOptions($batch->getBatchProcessorOptions());

        // Parsear los datos de entrada del lote para obtener los documentos.
        $parsedDocuments = $this->parseDocuments($batch);

        // Crear la bolsa de cada documento.
        $documentBags = [];
        foreach ($parsedDocuments as $parsedData) {
            $documentBag = new DocumentBag();
            $documentBag->setEmisor($emisor);

            // Completar el documento si así se solicitó.
            if ($options->getReader()->requireBool('complete')) {
                $parsedData = $this->completeParsedData(
                    $batch,
                    $parsedData
                );
            }

            // Asignar documento parseado desde el archivo masivo.
            $documentBag->setParsedData($parsedData);

            // Agregar la bolsa al listado de bolsas que se generaron a partir
            // del archivo de emisión masiva.
            $documentBags[] = $documentBag;
        }

        // Asignar bolsas con los documentos al lote procesado.
        $batch->setDocumentBags($documentBags);

        // Entregar el lote con las bolsas de documentos.
        return $batch;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DocumentBatchInterface $batch): DocumentBatchInterface
    {
        $emisor = $batch->requireEmisor();
        $options = $this->resolveOptions($batch->getBatchProcessorOptions());

        // Parsear los datos de entrada del lote. Con esto el lote queda con la
        // bolsa de cada documento, con sus datos parseados.
        $this->parse($batch);

        // Manager de folios y seguimiento del último folio por tipo de
        // documento, para este lote.
        $cafManager = new CafManager();
        $lastFolios = [];

        // Crear el documento de cada bolsa.
        foreach ($batch->getDocumentBags() as $documentBag) {
            // Normalizar lo básico de la bolsa del documento.
            // Esto es para poder tener el tipo de documento de los datos
            // parseados.
            $this->documentBagManagerWorker->normalize($documentBag);

            // Solicitar CAF y asignar certificado si se solicitó.
            if ($options->getReader()->requireBool('stamp')) {
                $folio = $documentBag->getFolio();
                $folio = is_int($folio) ? $folio : null;
                $tipoDoc = ValueCaster::requireInt(
                    $documentBag->requireTipoDocumento()->getCodigo()
                );

                if ($folio === null) {
                    // Auto-asignar el siguiente folio disponible del lote.
                    if (!$cafManager->hasEnoughFolios($tipoDoc)) {
                        $start = ($lastFolios[$tipoDoc] ?? 0) + 1;
                        $fakeCaf = $this->cafFaker->create($emisor, $tipoDoc, $start, $start + 999);
                        $cafManager->add($fakeCaf->saveXml());
                    }
                    $cafFolio = $cafManager->consume($tipoDoc);
                    $documentBag->setFolio($cafFolio->getFolio());
                    $documentBag->setCaf($cafFolio->getCaf());
                    $lastFolios[$tipoDoc] = $cafFolio->getFolio();
                } else {
                    // El documento trae folio preestablecido: buscar o generar
                    // el CAF que lo cubra.
                    try {
                        $caf = $cafManager->getCafForFolio($tipoDoc, $folio);
                    } catch (RuntimeException $e) {
                        $fakeCaf = $this->cafFaker->create($emisor, $tipoDoc, $folio, $folio);
                        $cafManager->add($fakeCaf->saveXml());
                        $caf = $cafManager->getCafForFolio($tipoDoc, $folio);
                    }
                    $documentBag->setCaf($caf);
                }

                // Asignar certificado a la bolsa del documento.
                $documentBag->setCertificate($batch->getCertificate());
            }

            // Construir el documento a partir de los datos de la bolsa.
            $this->builderWorker->build($documentBag);
        }

        // Entregar el lote con las bolsas de documentos.
        return $batch;
    }

    /**
     * Parsea los datos de entrada del lote, con la estrategia que se haya
     * solicitado, y entrega los datos de los documentos que se deben crear.
     *
     * @param DocumentBatchInterface $batch
     * @return array<int, array<string, mixed>>
     */
    private function parseDocuments(DocumentBatchInterface $batch): array
    {
        $options = $this->resolveOptions($batch->getBatchProcessorOptions());
        $strategy = $this->getStrategy($options->getReader()->requireString('strategy'));

        assert($strategy instanceof BatchProcessorStrategyInterface);

        try {
            $documents = $strategy->parse($batch);
        } catch (Throwable $e) {
            throw new BatchProcessorException(
                message: [
                    'Could not process the document batch: {detail}',
                    'detail' => $e,
                ],
                documentBatch: $batch
            );
        }

        return $documents;
    }

    /**
     * Completa los datos del documento parseado desde el archivo masivo.
     *
     * @param DocumentBatchInterface $batch
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function completeParsedData(
        DocumentBatchInterface $batch,
        array $data
    ): array {
        $emisor = $batch->getEmisor();

        // Sin emisor en el lote no hay datos con los que completar.
        if ($emisor === null) {
            return $data;
        }

        // Los datos del lote completan los que el documento no trae.
        $defaults = [
            'RUTEmisor' => $emisor->getRut(),
            'RznSoc' => $emisor->getRazonSocial(),
            'GiroEmis' => $emisor->getGiro(),
            'Telefono' => $emisor->getTelefono(),
            'CorreoEmisor' => $emisor->getEmail(),
            'Acteco' => $emisor->getActividadEconomica(),
            'DirOrigen' => $emisor->getDireccion(),
            'CmnaOrigen' => $emisor->getComuna(),
            'CdgSIISucur' => $emisor->getCodigoSucursal(),
            'CdgVendedor' => $emisor->getVendedor(),
        ];

        $reader = ArrayReader::fromArray($data, 'document');
        $writer = ArrayWriter::fromReference($data);
        foreach ($defaults as $field => $default) {
            $path = 'Encabezado.Emisor.' . $field;
            $writer->set($path, $reader->get($path, ValueType::mixed()) ?: $default);
        }

        return $data;
    }
}
