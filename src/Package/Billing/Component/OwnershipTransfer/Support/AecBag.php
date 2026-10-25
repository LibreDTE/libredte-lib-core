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

namespace libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Support;

use Derafu\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\Aec;

/**
 * Contenedor de datos para construir o re-ceder un Archivo Electrónico de Cesión (AEC).
 *
 * Dos modos de uso según el tipo de `$source`:
 *
 *   - `DocumentInterface`: primera cesión. Se construye el `DTECedido` desde
 *     el DTE dado y se añade la primera `Cesion` (seq = 1 por defecto).
 *     El `DocumentInterface` se obtiene cargando un XML de `EnvioDTE` mediante
 *     `DispatcherWorker::loadXml($xml)` → `DocumentEnvelope::getDocuments()[0]`.
 *
 *   - `Aec`: re-cesión. Se reutiliza el `DTECedido` y las `Cesion` existentes
 *     del AEC recibido y se añade una nueva `Cesion`. El número de secuencia
 *     se calcula automáticamente (máximo existente + 1) salvo que se informe
 *     explícitamente con `$seq`.
 */
class AecBag
{
    /**
     * @param DocumentInterface|Aec $source DTE para primera cesión, o AEC
     *   existente para re-cesión.
     * @param array<string, mixed> $cedente Datos del cedente:
     *   - `RUT`: RUT del cedente.
     *   - `RazonSocial`: razón social del cedente.
     *   - `Direccion`: dirección del cedente (mínimo 5 caracteres).
     *   - `eMail`: correo electrónico del cedente (mínimo 6 caracteres).
     *   - `RUTAutorizado`: arreglo o lista de arreglos con `RUT` y `Nombre`.
     * @param array<string, mixed> $cesionario Datos del cesionario:
     *   - `RUT`: RUT del cesionario.
     *   - `RazonSocial`: razón social del cesionario.
     *   - `Direccion`: dirección del cesionario (mínimo 5 caracteres).
     *   - `eMail`: correo electrónico del cesionario (mínimo 6 caracteres).
     * @param array<string, mixed> $cesion Datos de la cesión:
     *   - `MontoCesion`: monto cedido.
     *   - `UltimoVencimiento`: fecha de último vencimiento (YYYY-MM-DD).
     * @param CertificateInterface|null $certificate Certificado para firmar.
     * @param int|null $seq Número de secuencia de la cesión. Si es `null` se
     *   calcula automáticamente (1 para primera cesión, máximo+1 para re-cesión).
     */
    public function __construct(
        private readonly DocumentInterface|Aec $source,
        private readonly array $cedente,
        private readonly array $cesionario,
        private readonly array $cesion,
        private readonly ?CertificateInterface $certificate = null,
        private readonly ?int $seq = null,
    ) {
    }

    /**
     * Entrega la fuente: un `DocumentInterface` (primera cesión) o un `Aec`
     * (re-cesión).
     *
     * @return DocumentInterface|Aec
     */
    public function getSource(): DocumentInterface|Aec
    {
        return $this->source;
    }

    /**
     * Indica si es una re-cesión (la fuente es un AEC existente).
     *
     * @return bool
     */
    public function isRecesion(): bool
    {
        return $this->source instanceof Aec;
    }

    public function getCedente(): array
    {
        return $this->cedente;
    }

    public function getCesionario(): array
    {
        return $this->cesionario;
    }

    public function getCesion(): array
    {
        return $this->cesion;
    }

    public function getCertificate(): ?CertificateInterface
    {
        return $this->certificate;
    }

    /**
     * Entrega el número de secuencia explícito, o `null` para que el job lo
     * calcule automáticamente.
     *
     * @return int|null
     */
    public function getSeq(): ?int
    {
        return $this->seq;
    }
}
