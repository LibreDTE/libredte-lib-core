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

use Derafu\Backbone\Contract\StrategiesAwareInterface;
use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Signature\Contract\SignatureValidationResultInterface;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ValidatorException;
use NoDiscard;

/**
 * Interfaz para los validadores de documentos.
 */
interface ValidatorWorkerInterface extends WorkerInterface, StrategiesAwareInterface
{
    /**
     * Realiza la validación de los datos del documento.
     *
     * @param DocumentBagInterface|XmlDocumentInterface|string $source
     * Origen a validar: el documento con sus datos ya cargados en la
     * bolsa, o su XML ya construido (como documento o como string).
     * @return void
     * @throws ValidatorException
     */
    public function validate(
        DocumentBagInterface|XmlDocumentInterface|string $source
    ): void;

    /**
     * Valida el esquema del XML del documento.
     *
     * @param DocumentBagInterface|XmlDocumentInterface|string $source
     * Origen a validar: el documento con sus datos ya cargados en la
     * bolsa, o su XML ya construido (como documento o como string).
     * @return XmlDocumentInterface El documento XML validado.
     * @throws XmlException Si la validación del esquema falla.
     */
    public function validateSchema(
        DocumentBagInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface;

    /**
     * Valida la firma electrónica del XML del documento.
     *
     * @param DocumentBagInterface|XmlDocumentInterface|string $source
     * Origen a validar: el documento con sus datos ya cargados en la
     * bolsa, o su XML ya construido (como documento o como string).
     * @return SignatureValidationResultInterface El resultado de validar
     * la firma electrónica del documento.
     * @throws SignatureException Si la validación de la firma falla.
     */
    #[NoDiscard()]
    public function validateSignature(
        DocumentBagInterface|XmlDocumentInterface|string $source
    ): SignatureValidationResultInterface;
}
