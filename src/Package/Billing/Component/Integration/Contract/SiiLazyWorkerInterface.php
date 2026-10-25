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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\AuthenticateException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\ConsumeWebserviceException;

/**
 * Interfaz del lazy worker del SII.
 */
interface SiiLazyWorkerInterface extends WorkerInterface
{
    /**
     * Realiza una solicitud a un servicio web del SII mediante el uso de WSDL.
     *
     * Este método prepara y normaliza los datos recibidos y llama al método
     * que realmente hace la consulta al SII: callServiceFunction().
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $service Nombre del servicio del SII que se consumirá.
     * @param string $function Nombre de la función que se ejecutará en el
     * servicio web del SII.
     * @param array|int $args Argumentos que se pasarán al servicio web.
     * @param int|null $retries Intentos que se realizarán como máximo para
     * obtener respuesta.
     * @param string|null $token Token de autenticación. Si se provee, se
     * establece como cookie TOKEN en el cliente SOAP (requerido por el RCV).
     * @return XmlDocumentInterface Documento XML con la respuesta del servicio web.
     * @throws ConsumeWebserviceException En caso de error.
     */
    public function consumeWebservice(
        SiiRequestInterface $request,
        string $service,
        string $function,
        array|int $args = [],
        ?int $retries = null,
        ?string $token = null
    ): XmlDocumentInterface;

    /**
     * Obtiene un token de autenticación asociado al certificado digital.
     *
     * El token se busca primero en la caché, si existe, se reutilizará, si no
     * existe se solicitará uno nuevo al SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @return string El token asociado al certificado digital de la solicitud.
     * @throws AuthenticateException Si hubo algún error al obtener el token.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/autenticacion.pdf Manual del Desarrollador del SII: Autenticación Automática.
     */
    public function authenticate(SiiRequestInterface $request): string;
}
