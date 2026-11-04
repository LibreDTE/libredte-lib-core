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

use Derafu\Array\ArrayReader;
use Derafu\Array\Exception\ArrayException;
use JsonSerializable;

/**
 * Respuesta del SII a la consulta de cesibilidad de un DTE.
 */
class CheckDocumentAssignabilityResponse implements JsonSerializable
{
    /**
     * Códigos de respuesta con que el SII informa que el DTE es cedible:
     *
     *   - 23: DTE cedible, sin reclamos.
     *   - 25: DTE cedible, habiendo pasado 8 días se entiende dado acuse de
     *     recibo.
     *
     * Los demás códigos (1, 2, 10, 18, 20, 21, 22 y 24) son de un DTE que no es
     * cedible o de un error en la consulta.
     *
     * Referencia: https://www.sii.cl/factura_electronica/Webservice_Registro_Reclamo_DTE_V1.1.pdf
     */
    private const ASSIGNABLE_CODES = [23, 25];

    /**
     * Código de respuesta del SII.
     */
    private int $code;

    /**
     * Glosa o descripción de la respuesta del SII.
     */
    private string $description;

    /**
     * @param array<array-key, mixed> $response Datos de la respuesta del SII.
     * @throws ArrayException Si la respuesta no trae el código de respuesta.
     */
    public function __construct(array $response/*, array $request = []*/)
    {
        $reader = ArrayReader::fromArray($response, 'SII response');
        $return = $reader->getReader('return') ?? $reader;
        $this->code = $return->requireAsInt('codResp');
        $this->description = $return->getAsString('descResp') ?? '';
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
     * Indica si el DTE es cedible según el SII.
     */
    public function isAssignable(): bool
    {
        return in_array($this->code, self::ASSIGNABLE_CODES, true);
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
