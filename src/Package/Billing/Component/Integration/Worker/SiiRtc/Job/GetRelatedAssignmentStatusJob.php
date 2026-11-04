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
use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtc\GetAssignmentStatusException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\GetAssignmentStatusResponse;
use Throwable;
use UnexpectedValueException;

/**
 * Clase para consultar el estado de la cesión de un documento en el RTC del
 * SII desde una empresa vinculada con ella (tenedor o receptor).
 */
#[Job(name: 'get_related_assignment_status', worker: 'sii_rtc', component: 'integration', package: 'billing')]
class GetRelatedAssignmentStatusJob extends AbstractJob implements JobInterface
{
    private const SERVICE = 'wsRPETCConsulta';

    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Consulta el detalle de la cesión de un documento a una empresa vinculada
     * con ella, ya sea como tenedor o como receptor.
     *
     * Referencia: https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del documento cedido.
     * @param int $document Tipo de documento (33 o 34).
     * @param int $number Folio del documento.
     * @param string $relatedCompany RUT de la empresa vinculada con la cesión,
     * como tenedor o como receptor del documento.
     * @return GetAssignmentStatusResponse
     * @throws UnexpectedValueException Si alguno de los RUT es inválido.
     * @throws GetAssignmentStatusException En caso de error.
     */
    public function get(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $relatedCompany
    ): GetAssignmentStatusResponse {
        Rut::validate($company);
        Rut::validate($relatedCompany);
        [$rut, $dv] = Rut::toArray($company);
        [$relatedRut, $relatedDv] = Rut::toArray($relatedCompany);

        try {
            $token = $this->siiLazyWorker->authenticate($request);
            $requestData = [
                'Token' => $token,
                'RutEmisor' => $rut,
                'DVEmisor' => $dv,
                'TipoDoc' => $document,
                'FolioDoc' => $number,
                'RutEmpresa' => $relatedRut,
                'DVEmpresa' => $relatedDv,
            ];

            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: self::SERVICE,
                function: 'getEstCesionRelac',
                args: $requestData
            );

            return new GetAssignmentStatusResponse(
                $this->xmlService->decode($xmlResponse),
                $requestData
            );
        } catch (Throwable $e) {
            throw new GetAssignmentStatusException([
                'Could not obtain the assignment status of document type {document} number {number} for {company} from the SII. {detail}',
                'document' => $document,
                'number' => $number,
                'company' => $company,
                'detail' => $e,
            ]);
        }
    }
}
