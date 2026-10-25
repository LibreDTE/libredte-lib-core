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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Cache\Enum\LocalCacheBackend;
use Derafu\Cache\LocalCacheFactory;
use Derafu\Cache\Memoizer;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\AuthenticateException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Clase para gestionar las solicitudes de token para autenticación al SII.
 */
#[Job(name: 'authenticate', worker: 'sii_lazy', component: 'integration', package: 'billing')]
class AuthenticateJob extends AbstractJob implements JobInterface
{
    /**
     * Instancia con la implementación de la caché que se utilizará para el
     * almacenamiento de los tokens.
     *
     * @var CacheItemPoolInterface
     */
    private CacheItemPoolInterface $cache;

    /**
     * Instancia utilizada para calcular y cachear el token una sola vez,
     * incluso ante accesos concurrentes.
     *
     * @var Memoizer
     */
    private Memoizer $memoizer;

    /**
     * Constructor y sus dependencias.
     *
     * @param SignatureServiceInterface $signatureService Servicio para firma
     * electrónica.
     * @param XmlServiceInterface $xmlService Servicio para manejo de documentos
     * XML.
     * @param ConsumeWebserviceJob $consumeWebserviceJob Trabajo que realiza
     * consultas a la API SOAP del SII.
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(
        private SignatureServiceInterface $signatureService,
        private XmlServiceInterface $xmlService,
        private ConsumeWebserviceJob $consumeWebserviceJob,
        ?CacheItemPoolInterface $cache = null
    ) {
        if ($cache !== null) {
            $this->cache = $cache;
        }

        $this->memoizer = new Memoizer();
    }

    /**
     * Obtiene un token de autenticación asociado al certificado digital.
     *
     * El token se busca primero en la caché, si existe, se reutilizará, si no
     * existe se solicitará uno nuevo al SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @return string El token asociado al certificado digital de la solicitud.
     * @throws AuthenticateException Si hubo algún error al obtener el token.
     */
    public function authenticate(SiiRequestInterface $request): string
    {
        $cache = $this->getCache($request->getTokenDefaultCache());

        // Armar clave de la caché para el token asociado al certificado.
        $cacheKey = $request->getTokenKey();

        // Obtener el token desde la caché. Si no hay uno o está expirado, se
        // calcula una sola vez (incluso ante accesos concurrentes) y se
        // guarda en la caché.
        return $this->memoizer->remember(
            $cache,
            $cacheKey,
            function () use ($request) {
                // Esto falla con excepción que se deja pasar a quien haya
                // llamado a este método getToken().
                if ($request->getCertificate() === null) {
                    throw new LogicException(
                        'A digital certificate must be provided to authenticate with the SII.'
                    );
                }

                return $this->getTokenFromSii($request);
            },
            $request->getTokenTtl()
        );
    }

    /**
     * Método para obtener el token de la sesión en el SII.
     *
     * Primero se obtiene una semilla, luego se firma la semilla con el
     * certificado digital y con esta semilla firmada se hace la solicitud del
     * token al SII.
     *
     * Referencia: http://www.sii.cl/factura_electronica/autenticacion.pdf
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @return string Token para autenticación en SII.
     * @throws AuthenticateException En caso de error.
     */
    private function getTokenFromSii(SiiRequestInterface $request): string
    {
        // Obtener semilla.
        $semilla = $this->getSeedFromSii($request);

        // Crear solicitud del token con la semilla, parámetro getTokenRequest
        // de la función getToken() en el servicio web GetTokenFromSeed.
        $xmlRequest = $this->xmlService->encode([
            'getToken' => [
                'item' => [
                    'Semilla' => $semilla,
                ],
            ],
        ]);

        // Firmar el XML de la solicitud del token.
        try {
            $xmlRequestSigned = $this->signatureService->signXml(
                $xmlRequest,
                $request->getCertificate()
            );
        } catch (SignatureException $e) {
            throw new AuthenticateException([
                'Could not sign getToken. {detail}',
                'detail' => $e,
            ]);
        }

        // Realizar la solicitud del token al SII.
        $xmlResponse = $this->consumeWebserviceJob->sendRequest(
            $request,
            'GetTokenFromSeed',
            'getToken',
            ['pszXml' => $xmlRequestSigned]
        );

        // Extraer respuesta de la solicitud del token.
        $response = $this->xmlService->decode($xmlResponse);
        $estado = $response['SII:RESPUESTA']['SII:RESP_HDR']['ESTADO'] ?? null;
        $token = $response['SII:RESPUESTA']['SII:RESP_BODY']['TOKEN'] ?? null;

        // Validar respuesta de la solicitud del token.
        if ($estado !== '00' || $token === null || $token === '') {
            $glosa = $response['SII:RESPUESTA']['SII:RESP_HDR']['GLOSA'] ?? null;
            throw new AuthenticateException([
                'Could not obtain the token to authenticate user {user} with the SII. {detail}',
                'user' => $request->getCertificate()->getId(),
                'detail' => $glosa,
            ]);
        }

        // Entregar el token obtenido desde el SII para la sesión.
        return $token;
    }

    /**
     * Obtiene una semilla desde el SII para luego usarla en la obtención del
     * token para la autenticación.
     *
     * Este es el único servicio web que se puede llamar sin utilizar el
     * certificado digital. Es de libre consumo y se usa para obtener la
     * semilla necesaria para luego, usando el certificado, obtener un token
     * válido para la sesión en el SII.
     *
     * Nota: la semilla tiene una validez de 2 minutos.
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/CrSeed.jws?WSDL
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @return int La semilla si se logró obtener.
     * @throws AuthenticateException En caso de error.
     */
    private function getSeedFromSii(SiiRequestInterface $request): int
    {
        $xmlResponse = $this->consumeWebserviceJob->sendRequest(
            $request,
            'CrSeed',
            'getSeed'
        );
        $response = $this->xmlService->decode($xmlResponse);
        $estado = $response['SII:RESPUESTA']['SII:RESP_HDR']['ESTADO'] ?? null;
        $semilla = $response['SII:RESPUESTA']['SII:RESP_BODY']['SEMILLA'] ?? null;

        if ($estado !== '00' || $semilla === null || $semilla === '') {
            throw new AuthenticateException(
                'Could not obtain the seed.'
            );
        }

        return (int) $semilla;
    }

    /**
     * Entrega una instancia con la implementación de una caché para ser
     * utilizada en la biblioteca.
     *
     * @param string $defaultCache Caché por defecto que se debe crear.
     * @return CacheItemPoolInterface Implementación de caché PSR-6.
     */
    private function getCache(string $defaultCache): CacheItemPoolInterface
    {
        // Si no está asignada la caché se asigna a una por defecto.
        if (!isset($this->cache)) {
            $this->cache = LocalCacheFactory::pool(
                LocalCacheBackend::from($defaultCache),
                'libredte_lib',
                sys_get_temp_dir(),
                3600 // TTL por defecto a una hora (3600 segundos).
            );
        }

        // Entregar la instancia de la caché.
        return $this->cache;
    }
}
