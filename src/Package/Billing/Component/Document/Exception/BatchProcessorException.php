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

namespace libredte\lib\Core\Package\Billing\Component\Document\Exception;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBatchInterface;
use Throwable;

/**
 * Excepción para el worker "billing.document.batch_processor".
 */
class BatchProcessorException extends DocumentException
{
    /**
     * Contenedor del lote de documentos que se estaba manipulando cuando se
     * generó la excepción.
     *
     * @var DocumentBatchInterface|null
     */
    protected ?DocumentBatchInterface $documentBatch = null;

    /**
     * Constructor de la excepción.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param DocumentBatchInterface|null $documentBatch
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?DocumentBatchInterface $documentBatch = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->documentBatch = $documentBatch;
    }

    /**
     * Entrega, si está asignado, el contenedor del lote documentos.
     *
     * @return DocumentBatchInterface|null
     */
    public function getDocumentBatch(): ?DocumentBatchInterface
    {
        return $this->documentBatch;
    }
}
