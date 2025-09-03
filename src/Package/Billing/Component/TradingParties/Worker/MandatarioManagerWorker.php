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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\ApiResource;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Certificate\Contract\CertificateFakerInterface;
use Derafu\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioManagerWorkerInterface;

/**
 * Clase para el worker que administra los mandatarios.
 */
#[Worker(name: 'mandatario_manager', component: 'trading_parties', package: 'billing')]
class MandatarioManagerWorker extends AbstractWorker implements MandatarioManagerWorkerInterface
{
    public function __construct(
        private MandatarioFactoryInterface $mandatarioFactory,
        private CertificateFakerInterface $certificateFaker
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createFromCertificate(
        CertificateInterface $certificate
    ): MandatarioInterface {
        return $this->mandatarioFactory->create([
            'run' => $certificate->getId(),
            'nombre' => $certificate->getName(),
            'email' => $certificate->getEmail(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    #[ApiResource(
        parametersExample: [
            'mandatario' => [
                'run' => '12345678-9',
                'nombre' => 'Juan Pérez',
                'email' => 'juan.perez@example.com',
            ],
        ],
    )]
    public function createFakeCertificate(
        MandatarioInterface $mandatario
    ): CertificateInterface {
        return $this->certificateFaker->createFake(
            id: $mandatario->getRun(),
            name: $mandatario->getNombre(),
            email: $mandatario->getEmail()
        );
    }
}
