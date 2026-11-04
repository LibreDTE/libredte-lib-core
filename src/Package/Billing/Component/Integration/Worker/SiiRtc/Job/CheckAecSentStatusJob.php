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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtc\CheckAecSentStatusException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\CheckAecSentStatusResponse;
use Throwable;

/**
 * Clase para consultar el estado del envío de un AEC al RTC del SII.
 */
#[Job(name: 'check_aec_sent_status', worker: 'sii_rtc', component: 'integration', package: 'billing')]
class CheckAecSentStatusJob extends AbstractJob implements JobInterface
{
    private const SERVICE = 'wsRPETCConsulta';

    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Consulta el estado del envío de un AEC al RTC del SII.
     *
     * No se necesita el RUT de la empresa: el token con que se consulta
     * identifica al contribuyente y su permiso sobre el envío.
     *
     * Referencia: https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param int $trackId Número de seguimiento asignado al envío del AEC.
     * @return CheckAecSentStatusResponse
     * @throws CheckAecSentStatusException En caso de error.
     */
    public function check(
        SiiRequestInterface $request,
        int $trackId
    ): CheckAecSentStatusResponse {
        try {
            $token = $this->siiLazyWorker->authenticate($request);
            $requestData = [
                'Token' => $token,
                'TrackId' => $trackId,
            ];

            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: self::SERVICE,
                function: 'getEstEnvio',
                args: $requestData
            );

            return new CheckAecSentStatusResponse(
                $this->xmlService->decode($xmlResponse),
                $requestData
            );
        } catch (Throwable $e) {
            throw new CheckAecSentStatusException([
                'Could not obtain the status of the AEC sent to the SII with Track ID {trackId}. {detail}',
                'trackId' => $trackId,
                'detail' => $e,
            ]);
        }
    }
}
