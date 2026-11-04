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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Abstract;

use Derafu\Array\ArrayReader;
use Derafu\Enum\Contract\StatusInterface;
use JsonSerializable;

/**
 * Clase base para las respuestas del servicio de consultas del RTC del SII
 * (`wsRPETCConsulta`).
 *
 * Todas las respuestas traen en la cabecera el resultado de la operación
 * (`ESTADO` y, si hubo un error, `GLOSA`) y los datos propios de cada función
 * en el cuerpo.
 *
 * Referencia: https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf
 */
abstract class AbstractSiiRtcQueryResponse extends AbstractSiiWsdlResponse implements JsonSerializable
{
    /**
     * Estados de la cabecera, el resultado de la operación consultada.
     *
     * Solo el `0` es el estado "normal", los demás son errores.
     */
    private const HEADER_STATUSES = [
        '0' => 'Requerimiento recibido OK.',
        '1' => 'Error de datos.',
        '2' => 'Error: no se encontró información.',
        '3' => 'Error de autenticación: token inactivo.',
        '4' => 'Error de autenticación: token no existe.',
        '5' => 'Error de autenticación.',
        '6' => 'Error interno.',
        '7' => 'Error interno.',
        '8' => 'Error interno.',
        '9' => 'Error de autenticación: no se pueden obtener los permisos.',
        '10' => 'Error de permisos: el usuario conectado no tiene permiso en la empresa.',
        // Código que el SII informa y que el manual no documenta.
        '-10' => 'Error: el documento solicitado no existe o no ha sido cedido.',
        '-15' => 'Error de permisos: la empresa relacionada no está vinculada con el documento consultado.',
    ];

    /**
     * Código del estado de la cabecera.
     */
    public function getHeaderStatus(): string
    {
        return ArrayReader::fromArray($this->headers, 'SII response headers')->requireString('SII:ESTADO');
    }

    /**
     * Indica si el SII recibió y procesó bien la consulta.
     *
     * Que sea `true` no significa que el resultado sea el esperado, sino que no
     * hubo un error en la consulta misma.
     */
    public function isRequestOk(): bool
    {
        return $this->getHeaderStatus() === '0';
    }

    /**
     * Descripción del estado de la cabecera.
     *
     * Con un error se usa la descripción conocida del código y, si no la hay,
     * la glosa del SII.
     */
    protected function getHeaderDescription(): string
    {
        $status = $this->getHeaderStatus();

        return self::HEADER_STATUSES[$status]
            ?? ArrayReader::fromArray($this->headers, 'SII response headers')->getAsString('SII:GLOSA')
            ?? sprintf('Error desconocido código #%s.', $status)
        ;
    }

    /**
     * Obtiene el tipo de salida de la respuesta.
     */
    abstract public function getStatusType(): StatusInterface;

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
