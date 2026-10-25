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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte;

use Derafu\Enum\Contract\StatusInterface;
use Derafu\Enum\Status;
use JsonSerializable;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiWsdlResponse;

/**
 * Respuesta de la solicitud de correo de estado de un documento subido al SII.
 *
 * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/OIFE2005_wsDTECorreo_MDE.pdf
 */
class RequestXmlDocumentSentStatusByEmailResponse extends AbstractSiiWsdlResponse implements JsonSerializable
{
    /**
     * Estados de salida y error.
     *
     * El resultado de la consulta al SII puede arrojar uno de estos estados.
     *
     * En otros servicios los estados se separan en estados "normales" y
     * "errores", acá solo hay una lista donde solo "0" es el estado "normal" y
     * todo los demás son códigos de estado de errores.
     */
    private const STATUSES = [
        '0' => 'Requerimiento recibido OK.',
        // Errores de Datos.
        '101' => 'Error en dígito verificador del Rut de la empresa.',
        '102' => 'Faltan datos de entrada.',
        '105' => 'Error Track ID no existe.',
        '106' => 'Usuario autenticado no tiene permisos sobre Empresa.',
        '114' => 'Envío solicitado no ha concluido su validación, por lo tanto no existe correo.',
        // Errores por autenticación.
        '104' => 'Token Inactivo o No Existe',
        // Otros Errores.
        '103' => 'Error Interno.',
        '107' => 'Error Interno.',
        '108' => 'Error Interno.',
        '110' => 'Error Interno.',
        '111' => 'Error Interno.',
        '112' => 'Error Interno.',
        '113' => 'Error Interno.',
    ];

    /**
     * Obtiene el tipo de salida para un estado.
     *
     * @return StatusInterface Tipo de salida.
     */
    public function getStatusType(): StatusInterface
    {
        return $this->getStatus() === '0' ? Status::Success : Status::Danger;
    }

    /**
     * Obtiene los datos normalizados de la respuesta.
     *
     * @return array Datos normalizados de la respuesta del SII.
     */
    public function getData(): array
    {
        // Si no existen los datos normalizados de la respuesta se normalizan.
        if (!isset($this->data)) {
            // RUT de la empresa emisora del documento.
            $rutExists = isset($this->requestData['RutEmpresa'])
                && isset($this->requestData['DvEmpresa'])
            ;
            $company = $rutExists
                ? $this->requestData['RutEmpresa']
                    . '-' . $this->requestData['DvEmpresa']
                : null
            ;

            // Normalizar el estado de la consulta del envío.
            [$status, $description] = $this->parseEstado($this->headers);

            // Armar los datos normalizados.
            $this->data = [
                'company' => $company,
                'track_id' => $this->requestData['TrackId'] ?? null,
                'status' => $status,
                'description' => $description,
                'token' => $this->requestData['Token'] ?? null,
            ];
        }

        // Entregar los datos de la respuesta del estado del DTE.
        return $this->data;
    }

    /**
     * Parsea el estado de la respuesta del SII.
     *
     * @param array $headers Encabezados de la respuesta.
     * @return array Arreglo con el estado, error y descripción.
     */
    private function parseEstado(array $headers): array
    {
        // Asignar el código del estado.
        $status = $headers['SII:ESTADO'];
        $description = $headers['SII:GLOSA'] ?? null;

        // Si existe una glosa para el estado se reemplaza la recibida del SII.
        if (isset(self::STATUSES[$status])) {
            $description = self::STATUSES[$status];
        }

        // Entregar valores determinados para el error.
        return [$status, $description];
    }

    /**
     * Entrega los datos de la respuesta como un arreglo.
     *
     * @return array<array-key, mixed> Datos de la respuesta.
     */
    public function toArray(): array
    {
        return $this->getData();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = $this->toArray();
        unset($data['token']);

        return $data;
    }
}
