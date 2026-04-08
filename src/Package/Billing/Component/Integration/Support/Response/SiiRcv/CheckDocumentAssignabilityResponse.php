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
 * Respuesta del SII a la consulta de cesibilidad de un DTE.
 */
class CheckDocumentAssignabilityResponse implements JsonSerializable
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
     * Indica si el DTE es cedible según el SII.
     */
    public function isAssignable(): bool
    {
        return $this->code === 0;
    }

    public function toArray(): array
    {
        return [
            'codigo' => $this->code,
            'glosa' => $this->description,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
