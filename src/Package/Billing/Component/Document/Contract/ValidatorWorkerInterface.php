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

use Derafu\Backbone\Contract\StrategiesAwareInterface;
use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ValidatorException;

/**
 * Interfaz para los validadores de documentos.
 */
interface ValidatorWorkerInterface extends WorkerInterface, StrategiesAwareInterface
{
    /**
     * Realiza la validación del documento.
     *
     * @param DocumentBagInterface|XmlDocumentInterface|string $source
     * @return void
     * @throws ValidatorException
     */
    public function validate(
        DocumentBagInterface|XmlDocumentInterface|string $source
    ): void;

    /**
     * Valida el esquema del XML del DTE.
     *
     * @param DocumentBagInterface|XmlDocumentInterface|string $source
     * @return void
     * @throws XmlException Si la validación del esquema falla.
     */
    public function validateSchema(
        DocumentBagInterface|XmlDocumentInterface|string $source
    ): void;

    /**
     * Valida la firma electrónica del documento XML del DTE.
     *
     * @param DocumentBagInterface|XmlDocumentInterface|string $source
     * @return void
     * @throws SignatureException Si la validación de la firma falla.
     */
    public function validateSignature(
        DocumentBagInterface|XmlDocumentInterface|string $source
    ): void;
}
