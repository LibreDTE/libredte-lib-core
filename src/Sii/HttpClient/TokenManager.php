<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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

namespace libredte\lib\Core\Sii\HttpClient;

use libredte\lib\Core\Signature\Certificate;
use Psr\SimpleCache\CacheInterface;

/**
 * Clase para gestionar las solicitudes de token para autenticación al SII.
 */
class TokenManager
{
    /**
     * Tiempo en segundos que el token es válido desde que se solicitó.
     *
     * @var int
     */
    private const TOKEN_TTL = 60;

    /**
     * Formato de la clave en caché para guardar el token asociado a un
     * certificado.
     *
     * Se utiliza un placeholder que se reemplazará con el ID del certificado.
     *
     * @var string
     */
    private const TOKEN_KEY = 'libredte_lib_sii_auth_token_%s';

    /**
     * Instancia con la implementación de la caché que se utilizará para el
     * almacenamiento de los tokens.
     *
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Cliente de la API SOAP del SII.
     *
     * @var WsdlConsumer
     */
    private WsdlConsumer $wsdlConsumer;

    /**
     * Constructor del TokenManager para inyectar la implementación de caché y
     * el cliente de los servicios web SOAP del SII para obtener un token.
     */
    public function __construct(
        CacheInterface $cache,
        WsdlConsumer $wsdlConsumer
    )
    {
        $this->cache = $cache;
        $this->wsdlConsumer = $wsdlConsumer;
    }

    /**
     * Obtiene un token de autenticación asociado al certificado digital.
     *
     * El token se busca primero en la caché, si existe, se reutilizará, si no
     * existe se solicitará uno nuevo al SII.
     *
     * @param Certificate $certificate Certificado digital con el cual se desea
     * obtener un token de autenticación en el SII.
     * @return string El token asociado al certificado.
     * @throws SiiClientException Si hubo algún error al obtener el token.
     */
    public function getToken(Certificate $certificate): string
    {
        // Armar clave de la caché para el token asociado al certificado.
        $cacheKey = sprintf(self::TOKEN_KEY, $certificate->getID());

        // Verificar si hay un token en la caché y si no ha expirado.
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Si no hay un token o está expirado, solicitar uno nuevo.
        // Esto falla con excepción que se deja pasara a quien haya llamado a
        // este método getToken().
        $newToken = $this->wsdlConsumer->getToken();

        // Si se logró obtener un token, se guarda en la caché.
        $this->cache->set($cacheKey, $newToken, self::TOKEN_TTL);

        // Entregar el nuevo token obtenido.
        return $newToken;
    }

}
