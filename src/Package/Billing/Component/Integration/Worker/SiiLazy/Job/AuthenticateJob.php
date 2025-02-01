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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job;

use Derafu\Lib\Core\Foundation\Abstract\AbstractJob;
use Derafu\Lib\Core\Foundation\Contract\JobInterface;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Contract\SignatureComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Exception\SignatureException;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiAuthenticateException;
use LogicException;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Clase para gestionar las solicitudes de token para autenticación al SII.
 */
class AuthenticateJob extends AbstractJob implements JobInterface
{
    /**
     * Componente para firma electrónica.
     *
     * @var SignatureComponentInterface
     */
    private SignatureComponentInterface $signatureComponent;

    /**
     * Componente para manejo de documentos XML.
     *
     * @var XmlComponentInterface
     */
    private XmlComponentInterface $xmlComponent;

    /**
     * Trabajo que realiza consultas a la API SOAP del SII.
     *
     * @var ConsumeWebserviceJob
     */
    private ConsumeWebserviceJob $consumeWebserviceJob;

    /**
     * Instancia con la implementación de la caché que se utilizará para el
     * almacenamiento de los tokens.
     *
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Constructor y sus dependencias.
     */
    public function __construct(
        SignatureComponentInterface $signatureComponent,
        XmlComponentInterface $xmlComponent,
        ConsumeWebserviceJob $consumeWebserviceJob,
        ?CacheInterface $cache = null
    ) {
        $this->signatureComponent = $signatureComponent;
        $this->xmlComponent = $xmlComponent;
        $this->consumeWebserviceJob = $consumeWebserviceJob;
        if ($cache !== null) {
            $this->cache = $cache;
        }
    }

    /**
     * Obtiene un token de autenticación asociado al certificado digital.
     *
     * El token se busca primero en la caché, si existe, se reutilizará, si no
     * existe se solicitará uno nuevo al SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @return string El token asociado al certificado digital de la solicitud.
     * @throws SiiAuthenticateException Si hubo algún error al obtener el token.
     */
    public function authenticate(SiiRequestInterface $request): string
    {
        $cache = $this->getCache($request->getTokenDefaultCache());

        // Armar clave de la caché para el token asociado al certificado.
        $cacheKey = $request->getTokenKey();

        // Verificar si hay un token en la caché y si no ha expirado.
        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        // Si no hay un token o está expirado, solicitar uno nuevo.
        // Esto falla con excepción que se deja pasara a quien haya llamado a
        // este método getToken().
        if ($request->getCertificate() === null) {
            throw new LogicException(
                'Para autenticar en el SII se debe proveer un certificado digital.'
            );
        }
        $newToken = $this->getTokenFromSii($request);

        // Si se logró obtener un token, se guarda en la caché.
        $cache->set($cacheKey, $newToken, $request->getTokenTtl());

        // Entregar el nuevo token obtenido.
        return $newToken;
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
     * @throws SiiAuthenticateException En caso de error.
     */
    private function getTokenFromSii(SiiRequestInterface $request): string
    {
        // Obtener semilla.
        $semilla = $this->getSeedFromSii($request);

        // Crear solicitud del token con la semilla, parámetro getTokenRequest
        // de la función getToken() en el servicio web GetTokenFromSeed.
        $xmlRequest = $this->xmlComponent->getEncoderWorker()->encode([
            'getToken' => [
                'item' => [
                    'Semilla' => $semilla,
                ],
            ],
        ]);

        // Firmar el XML de la solicitud del token.
        try {
            $xmlRequestSigned = $this->signatureComponent->getGeneratorWorker()->signXml(
                $xmlRequest,
                $request->getCertificate()
            );
        } catch (SignatureException $e) {
            throw new SiiAuthenticateException(sprintf(
                'No fue posible firmar getToken. %s',
                $e->getMessage()
            ));
        }

        // Realizar la solicitud del token al SII.
        $xmlResponse = $this->consumeWebserviceJob->sendRequest(
            $request,
            'GetTokenFromSeed',
            'getToken',
            ['pszXml' => $xmlRequestSigned]
        );

        // Extraer respuesta de la solicitud del token.
        $response = $this->xmlComponent->getDecoderWorker()->decode($xmlResponse);
        $estado = $response['SII:RESPUESTA']['SII:RESP_HDR']['ESTADO'] ?? null;
        $token = $response['SII:RESPUESTA']['SII:RESP_BODY']['TOKEN'] ?? null;

        // Validar respuesta de la solicitud del token.
        if ($estado !== '00' || $token === null) {
            $glosa = $response['SII:RESPUESTA']['SII:RESP_HDR']['GLOSA'] ?? null;
            throw new SiiAuthenticateException(sprintf(
                'No fue posible obtener el token para autenticar en el SII al usuario %s. %s',
                $request->getCertificate()->getId(),
                $glosa
            ));
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
     * @throws SiiAuthenticateException En caso de error.
     */
    private function getSeedFromSii(SiiRequestInterface $request): int
    {
        $xmlResponse = $this->consumeWebserviceJob->sendRequest(
            $request,
            'CrSeed',
            'getSeed'
        );
        $response = $this->xmlComponent->getDecoderWorker()->decode($xmlResponse);
        $estado = $response['SII:RESPUESTA']['SII:RESP_HDR']['ESTADO'] ?? null;
        $semilla = $response['SII:RESPUESTA']['SII:RESP_BODY']['SEMILLA'] ?? null;

        if ($estado !== '00' || $semilla === null) {
            throw new SiiAuthenticateException(
                'No fue posible obtener la semilla.'
            );
        }

        return (int) $semilla;
    }

    /**
     * Entrega una instancia con la implementación de una caché para ser
     * utilizada en la biblioteca.
     *
     * @param string $defaultCache Caché por defecto que se debe crear.
     * @return CacheInterface Implementación de caché PSR-16.
     */
    private function getCache(string $defaultCache): CacheInterface
    {
        // Si no está asignada la caché se asigna a una por defecto.
        if (!isset($this->cache)) {
            // Asignar una implementación de caché en el sistema de archivos.
            if ($defaultCache === 'filesystem') {
                $adapter = new FilesystemAdapter(
                    'libredte_lib',
                    3600, // TTL por defecto a una hora (3600 segundos).
                    sys_get_temp_dir()
                );
            }

            // Asignar una implementación de caché en memoria.
            else {
                $adapter = new ArrayAdapter();
            }

            // Asignar el adaptador de la caché que se utilizará convirtiéndolo
            // a una instancia válida de PSR-16.
            $this->cache = new Psr16Cache($adapter);
        }

        // Entregar la instancia de la caché.
        return $this->cache;
    }
}
