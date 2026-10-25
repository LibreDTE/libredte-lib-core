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

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Contract\OptionsAwareInterface;
use JsonSerializable;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Interfaz para el contenedor de varios documentos que se procesarán en lote.
 */
interface DocumentBatchInterface extends OptionsAwareInterface, JsonSerializable
{
    /**
     * Entrega la ruta del archivo con los documentos que se deben procesar.
     *
     * @return string|null Ruta del archivo, o `null` si el lote se creó a
     * partir del contenido.
     */
    public function getInputFile(): ?string;

    /**
     * Entrega el contenido con los documentos que se deben procesar.
     *
     * Si el lote se creó a partir de un archivo, el contenido se lee la primera
     * vez que se solicita.
     *
     * @return string
     * @throws BatchProcessorException Si no es posible leer el archivo.
     */
    public function getInputData(): string;

    /**
     * Asigna el emisor del documento.
     *
     * @param EmisorInterface|null $emisor
     * @return static
     */
    public function setEmisor(?EmisorInterface $emisor): static;

    /**
     * Obtiene el emisor del documento.
     *
     * @return EmisorInterface|null
     */
    public function getEmisor(): ?EmisorInterface;

    /**
     * Asigna el certificado para firmar el documento.
     *
     * @param CertificateInterface|null $certificate
     * @return static
     */
    public function setCertificate(?CertificateInterface $certificate): static;

    /**
     * Obtiene el certificado para firmar el documento.
     *
     * @return CertificateInterface|null
     */
    public function getCertificate(): ?CertificateInterface;

    /**
     * Asigna el listado de bolsas con documentos procesados en lote.
     *
     * @param DocumentBagInterface[] $bags
     * @return static
     */
    public function setDocumentBags(array $bags): static;

    /**
     * Obtiene el listado de bolsas con documentos procesados en lote.
     *
     * @return DocumentBagInterface[]
     */
    public function getDocumentBags(): array;

    /**
     * Obtiene las opciones del procesamiento en lote.
     *
     * @return array Opciones del batch processor.
     */
    public function getBatchProcessorOptions(): array;

    /**
     * Convierte el lote a un array.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
