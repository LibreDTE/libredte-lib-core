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

use Derafu\Lib\Core\Common\Contract\OptionsAwareInterface;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;

/**
 * Interfaz para una solicitud al SII.
 */
interface SiiRequestInterface extends OptionsAwareInterface
{
    /**
     * Entrega el ambiente que está configurado para realizar las conexiones al
     * Servicio de Impuestos Internos.
     *
     * @return SiiAmbiente Ambiente que se utilizará en la conexión al SII.
     */
    public function getAmbiente(): SiiAmbiente;

    /**
     * Entrega la cantidad de reintentos que se deben realizar al hacer una
     * consulta a un servicio web del SII.
     *
     * @param int|null $reintentos
     * @return int
     */
    public function getReintentos(?int $reintentos = null): int;

    /**
     * Indica si se está o no verificando el SSL en las conexiones al SII.
     *
     * @return bool `true` si se está verificando, `false` en caso contrario.
     */
    public function getVerificarSsl(): bool;

    /**
     * Indica cuál es la caché por defecto que se debe utilizar al realizar la
     * consulta al SII en la gestión de tokens.
     *
     * @return string Tipo de caché por defecto configurada.
     */
    public function getTokenDefaultCache(): string;

    /**
     * Obtiene la clave (llave) del token en la caché según el certificado
     * digital asignado.
     *
     * @return string
     */
    public function getTokenKey(): string;

    /**
     * Obtiene el TTL del token solicitado al SII.
     *
     * @return int
     */
    public function getTokenTtl(): int;

    /**
     * Obtiene el certificado digital asociado a la solicitud.
     *
     * @return CertificateInterface|null
     */
    public function getCertificate(): ?CertificateInterface;
}
