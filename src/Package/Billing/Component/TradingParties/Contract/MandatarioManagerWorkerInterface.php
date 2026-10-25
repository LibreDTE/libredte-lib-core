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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Exception\CertificateException;
use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;

/**
 * Interfaz para el worker que administra los mandatarios.
 */
interface MandatarioManagerWorkerInterface extends WorkerInterface
{
    /**
     * Crea una instancia del mandatario que es dueño del certificado digital.
     *
     * @param CertificateInterface $certificate Certificado digital desde el
     * que se extraen los datos del mandatario (RUT y nombre).
     * @return MandatarioInterface El mandatario creado a partir de los
     * datos del certificado.
     * @throws LogicException Si el certificado no incluye RUT, nombre o
     * correo electrónico del titular.
     */
    public function createFromCertificate(
        CertificateInterface $certificate
    ): MandatarioInterface;

    /**
     * Genera y devuelve un certificado ficticio para el mandatario.
     *
     * @param MandatarioInterface $mandatario Mandatario para el que se
     * generará el certificado falso.
     * @return CertificateInterface Certificado ficticio del mandatario.
     * @throws CertificateException Si falla la generación del certificado
     * autofirmado o su carga posterior.
     */
    public function createFakeCertificate(
        MandatarioInterface $mandatario
    ): CertificateInterface;
}
