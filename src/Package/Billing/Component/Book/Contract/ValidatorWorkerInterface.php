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

namespace libredte\lib\Core\Package\Billing\Component\Book\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Signature\Contract\SignatureValidationResultInterface;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlException;
use NoDiscard;

/**
 * Interfaz para el validador de libros tributarios electrónicos.
 */
interface ValidatorWorkerInterface extends WorkerInterface
{
    /**
     * Valida el esquema XSD del XML del libro.
     *
     * Si se pasa una bolsa (`BookBagInterface`), el esquema se determina
     * según el tipo de libro asociado a esa bolsa. Si se pasa el XML
     * directamente, el esquema se detecta a partir del elemento raíz del
     * documento.
     *
     * @param BookBagInterface|XmlDocumentInterface|string $source Origen a
     * validar: la bolsa con el tipo de libro ya definido, o el XML del
     * libro ya construido (como documento o como string).
     * @return XmlDocumentInterface El documento XML validado.
     * @throws XmlException Si la validación del esquema falla.
     */
    public function validateSchema(
        BookBagInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface;

    /**
     * Valida la firma electrónica del XML del libro.
     *
     * @param BookBagInterface|XmlDocumentInterface|string $source Origen a
     * validar: la bolsa con el tipo de libro ya definido, o el XML del
     * libro ya construido (como documento o como string).
     * @return SignatureValidationResultInterface El resultado de validar
     * la firma electrónica del libro.
     * @throws SignatureException Si la validación de la firma falla.
     */
    #[NoDiscard()]
    public function validateSignature(
        BookBagInterface|XmlDocumentInterface|string $source
    ): SignatureValidationResultInterface;
}
