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
 * Respuesta del SII al ingresar una aceptación o reclamo de un DTE.
 */
class SubmitDocumentAcceptanceResponse implements JsonSerializable
{
    /**
     * Código de respuesta del SII.
     */
    private int $code;

    /**
     * Glosa o descripción de la respuesta del SII.
     */
    private string $description;

    public function __construct(array $response/*, array $request = []*/)
    {
        $return = $response['return'] ?? $response;
        $this->code = (int) ($return['codResp'] ?? 0);
        $this->description = (string) ($return['descResp'] ?? '');
    }

    /**
     * Entrega el código de respuesta del SII.
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Entrega la descripción de la respuesta del SII.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Indica si la operación fue aceptada por el SII.
     *
     * Además de código 0, los códigos 7, 8 y 27 son considerados OK según el
     * comportamiento documentado del RCV del SII.
     */
    public function isAccepted(): bool
    {
        return in_array($this->code, [0, 7, 8, 27], true);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'codigo' => $this->code,
            'glosa' => $this->description,
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
