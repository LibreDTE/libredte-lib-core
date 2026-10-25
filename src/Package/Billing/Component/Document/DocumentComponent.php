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

namespace libredte\lib\Core\Package\Billing\Component\Document;

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BatchProcessorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DispatcherWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ExamplesWorkerInterface;
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
#[Component(name: 'document', package: 'billing')]
class DocumentComponent extends AbstractComponent implements DocumentComponentInterface
{
    public function __construct(
        private BatchProcessorWorkerInterface $batchProcessorWorker,
        private BuilderWorkerInterface $builderWorker,
        private DispatcherWorkerInterface $dispatcherWorker,
        private DocumentBagManagerWorkerInterface $documentBagManagerWorker,
        private ExamplesWorkerInterface $examplesWorker,
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
            'examples' => $this->examplesWorker,
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
    public function getExamplesWorker(): ExamplesWorkerInterface
    {
        return $this->examplesWorker;
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
        string|CafInterface|null $caf = null,
        string|array|CertificateInterface|null $certificate = null,
        array|OptionsInterface $options = []
    ): DocumentBagInterface {
        // Si el CAF es un string se debe construir el CAF.
        if (is_string($caf)) {
            $caf = $this->cafLoader->load($caf);
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

    public function loadXml(string $xml): array
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        if ($xmlDocument->getDocumentElement()->tagName === 'DTE') {
            $bag = $this->loaderWorker->loadXml($xmlDocument);
            return [$bag];
        }

        $envelope = $this->dispatcherWorker->loadXml($xmlDocument);

        return $envelope->getDocuments();
    }
}
