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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\CheckDocumentAssignabilityException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\GetDocumentSiiReceptionDateException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\ListDocumentEventsException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\SubmitDocumentAcceptanceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\CheckDocumentAssignabilityResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\GetDocumentSiiReceptionDateResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\ListDocumentEventsResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\SubmitDocumentAcceptanceResponse;

/**
 * Interfaz del worker del Registro de Compra y Venta (RCV) del SII.
 */
interface SiiRcvWorkerInterface extends WorkerInterface
{
    /**
     * Ingresa una aceptación o reclamo de un DTE en el RCV del SII.
     *
     * Acciones válidas: ERM, ACD, RCD, RFP, RFT.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $action Acción a registrar.
     * @return SubmitDocumentAcceptanceResponse
     * @throws SubmitDocumentAcceptanceException En caso de error.
     */
    public function submitDocumentAcceptance(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $action
    ): SubmitDocumentAcceptanceResponse;

    /**
     * Lista el historial de eventos de un DTE en el RCV del SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @return ListDocumentEventsResponse
     * @throws ListDocumentEventsException En caso de error.
     */
    public function listDocumentEvents(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): ListDocumentEventsResponse;

    /**
     * Consulta si un DTE puede ser cedido (factoring/AEC).
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @return CheckDocumentAssignabilityResponse
     * @throws CheckDocumentAssignabilityException En caso de error.
     */
    public function checkDocumentAssignability(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): CheckDocumentAssignabilityResponse;

    /**
     * Consulta la fecha en que el SII recibió un DTE.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @return GetDocumentSiiReceptionDateResponse
     * @throws GetDocumentSiiReceptionDateException En caso de error.
     */
    public function getDocumentSiiReceptionDate(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): GetDocumentSiiReceptionDateResponse;
}
