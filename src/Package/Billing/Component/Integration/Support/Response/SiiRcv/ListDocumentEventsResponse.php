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
     * @var array<int, array{codigo: string, glosa: string, responsable: string, fecha: string}>
     */
    private array $events;

    public function __construct(array $response)
    {
        $this->events = [];

        $return = $response['return'] ?? $response;
        $items = $return['listaEventosDoc'] ?? [];

        if (!empty($items)) {
            if (isset($items['codEvento'])) {
                $items = [$items];
            }
            foreach ($items as $event) {
                $this->events[] = [
                    'codigo' => (string) ($event['codEvento'] ?? ''),
                    'glosa' => (string) ($event['descEvento'] ?? ''),
                    'responsable' => ($event['rutResponsable'] ?? '') . '-' . ($event['dvResponsable'] ?? ''),
                    'fecha' => (string) ($event['fechaEvento'] ?? ''),
                ];
            }
        }
    }

    /**
     * Entrega los eventos del DTE.
     *
     * @return array<int, array{codigo: string, glosa: string, responsable: string, fecha: string}>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function toArray(): array
    {
        return $this->events;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
