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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Backbone\Contract\ComponentInterface;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Contract\OptionsInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use stdClass;

/**
 * Interfaz para `DocumentComponent`.
 */
interface DocumentComponentInterface extends ComponentInterface
{
    /**
     * Entrega el worker "billing.document.batch_processor".
     *
     * @return BatchProcessorWorkerInterface
     */
    public function getBatchProcessorWorker(): BatchProcessorWorkerInterface;

    /**
     * Entrega el worker "billing.document.builder".
     *
     * @return BuilderWorkerInterface
     */
    public function getBuilderWorker(): BuilderWorkerInterface;

    /**
     * Entrega el worker "billing.document.dispatcher".
     *
     * @return DispatcherWorkerInterface
     */
    public function getDispatcherWorker(): DispatcherWorkerInterface;

    /**
     * Entrega el worker "billing.document.examples".
     *
     * @return ExamplesWorkerInterface
     */
    public function getExamplesWorker(): ExamplesWorkerInterface;

    /**
     * Entrega el worker "billing.document.document_bag_manager".
     *
     * @return DocumentBagManagerWorkerInterface
     */
    public function getDocumentBagManagerWorker(): DocumentBagManagerWorkerInterface;

    /**
     * Entrega el worker "billing.document.loader".
     *
     * @return LoaderWorkerInterface
     */
    public function getLoaderWorker(): LoaderWorkerInterface;

    /**
     * Entrega el worker "billing.document.normalizer".
     *
     * @return NormalizerWorkerInterface
     */
    public function getNormalizerWorker(): NormalizerWorkerInterface;

    /**
     * Entrega el worker "billing.document.parser".
     *
     * @return ParserWorkerInterface
     */
    public function getParserWorker(): ParserWorkerInterface;

    /**
     * Entrega el worker "billing.document.renderer".
     *
     * @return RendererWorkerInterface
     */
    public function getRendererWorker(): RendererWorkerInterface;

    /**
     * Entrega el worker "billing.document.sanitizer".
     *
     * @return SanitizerWorkerInterface
     */
    public function getSanitizerWorker(): SanitizerWorkerInterface;

    /**
     * Entrega el worker "billing.document.validator".
     *
     * @return ValidatorWorkerInterface
     */
    public function getValidatorWorker(): ValidatorWorkerInterface;

    /**
     * Ejecuta el proceso de facturación sobre los datos.
     *
     * Si se pasa un CAF el documento será timbrado.
     *
     * Si se pasa un CAF el documento será firmado.
     *
     * @param string|array|stdClass $data
     * @param string|CafInterface|null $caf
     * @param string|array|CertificateInterface|null $certificate
     * @param array|OptionsInterface $options
     * @return DocumentBagInterface
     */
    public function bill(
        string|array|stdClass $data,
        string|CafInterface|null $caf = null,
        string|array|CertificateInterface|null $certificate = null,
        array|OptionsInterface $options = []
    ): DocumentBagInterface;

    /**
     * Carga un XML de documento tributario o sobre de documentos tributarios.
     *
     * @param string $xml XML de un documento o sobre con uno o más documentos.
     * @return array<DocumentBagInterface> Bolsas con los documentos cargados.
     */
    public function loadXml(string $xml): array;
}
