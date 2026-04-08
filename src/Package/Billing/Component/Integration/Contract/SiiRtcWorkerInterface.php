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
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtc\SendAecException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;

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
     * @param int|null $retry Intentos que se realizarán como máximo al enviar.
     * @return SendAecResponse Respuesta con el Track ID del envío.
     * @throws SendAecException Si hay algún error al enviar el AEC.
     * @link https://palena.sii.cl/cgi_rtc/RTC/RTCDocum.cgi?2
     */
    public function sendAec(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        string $emailNotif,
        ?int $retry = null
    ): SendAecResponse;
}
