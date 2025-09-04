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

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DispatcherException;

/**
 * Interfaz para el worker que maneja el envío de los documentos.
 */
interface DispatcherWorkerInterface extends WorkerInterface
{
    /**
     * Crea un sobre con los datos de la bolsa de un documento tributario.
     *
     * @param DocumentBagInterface $bag
     * @return DocumentEnvelopeInterface
     */
    public function create(DocumentBagInterface $bag): DocumentEnvelopeInterface;

    /**
     * Normaliza un sobre con datos de los documentos tributarios transferidos.
     *
     * Se completará el contenido que falte con lo que se pueda completar según
     * sea el contenido del sobre.
     *
     * @param DocumentEnvelopeInterface $envelope
     * @return DocumentEnvelopeInterface
     */
    public function normalize(
        DocumentEnvelopeInterface $envelope
    ): DocumentEnvelopeInterface;

    /**
     * Realiza la carga del sobre de documentos desde un string XML.
     *
     * @param string $xml Datos del sobre de documentos tributarios.
     * @return DocumentEnvelopeInterface Contenedor con los datos del sobre.
     * @throws DispatcherException
     */
    public function loadXml(string $xml): DocumentEnvelopeInterface;

    /**
     * Realiza la validación del sobre de documentos tributarios.
     *
     * @param DocumentEnvelopeInterface|XmlDocumentInterface|string $source
     * @return XmlDocumentInterface
     * @throws DispatcherException
     */
    public function validate(
        DocumentEnvelopeInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface;

    /**
     * Valida el esquema del XML del sobre de documentos tributarios.
     *
     * @param DocumentEnvelopeInterface|XmlDocumentInterface|string $source
     * @return XmlDocumentInterface
     * @throws XmlException Si la validación del esquema falla.
     */
    public function validateSchema(
        DocumentEnvelopeInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface;

    /**
     * Valida la firma electrónica del sobre de documentos tributarios.
     *
     * @param DocumentEnvelopeInterface|XmlDocumentInterface|string $source
     * @return XmlDocumentInterface
     * @throws SignatureException Si la validación de la firma falla.
     */
    public function validateSignature(
        DocumentEnvelopeInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface;
}
