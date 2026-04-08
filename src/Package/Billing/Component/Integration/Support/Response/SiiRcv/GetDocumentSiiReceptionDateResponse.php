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

    public function __construct(array $response)
    {
        $rawDate = (string) ($response['data'] ?? '');
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

    public function toArray(): array
    {
        return ['fecha_recepcion_sii' => $this->receptionDate];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
