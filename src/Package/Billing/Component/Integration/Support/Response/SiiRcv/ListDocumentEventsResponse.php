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
 * Respuesta del SII con el historial de eventos de un DTE.
 *
 * Cada evento tiene: `codigo`, `glosa`, `responsable` (RUT-DV) y `fecha`.
 */
class ListDocumentEventsResponse implements JsonSerializable
{
    /**
     * Lista de eventos del DTE.
     *
     * @var array<int, DocumentEvent>
     */
    private array $events;

    public function __construct(array $response/*, array $request = []*/)
    {
        $this->events = [];

        $return = $response['return'] ?? $response;
        $items = $return['listaEventosDoc'] ?? [];

        if (!empty($items)) {
            if (isset($items['codEvento'])) {
                $items = [$items];
            }
            foreach ($items as $event) {
                $this->events[] = new DocumentEvent(
                    codigo: (string) ($event['codEvento'] ?? ''),
                    glosa: (string) ($event['descEvento'] ?? ''),
                    responsable: ($event['rutResponsable'] ?? '') . '-' . ($event['dvResponsable'] ?? ''),
                    fecha: self::normalizeFecha((string) ($event['fechaEvento'] ?? '')),
                );
            }
        }
    }

    /**
     * Normaliza la fecha de un evento desde el formato del SII
     * (`DD-MM-YYYY HH:MM:SS`) a `YYYY-MM-DD HH:MM:SS`.
     *
     * Mismo formato de origen y de destino que usa
     * `GetDocumentSiiReceptionDateResponse` para `fecha_recepcion_sii`.
     *
     * @param string $fecha Fecha cruda del evento, tal como la entrega el SII.
     * @return string Fecha normalizada, o la fecha original sin modificar si
     * no calza con el formato esperado (ej. string vacío).
     */
    private static function normalizeFecha(string $fecha): string
    {
        if (
            !preg_match(
                '/^(\d{2})-(\d{2})-(\d{4}) (\d{2}:\d{2}:\d{2})$/',
                $fecha,
                $matches
            )
        ) {
            return $fecha;
        }

        [, $d, $m, $Y, $time] = $matches;

        return sprintf('%s-%s-%s %s', $Y, $m, $d, $time);
    }

    /**
     * Entrega los eventos del DTE.
     *
     * @return array<int, DocumentEvent>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Entrega los eventos del DTE.
     *
     * @return array<int, DocumentEvent>
     */
    public function toArray(): array
    {
        return $this->events;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_map(
            fn (DocumentEvent $event): array => $event->toArray(),
            $this->events
        );
    }
}
