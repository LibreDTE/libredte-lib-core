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
     * @return array
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
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
