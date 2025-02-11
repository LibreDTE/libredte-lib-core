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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BatchProcessorStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BatchProcessorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBatchInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafProviderInterface;
use Throwable;

/**
 * Clase para los procesadores de documentos en lote.
 */
class BatchProcessorWorker extends AbstractWorker implements BatchProcessorWorkerInterface
{
    /**
     * Esquema de las opciones.
     *
     * @var array<string,array|bool>
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
     * @param CafProviderInterface $cafProvider
     * @param DocumentBagManagerWorkerInterface $documentBagManagerWorker
     * @param BuilderWorkerInterface $builderWorker
     * @param array $jobs
     * @param array $handlers
     * @param array $strategies
     */
    public function __construct(
        private CafProviderInterface $cafProvider,
        private DocumentBagManagerWorkerInterface $documentBagManagerWorker,
        private BuilderWorkerInterface $builderWorker,
        iterable $jobs = [],
        iterable $handlers = [],
        iterable $strategies = []
    ) {
        parent::__construct($jobs, $handlers, $strategies);
    }

    /**
     * {@inheritDoc}
     */
    public function process(DocumentBatchInterface $batch): array
    {
        $emisor = $batch->getEmisor();
        $options = $this->resolveOptions($batch->getOptions());

        // Cargar documentos desde el archivo.
        $parsedDocuments = $this->loadDocumentsFromFile($batch);

        // Crear la bolsa de cada documento.
        $documentBags = [];
        foreach ($parsedDocuments as $parsedData) {
            $documentBag = new DocumentBag();
            $documentBag->setEmisor($emisor);

            // Completar el documento si así se solicitó.
            if ($options->get('complete')) {
                $parsedData = $this->completeParsedData(
                    $batch,
                    $parsedData
                );
            }

            // Asignar documento parseado desde el archivo masivo.
            $documentBag->setParsedData($parsedData);

            // Normalizar lo básico de la bolsa del documento.
            // Esto es para poder tener el tipo de documento de los datos
            // parseados.
            $this->documentBagManagerWorker->normalize($documentBag);

            // Solicitar CAF al proveedor y asignar certificado si se solicitó.
            if ($options->get('stamp')) {
                // Buscar folio del documento (si existe) y obtener el CAF que
                // tiene ese folio.
                $folio = $documentBag->getFolio();
                $folio = is_int($folio) ? $folio : null;
                $cafBag = $this->cafProvider->retrieve(
                    $emisor,
                    $documentBag->getTipoDocumento(),
                    $folio
                );

                // Si no había un folio en el documento se deberá asignar el
                // siguiente folio del CAF a los datos de la bolsa del
                // documento.
                if ($folio === null) {
                    $siguienteFolio = $cafBag->getSiguienteFolio();
                    $documentBag->setFolio($siguienteFolio);
                }

                // Asignar CAF y certificado a la bolsa del documento.
                $documentBag->setCaf($cafBag->getCaf());
                $documentBag->setCertificate($batch->getCertificate());
            }

            // Construir el documento a partir de los datos de la bolsa.
            $this->builderWorker->build($documentBag);

            // Agregar la bolsa al listado de bolsas que se generaron a partir
            // del archivo de emisión masiva.
            $documentBags[] = $documentBag;
        }

        // Asignar bolsas con los documentos al lote procesado.
        $batch->setDocumentBags($documentBags);

        // Entregar las bolsas de documentos.
        return $documentBags;
    }

    /**
     * Carga los documentos desde el archivo según la estrategia de
     * procesamiento en lote que se haya solicitado.
     *
     * @param DocumentBatchInterface $batch
     * @return array
     */
    private function loadDocumentsFromFile(DocumentBatchInterface $batch): array
    {
        $options = $this->resolveOptions($batch->getBatchProcessorOptions());
        $strategy = $this->getStrategy($options->get('strategy'));

        assert($strategy instanceof BatchProcessorStrategyInterface);

        try {
            $documents = $strategy->process($batch);
        } catch (Throwable $e) {
            throw new BatchProcessorException(
                message: $e->getMessage(),
                documentBatch: $batch
            );
        }

        return $documents;
    }

    /**
     * Completa los datos del documento parseado desde el archivo masivo.
     *
     * @param DocumentBatchInterface $batch
     * @param array $data
     * @return array
     */
    private function completeParsedData(
        DocumentBatchInterface $batch,
        array $data
    ): array {
        $emisor = $batch->getEmisor();

        $data['Encabezado']['Emisor']['RUTEmisor'] =
            ($data['Encabezado']['Emisor']['RUTEmisor'] ?? false)
            ?: $emisor->getRut()
        ;
        $data['Encabezado']['Emisor']['RznSoc'] =
            ($data['Encabezado']['Emisor']['RznSoc'] ?? false)
            ?: $emisor->getRazonSocial()
        ;
        $data['Encabezado']['Emisor']['GiroEmis'] =
            ($data['Encabezado']['Emisor']['GiroEmis'] ?? false)
            ?: ($emisor->getGiro() ?? false)
        ;
        $data['Encabezado']['Emisor']['Telefono'] =
            ($data['Encabezado']['Emisor']['Telefono'] ?? false)
            ?: ($emisor->getTelefono() ?? false)
        ;
        $data['Encabezado']['Emisor']['CorreoEmisor'] =
            ($data['Encabezado']['Emisor']['CorreoEmisor'] ?? false)
            ?: ($emisor->getEmail() ?? false)
        ;
        $data['Encabezado']['Emisor']['Acteco'] =
            ($data['Encabezado']['Emisor']['Acteco'] ?? false)
            ?: ($emisor->getActividadEconomica() ?? false)
        ;
        $data['Encabezado']['Emisor']['DirOrigen'] =
            ($data['Encabezado']['Emisor']['DirOrigen'] ?? false)
            ?: ($emisor->getDireccion() ?? false)
        ;
        $data['Encabezado']['Emisor']['CmnaOrigen'] =
            ($data['Encabezado']['Emisor']['CmnaOrigen'] ?? false)
            ?: ($emisor->getComuna() ?? false)
        ;
        $data['Encabezado']['Emisor']['CdgSIISucur'] =
            ($data['Encabezado']['Emisor']['CdgSIISucur'] ?? false)
            ?: ($emisor->getCodigoSucursal() ?? false)
        ;
        $data['Encabezado']['Emisor']['CdgVendedor'] =
            ($data['Encabezado']['Emisor']['CdgVendedor'] ?? false)
            ?: ($emisor->getVendedor() ?? false)
        ;

        return $data;
    }
}
