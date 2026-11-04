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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc;

use Derafu\Array\ArrayReader;
use Derafu\Enum\Contract\StatusInterface;
use Derafu\Enum\Status;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiRtcQueryResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\EstadoEnvioAec;

/**
 * Respuesta de la consulta del estado del envío de un AEC al RTC del SII.
 *
 * Referencia: https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf
 */
class CheckAecSentStatusResponse extends AbstractSiiRtcQueryResponse
{
    /**
     * Estado del envío como enum.
     *
     * @return EstadoEnvioAec|null `null` si la consulta falló o si el SII
     * informa un estado que no está en el catálogo documentado.
     */
    public function getEstadoEnvioAec(): ?EstadoEnvioAec
    {
        if (!$this->isRequestOk()) {
            return null;
        }

        return EstadoEnvioAec::tryFrom($this->getBodyStatus());
    }

    /**
     * Número de seguimiento (Track ID) consultado.
     */
    public function getTrackId(): int
    {
        return ArrayReader::fromArray($this->getData(), 'SII response')->requireAsInt('track_id');
    }

    /**
     * Indica si el envío ya terminó de procesarse (fue aceptado, anulado o
     * rechazado). Con un estado intermedio se debe consultar más adelante.
     */
    public function isFinal(): bool
    {
        return $this->getEstadoEnvioAec()?->isFinal() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusType(): StatusInterface
    {
        if (!$this->isRequestOk()) {
            return Status::Danger;
        }

        return $this->getEstadoEnvioAec()?->getStatusType() ?? Status::Info;
    }

    /**
     * Obtiene los datos normalizados de la respuesta.
     *
     * @return array<string, mixed> Datos normalizados de la respuesta del SII.
     */
    public function getData(): array
    {
        if (!isset($this->data)) {
            $body = ArrayReader::fromArray($this->body, 'SII response body');
            $request = ArrayReader::fromArray($this->requestData, 'SII request');

            if ($this->isRequestOk()) {
                $status = $this->getBodyStatus();
                $estado = EstadoEnvioAec::tryFrom($status);
                $description = $estado?->getDescription()
                    ?? $body->getAsString('DESC_ESTADO')
                    ?? sprintf('Estado desconocido: %s', $status)
                ;
                $error = $estado?->isError() ?? false;
            } else {
                $status = $this->getHeaderStatus();
                $description = $this->getHeaderDescription();
                $error = true;
            }

            $this->data = [
                'track_id' => $body->getAsInt('TRACKID') ?? $request->getAsInt('TrackId'),
                'status' => $status,
                'description' => $description,
                'error' => $error,
                'final' => $this->isFinal(),
                'token' => $this->requestData['Token'] ?? null,
            ];
        }

        return $this->data;
    }

    /**
     * Código del estado del envío que entrega el cuerpo de la respuesta.
     */
    private function getBodyStatus(): string
    {
        return ArrayReader::fromArray($this->body, 'SII response body')->requireString('ESTADO_ENVIO');
    }
}
