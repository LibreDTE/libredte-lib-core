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

use Derafu\Lib\Core\Foundation\Contract\WorkerInterface;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiTokenManagerException;

/**
 * Interfaz del worker que administra el token de la sesión mediante API en SII.
 */
interface SiiTokenManagerWorkerInterface extends WorkerInterface
{
    /**
     * Obtiene un token de autenticación asociado al certificado digital.
     *
     * El token se busca primero en la caché, si existe, se reutilizará, si no
     * existe se solicitará uno nuevo al SII.
     *
     * @param CertificateInterface $certificate Certificado digital con el cual
     * se desea obtener un token de autenticación en el SII.
     * @return string El token asociado al certificado.
     * @throws SiiTokenManagerException Si hubo algún error al obtener el token.
     */
    public function getToken(CertificateInterface $certificate): string;
}
