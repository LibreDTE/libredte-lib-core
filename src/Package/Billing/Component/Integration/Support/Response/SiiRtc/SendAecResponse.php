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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc;

use JsonSerializable;

/**
 * Respuesta del RTC del SII al enviar un AEC.
 *
 * Contiene el Track ID asignado por el SII para hacer seguimiento del envío
 * del Archivo Electrónico de Cesión (AEC).
 */
class SendAecResponse implements JsonSerializable
{
    private readonly int $trackId;

    public function __construct(array $response/*, array $request = []*/)
    {
        $inner = reset($response);
        $this->trackId = is_array($inner) ? (int) ($inner['TRACKID'] ?? 0) : 0;
    }

    /**
     * Entrega el Track ID asignado por el RTC del SII al recibir el AEC.
     */
    public function getTrackId(): int
    {
        return $this->trackId;
    }

    public function toArray(): array
    {
        return ['track_id' => $this->trackId];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
