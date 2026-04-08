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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte;

use JsonSerializable;

/**
 * Respuesta del SII al enviar un documento XML (EnvioDTE o EnvioBOLETA).
 *
 * Contiene el Track ID asignado por el SII para hacer seguimiento del envío.
 */
class SendXmlDocumentResponse implements JsonSerializable
{
    private readonly int $trackId;

    public function __construct(array $response/*, array $request = []*/)
    {
        $this->trackId = (int) ($response['RECEPCIONDTE']['TRACKID'] ?? 0);
    }

    /**
     * Entrega el Track ID asignado por el SII al recibir el envío.
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
