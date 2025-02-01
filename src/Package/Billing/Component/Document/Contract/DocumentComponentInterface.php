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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Lib\Core\Foundation\Contract\ComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Support\Store\Contract\DataContainerInterface;
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
     * @param array|DataContainerInterface $options
     * @return DocumentBagInterface
     */
    public function bill(
        string|array|stdClass $data,
        string|CafInterface $caf = null,
        string|array|CertificateInterface $certificate = null,
        array|DataContainerInterface $options = []
    ): DocumentBagInterface;
}
