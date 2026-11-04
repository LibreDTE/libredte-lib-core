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

/**
 * Respuesta de la consulta del estado de la cesión de un documento en el RTC
 * del SII.
 *
 * Con el identificador de la cesión la respuesta trae su detalle (el tenedor, la
 * última anotación y la cobranza). Sin él, solo informa si el documento está
 * cedido o no.
 *
 * Referencia: https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf
 */
class GetAssignmentStatusResponse extends AbstractSiiRtcQueryResponse
{
    /**
     * Indica si el documento está cedido.
     *
     * Es el caso en que el SII responde sin error.
     */
    public function isAssigned(): bool
    {
        return $this->isRequestOk();
    }

    /**
     * RUT (con su dígito verificador) del tenedor actual del documento, si la
     * respuesta trae el detalle de la cesión.
     */
    public function getHolder(): ?string
    {
        return $this->getBodyReader()->getAsString('RUT_TENEDOR');
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusType(): StatusInterface
    {
        return $this->isRequestOk() ? Status::Success : Status::Danger;
    }

    /**
     * Obtiene los datos normalizados de la respuesta.
     *
     * @return array<string, mixed> Datos normalizados de la respuesta del SII.
     */
    public function getData(): array
    {
        if (!isset($this->data)) {
            $body = $this->getBodyReader();
            $headers = ArrayReader::fromArray($this->headers, 'SII response headers');

            $this->data = [
                'assigned' => $this->isAssigned(),
                'status' => $this->getHeaderStatus(),
                'description' => $this->isRequestOk()
                    ? ($headers->getAsString('SII:GLOSA') ?? $this->getHeaderDescription())
                    : $this->getHeaderDescription(),
                'holder' => $body->getAsString('RUT_TENEDOR'),
                'last_annotation' => $body->getAsString('FECHA_ULT_ANOT'),
                'collection_rut' => $body->getAsString('RUT_COBRANZA'),
                'collection_address' => $body->getAsString('DIR_COBRANZA'),
                'sworn_declaration' => $body->getAsString('DEC_JURADA'),
                'token' => $this->requestData['Token'] ?? null,
            ];
        }

        return $this->data;
    }

    private function getBodyReader(): ArrayReader
    {
        return ArrayReader::fromArray($this->body, 'SII response body');
    }
}
