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

namespace libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Signature\Contract\SignatureValidationResultInterface;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlException;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\Aec;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Exception\OwnershipTransferException;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Support\AecBag;
use NoDiscard;

/**
 * Interfaz para `AecWorker`.
 */
interface AecWorkerInterface extends WorkerInterface
{
    /**
     * Construye el AEC completo: `DTECedido`, `Cesion` y documento raíz
     * `AEC`.
     *
     * @param AecBag $bag Bolsa con el DTE, cedente, cesionario, cesión y
     *   certificado.
     * @return Aec El AEC construido y firmado.
     * @throws OwnershipTransferException En caso de error.
     */
    public function build(AecBag $bag): Aec;

    /**
     * Valida el esquema XSD del AEC.
     *
     * @param Aec|XmlDocumentInterface|string $source Origen a validar: el
     * AEC ya construido, o su XML ya construido (como documento o como
     * string).
     * @return XmlDocumentInterface El documento XML validado.
     * @throws XmlException Si la validación del esquema falla.
     * @throws OwnershipTransferException Si no se puede determinar el esquema.
     */
    public function validateSchema(
        Aec|XmlDocumentInterface|string $source
    ): XmlDocumentInterface;

    /**
     * Valida la(s) firma(s) electrónica(s) del AEC.
     *
     * El AEC contiene múltiples firmas: `DTECedido`, `Cesion` y `AEC`. Se
     * retornan todos los resultados.
     *
     * @param Aec|XmlDocumentInterface|string $source Origen a validar: el
     * AEC ya construido, o su XML ya construido (como documento o como
     * string).
     * @return array<SignatureValidationResultInterface> El resultado de
     * validar cada firma electrónica encontrada en el AEC.
     * @throws SignatureException Si el XML está mal formado o no contiene
     * firmas.
     */
    #[NoDiscard()]
    public function validateSignature(
        Aec|XmlDocumentInterface|string $source
    ): array;
}
