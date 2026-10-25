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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv;

use JsonSerializable;

/**
 * Un evento del historial de un DTE en el RCV del SII.
 *
 * Parte de `ListDocumentEventsResponse`.
 */
class DocumentEvent implements JsonSerializable
{
    public function __construct(
        private readonly string $codigo,
        private readonly string $glosa,
        private readonly string $responsable,
        private readonly string $fecha,
    ) {
    }

    /**
     * Entrega el código del evento informado por el SII.
     *
     * @return string
     */
    public function getCodigo(): string
    {
        return $this->codigo;
    }

    /**
     * Entrega la glosa (descripción) del evento.
     *
     * @return string
     */
    public function getGlosa(): string
    {
        return $this->glosa;
    }

    /**
     * Entrega el RUT (formato RUT-DV) del responsable del evento.
     *
     * @return string
     */
    public function getResponsable(): string
    {
        return $this->responsable;
    }

    /**
     * Entrega la fecha en que ocurrió el evento.
     *
     * @return string
     */
    public function getFecha(): string
    {
        return $this->fecha;
    }

    /**
     * Entrega el evento como arreglo.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'glosa' => $this->glosa,
            'responsable' => $this->responsable,
            'fecha' => $this->fecha,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
