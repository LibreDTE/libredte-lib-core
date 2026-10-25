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
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\GetDocumentSiiReceptionDateException;

/**
 * Respuesta del SII con la fecha de recepción de un DTE en el SII.
 *
 * El SII retorna la fecha en formato `DD-MM-YYYY HH:MM:SS` dentro del XML de
 * respuesta. Esta clase la normaliza a `YYYY-MM-DD HH:MM:SS`.
 */
class GetDocumentSiiReceptionDateResponse implements JsonSerializable
{
    /**
     * Fecha de recepción en formato `YYYY-MM-DD HH:MM:SS`.
     */
    private string $receptionDate;

    public function __construct(array $response/*, array $request = []*/)
    {
        $rawDate = (string) ($response['data'] ?? '');

        // El SII responde `<data/>` (vacío) cuando el documento consultado no
        // tiene fecha de recepción registrada (folio inexistente o aún no
        // recibido) — confirmado contra el SII de certificación.
        if ($rawDate === '') {
            throw new GetDocumentSiiReceptionDateException(
                'The SII has no reception date on record for the queried document.'
            );
        }

        [$day, $time] = explode(' ', $rawDate, 2);
        [$d, $m, $Y] = explode('-', $day);
        $this->receptionDate = sprintf('%s-%s-%s %s', $Y, $m, $d, $time);
    }

    /**
     * Entrega la fecha de recepción en formato `YYYY-MM-DD HH:MM:SS`.
     */
    public function getReceptionDate(): string
    {
        return $this->receptionDate;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return ['fecha_recepcion_sii' => $this->receptionDate];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
