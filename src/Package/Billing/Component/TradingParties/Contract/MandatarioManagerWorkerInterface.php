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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Contract;

use Derafu\Lib\Core\Foundation\Contract\WorkerInterface;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;

/**
 * Interfaz para el worker que administra los mandatarios.
 */
interface MandatarioManagerWorkerInterface extends WorkerInterface
{
    /**
     * Crea una instancia del mandatario que es dueño del certificado digital.
     *
     * @param CertificateInterface $certificate
     * @return MandatarioInterface
     */
    public function createFromCertificate(
        CertificateInterface $certificate
    ): MandatarioInterface;

    /**
     * Genera y devuelve un certificado ficticio para el mandatario.
     *
     * @return CertificateInterface Certificado ficticio del mandatario.
     */
    public function createFakeCertificate(
        MandatarioInterface $mandatario
    ): CertificateInterface;
}
