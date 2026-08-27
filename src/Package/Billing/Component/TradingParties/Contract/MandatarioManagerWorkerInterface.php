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

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Exception\CertificateException;
use LogicException;

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
