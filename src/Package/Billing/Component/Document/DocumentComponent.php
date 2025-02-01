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

namespace libredte\lib\Core\Package\Billing\Component\Document;

use Derafu\Lib\Core\Foundation\Abstract\AbstractComponent;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Support\Store\Contract\DataContainerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BatchProcessorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DispatcherWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\NormalizerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SanitizerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use stdClass;

/**
 * Componente "billing.document".
 *
 * Este componente se encarga de la creación, manipulación y renderización de
 * los documentos tributarios.
 */
class DocumentComponent extends AbstractComponent implements DocumentComponentInterface
{
    public function __construct(
        private BatchProcessorWorkerInterface $batchProcessorWorker,
        private BuilderWorkerInterface $builderWorker,
        private DispatcherWorkerInterface $dispatcherWorker,
        private DocumentBagManagerWorkerInterface $documentBagManagerWorker,
        private LoaderWorkerInterface $loaderWorker,
        private NormalizerWorkerInterface $normalizerWorker,
        private ParserWorkerInterface $parserWorker,
        private RendererWorkerInterface $rendererWorker,
        private SanitizerWorkerInterface $sanitizerWorker,
        private ValidatorWorkerInterface $validatorWorker,
        private CafLoaderWorkerInterface $cafLoader
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'batch_processor' => $this->batchProcessorWorker,
            'builder' => $this->builderWorker,
            'dispatcher' => $this->dispatcherWorker,
            'document_bag_manager' => $this->documentBagManagerWorker,
            'loader' => $this->loaderWorker,
            'normalizer' => $this->normalizerWorker,
            'parser' => $this->parserWorker,
            'renderer' => $this->rendererWorker,
            'sanitizer' => $this->sanitizerWorker,
            'validator' => $this->validatorWorker,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBatchProcessorWorker(): BatchProcessorWorkerInterface
    {
        return $this->batchProcessorWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getBuilderWorker(): BuilderWorkerInterface
    {
        return $this->builderWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getDispatcherWorker(): DispatcherWorkerInterface
    {
        return $this->dispatcherWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentBagManagerWorker(): DocumentBagManagerWorkerInterface
    {
        return $this->documentBagManagerWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getLoaderWorker(): LoaderWorkerInterface
    {
        return $this->loaderWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getNormalizerWorker(): NormalizerWorkerInterface
    {
        return $this->normalizerWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getParserWorker(): ParserWorkerInterface
    {
        return $this->parserWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getRendererWorker(): RendererWorkerInterface
    {
        return $this->rendererWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getSanitizerWorker(): SanitizerWorkerInterface
    {
        return $this->sanitizerWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getValidatorWorker(): ValidatorWorkerInterface
    {
        return $this->validatorWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function bill(
        string|array|stdClass $data,
        string|CafInterface $caf = null,
        string|array|CertificateInterface $certificate = null,
        array|DataContainerInterface $options = []
    ): DocumentBagInterface {
        // Si el CAF es un string se debe construir el CAF.
        if (is_string($caf)) {
            $cafBag = $this->cafLoader->load($caf);
            $caf = $cafBag->getCaf();
        }

        // Crear contenedor del documento.
        $bag = new DocumentBag(
            inputData: $data,
            options: $options,
            caf: $caf,
            certificate: $certificate
        );

        // Parsear los datos de entrada pasados para crear el documento.
        $this->parserWorker->parse($bag);

        // Construir el documento tributario.
        $this->builderWorker->build($bag);

        // Entregar contenedor del documento.
        return $bag;
    }
}
