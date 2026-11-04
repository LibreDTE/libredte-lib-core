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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Trait;

use Derafu\Array\ArrayReader;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\EstadoEnvioSii;

/**
 * Trait que implementa la revisión del estado de un envío a partir de los
 * datos normalizados de la respuesta (`getData()`).
 *
 * Se apoya en las claves `status`, `description` y `resume` de los datos. Lo
 * que depende del catálogo de códigos de cada API del SII (DTE o boletas) lo
 * deben implementar las clases que usen el trait.
 */
trait ReviewsSentStatusTrait
{
    /**
     * Obtiene los datos normalizados de la respuesta.
     *
     * @return array<string, mixed>
     */
    abstract public function getData(): array;

    /**
     * Entrega el estado normalizado para un código de estado que no es `EPR`.
     *
     * El código `EPR` (envío procesado) se resuelve con el resumen de los
     * documentos; el resto de los códigos dependen del catálogo del SII de
     * cada API.
     *
     * @param string $status Código del estado informado por el SII.
     * @return EstadoEnvioSii|null `null` si el estado no es final o no se
     * reconoce.
     */
    abstract protected function getEstadoEnvioSiiByCode(string $status): ?EstadoEnvioSii;

    /**
     * Entrega el detalle de la revisión para un código de estado que no es
     * `EPR`.
     *
     * @param string $status Código del estado informado por el SII.
     * @return string|null El detalle o `null` si no existe.
     */
    abstract protected function getReviewDetailByCode(string $status): ?string;

    /**
     * Devuelve el estado de revisión del documento.
     *
     * @return string El estado de la revisión del documento.
     */
    public function getReviewStatus(): string
    {
        $data = ArrayReader::fromArray($this->getData(), 'SII response');
        $status = $data->requireString('status');
        $description = $data->getString('description');

        if ($status === 'EPR') {
            if ($this->hasRejectedDocuments()) {
                return 'RCH - DTE Rechazado';
            }

            if ($this->hasRepairsDocuments()) {
                return 'RLV - DTE Aceptado con Reparos Leves';
            }
        }

        return $description
            ? ($status . ' - ' . $description)
            : $status
        ;
    }

    /**
     * Devuelve el estado del envío normalizado como enum.
     *
     * Retorna `null` cuando el SII aún no ha procesado definitivamente el envío
     * (estados intermedios) o el estado no se reconoce.
     *
     * @return EstadoEnvioSii|null
     */
    public function getEstadoEnvioSii(): ?EstadoEnvioSii
    {
        $status = ArrayReader::fromArray($this->getData())->requireString('status');

        if ($status !== 'EPR') {
            return $this->getEstadoEnvioSiiByCode($status);
        }

        if ($this->hasRejectedDocuments()) {
            return EstadoEnvioSii::RECHAZADO;
        }

        if ($this->hasRepairsDocuments()) {
            return EstadoEnvioSii::REPARO;
        }

        return EstadoEnvioSii::ACEPTADO;
    }

    /**
     * Devuelve el detalle de la revisión del documento.
     *
     * @return string|null El detalle de la revisión o `null` si no existe.
     */
    public function getReviewDetail(): ?string
    {
        $status = ArrayReader::fromArray($this->getData())->requireString('status');

        if ($status === 'EPR') {
            return $this->allDocumentsAreAccepted() ? 'DTE aceptado' : null;
        }

        return $this->getReviewDetailByCode($status);
    }

    /**
     * Verifica si todos los documentos fueron aceptados.
     *
     * @return bool True si todos los documentos fueron aceptados.
     */
    private function allDocumentsAreAccepted(): bool
    {
        $resume = ArrayReader::fromArray($this->getData())->requireReader('resume');

        return $resume->requireInt('reported') === $resume->requireInt('accepted');
    }

    /**
     * Verifica si hay documentos rechazados.
     *
     * @return bool True si existen documentos rechazados.
     */
    private function hasRejectedDocuments(): bool
    {
        return ArrayReader::fromArray($this->getData())->requireReader('resume')->requireInt('rejected') > 0;
    }

    /**
     * Verifica si hay documentos con reparos.
     *
     * @return bool True si existen documentos con reparos.
     */
    private function hasRepairsDocuments(): bool
    {
        return ArrayReader::fromArray($this->getData())->requireReader('resume')->requireInt('repairs') > 0;
    }
}
