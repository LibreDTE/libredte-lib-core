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

namespace libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Signature\Contract\SignatureValidationResultInterface;
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
     * Construye el AEC completo: DTECedido, Cesion y documento raíz AEC.
     *
     * @param AecBag $bag Contenedor con el DTE, cedente, cesionario, cesión y
     *   certificado.
     * @return Aec
     * @throws OwnershipTransferException En caso de error.
     */
    public function build(AecBag $bag): Aec;

    /**
     * Valida el esquema XSD del AEC.
     *
     * @param Aec|XmlDocumentInterface|string $source
     * @return XmlDocumentInterface El documento XML validado.
     * @throws XmlException Si la validación del esquema falla.
     * @throws OwnershipTransferException Si no se puede determinar el esquema.
     */
    #[NoDiscard()]
    public function validateSchema(
        Aec|XmlDocumentInterface|string $source
    ): XmlDocumentInterface;

    /**
     * Valida la(s) firma(s) electrónica(s) del AEC.
     *
     * El AEC contiene múltiples firmas: DTECedido, Cesion y AEC. Se retornan
     * todos los resultados.
     *
     * @param Aec|XmlDocumentInterface|string $source
     * @return array<SignatureValidationResultInterface>
     */
    #[NoDiscard()]
    public function validateSignature(
        Aec|XmlDocumentInterface|string $source
    ): array;
}
