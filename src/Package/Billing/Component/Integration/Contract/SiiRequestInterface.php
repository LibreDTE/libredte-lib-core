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

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Contract\OptionsAwareInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\IntegrationException;

/**
 * Interfaz para una solicitud al SII.
 */
interface SiiRequestInterface extends OptionsAwareInterface
{
    /**
     * Cantidad de intentos: valor por defecto y rango permitido.
     */
    public const RETRIES_DEFAULT = 3;

    public const RETRIES_MIN = 1;

    public const RETRIES_MAX = 10;

    /**
     * Segundos máximos de cada intento: valor por defecto y rango permitido.
     */
    public const TIMEOUT_DEFAULT = 20;

    public const TIMEOUT_MIN = 10;

    public const TIMEOUT_MAX = 60;

    /**
     * Segundos máximos de toda la operación: valor por defecto y rango
     * permitido.
     */
    public const MAX_DURATION_DEFAULT = 60;

    public const MAX_DURATION_MIN = 10;

    public const MAX_DURATION_MAX = 300;

    /**
     * Entrega el ambiente que está configurado para realizar las conexiones al
     * Servicio de Impuestos Internos.
     *
     * @return SiiEnvironment Ambiente que se utilizará en la conexión al SII.
     */
    public function getEnvironment(): SiiEnvironment;

    /**
     * Entrega la cantidad de reintentos que se deben realizar al hacer una
     * consulta a un servicio web del SII.
     *
     * @param int|null $retries
     * @return int Cantidad de intentos, de 1 a 10.
     */
    public function getRetries(?int $retries = null): int;

    /**
     * Entrega el tiempo máximo, en segundos, que puede tardar cada intento de
     * una llamada al SII.
     *
     * @return int Segundos por intento, de 10 a 60.
     */
    public function getTimeout(): int;

    /**
     * Entrega el tiempo máximo total, en segundos, que puede tardar una
     * llamada al SII, considerando todos sus intentos y las pausas entre
     * ellos. Una operación que hace varias llamadas tiene este tiempo por
     * cada una de ellas.
     *
     * @return int Segundos totales, de 10 a 300.
     */
    public function getMaxDuration(): int;

    /**
     * Indica si se está o no verificando el SSL en las conexiones al SII.
     *
     * @return bool `true` si se está verificando, `false` en caso contrario.
     */
    public function getVerifySsl(): bool;

    /**
     * Indica cuál es la caché por defecto que se debe utilizar al realizar la
     * consulta al SII en la gestión de tokens.
     *
     * @return string Tipo de caché por defecto configurada.
     */
    public function getTokenDefaultCache(): string;

    /**
     * Obtiene la clave (llave) del token en la caché según el certificado
     * digital asignado y el ambiente del SII de la solicitud.
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

    /**
     * Obtiene el certificado de la solicitud o falla si no está asignado.
     *
     * @return CertificateInterface
     * @throws IntegrationException Si la solicitud no tiene certificado.
     */
    public function requireCertificate(): CertificateInterface;
}
