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
use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\AuthenticateException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtc\CheckAecSentStatusException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtc\GetAssignmentStatusException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtc\SendAecException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\CheckAecSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\GetAssignmentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;
use UnexpectedValueException;

/**
 * Interfaz del worker del RTC del SII.
 */
interface SiiRtcWorkerInterface extends WorkerInterface
{
    /**
     * Envía un AEC al Registro de Transferencias de Créditos (RTC) del SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param XmlDocumentInterface $doc Documento XML del AEC que se enviará.
     * @param string $company RUT del cedente (empresa que cede el documento).
     * @param string $emailNotif Correo electrónico de contacto del cedente para
     *   notificaciones del SII sobre el resultado del procesamiento del AEC.
     * @param int|null $retries Intentos que se realizarán como máximo al enviar.
     * @return SendAecResponse Respuesta con el Track ID del envío.
     * @throws UnexpectedValueException Si el RUT del cedente es inválido.
     * @throws LogicException Si la solicitud no incluye un certificado
     * digital para autenticar en el SII.
     * @throws AuthenticateException Si falla la autenticación en el SII.
     * @throws SendAecException Si hay algún error al enviar el AEC.
     * @link https://palena.sii.cl/cgi_rtc/RTC/RTCDocum.cgi?2 Formulario web del SII para el envío manual de una cesión (AEC) al RTC, que este envío automatizado emula.
     */
    public function sendAec(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        string $emailNotif,
        ?int $retries = null
    ): SendAecResponse;

    /**
     * Obtiene el estado actualizado del envío de un AEC al RTC del SII.
     *
     * Este estado podría no ser el final; en ese caso, se debe reintentar la
     * consulta más adelante.
     *
     * Para consultar solo se necesita el Track ID: el token de la solicitud
     * identifica al contribuyente.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param int $trackId Número de seguimiento asignado al envío del AEC.
     * @return CheckAecSentStatusResponse El estado del envío.
     * @throws CheckAecSentStatusException En caso de error.
     * @link https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf Manual del Desarrollador Externo del SII: Consulta Estado Envío de AEC al Registro.
     */
    public function checkAecSentStatus(
        SiiRequestInterface $request,
        int $trackId
    ): CheckAecSentStatusResponse;

    /**
     * Obtiene el estado de la cesión de un documento en el RTC del SII.
     *
     * Sin el identificador de la cesión, la respuesta solo informa si el
     * documento está cedido. Con él, entrega además el detalle: el tenedor, la
     * fecha de la última anotación y la cobranza.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del documento cedido.
     * @param int $document Tipo de documento tributario electrónico (33 o 34).
     * @param int $number Folio del documento.
     * @param string|null $assignmentId Clave de acceso a la información de la
     * cesión.
     * @return GetAssignmentStatusResponse El estado de la cesión.
     * @throws UnexpectedValueException Si el RUT de la empresa es inválido.
     * @throws GetAssignmentStatusException En caso de error.
     * @link https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf Manual del Desarrollador Externo del SII: Consulta de Cesiones.
     */
    public function getAssignmentStatus(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        ?string $assignmentId = null
    ): GetAssignmentStatusResponse;

    /**
     * Obtiene el detalle de la cesión de un documento para una empresa
     * vinculada con ella, ya sea como tenedor o como receptor.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del documento cedido.
     * @param int $document Tipo de documento tributario electrónico (33 o 34).
     * @param int $number Folio del documento.
     * @param string $relatedCompany RUT de la empresa vinculada con la cesión.
     * @return GetAssignmentStatusResponse El detalle de la cesión.
     * @throws UnexpectedValueException Si alguno de los RUT es inválido.
     * @throws GetAssignmentStatusException En caso de error.
     * @link https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf Manual del Desarrollador Externo del SII: Consulta de Cesiones.
     */
    public function getRelatedAssignmentStatus(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $relatedCompany
    ): GetAssignmentStatusResponse;
}
