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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\ApiResource;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRtcWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\SendAecJob;

/**
 * Clase del worker del RTC del SII.
 *
 * Gestiona el envío del Archivo Electrónico de Cesión (AEC) al Registro de
 * Transferencias de Créditos (RTC) del SII.
 */
#[Worker(name: 'sii_rtc', component: 'integration', package: 'billing')]
class SiiRtcWorker extends AbstractWorker implements SiiRtcWorkerInterface
{
    public function __construct(
        private SendAecJob $sendAecJob,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[ApiResource(
        parametersExample: [
            'request' => [
                'certificate' => [
                    'data' => '',
                    'password' => '',
                ],
                'options' => [
                    'environment' => SiiEnvironment::PRODUCTION,
                ],
            ],
            'doc' => '',
            'company' => '12345678-5',
            'emailNotif' => 'cedente@empresa.cl',
        ],
    )]
    public function sendAec(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        string $emailNotif,
        ?int $retry = null
    ): SendAecResponse {
        return $this->sendAecJob->send(
            $request,
            $doc,
            $company,
            $emailNotif,
            $retry
        );
    }
}
