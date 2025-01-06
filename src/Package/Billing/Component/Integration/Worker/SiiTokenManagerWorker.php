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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker;

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Contract\SignatureComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Exception\SignatureException;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiTokenManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiWsdlConsumerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiTokenManagerException;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Clase para gestionar las solicitudes de token para autenticación al SII.
 */
class SiiTokenManagerWorker extends AbstractWorker implements SiiTokenManagerWorkerInterface
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
     * Lazy worker del SII.
     *
     * @var SiiLazyWorkerInterface
     */
    private SiiLazyWorkerInterface $lazyWorker;

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
     * Cliente de la API SOAP del SII.
     *
     * @var SiiWsdlConsumerWorkerInterface
     */
    private SiiWsdlConsumerWorkerInterface $wsdlConsumer;

    /**
     * Instancia con la implementación de la caché que se utilizará para el
     * almacenamiento de los tokens.
     *
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Constructor del TokenManager para inyectar la implementación de caché y
     * el cliente de los servicios web SOAP del SII para obtener un token.
     */
    public function __construct(
        SiiLazyWorkerInterface $lazyWorker,
        SignatureComponentInterface $signatureComponent,
        XmlComponentInterface $xmlComponent,
        SiiWsdlConsumerWorkerInterface $wsdlConsumer,
        ?CacheInterface $cache = null
    ) {
        $this->lazyWorker = $lazyWorker;
        $this->signatureComponent = $signatureComponent;
        $this->xmlComponent = $xmlComponent;
        $this->wsdlConsumer = $wsdlConsumer;
        $this->cache = $cache ?? $this->getCache();
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(CertificateInterface $certificate): string
    {
        // Armar clave de la caché para el token asociado al certificado.
        $cacheKey = sprintf(self::TOKEN_KEY, $certificate->getId());

        // Verificar si hay un token en la caché y si no ha expirado.
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Si no hay un token o está expirado, solicitar uno nuevo.
        // Esto falla con excepción que se deja pasara a quien haya llamado a
        // este método getToken().
        $newToken = $this->getTokenFromSii($certificate);

        // Si se logró obtener un token, se guarda en la caché.
        $this->cache->set($cacheKey, $newToken, self::TOKEN_TTL);

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
     * @param CertificateInterface $certificate
     * @return string Token para autenticación en SII.
     * @throws SiiTokenManagerException En caso de error.
     */
    private function getTokenFromSii(CertificateInterface $certificate): string
    {
        // Obtener semilla.
        $semilla = $this->getSeedFromSii();

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
                $certificate
            );
        } catch (SignatureException $e) {
            throw new SiiTokenManagerException(sprintf(
                'No fue posible firmar getToken. %s',
                $e->getMessage()
            ));
        }

        // Realizar la solicitud del token al SII.
        $xmlResponse = $this->wsdlConsumer->sendRequest(
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
            throw new SiiTokenManagerException(sprintf(
                'No fue posible obtener el token para autenticar en el SII al usuario %s. %s',
                $certificate->getId(),
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
     * @return int La semilla si se logró obtener.
     * @throws SiiTokenManagerException En caso de error.
     */
    private function getSeedFromSii(): int
    {
        $xmlResponse = $this->wsdlConsumer->sendRequest('CrSeed', 'getSeed');
        $response = $this->xmlComponent->getDecoderWorker()->decode($xmlResponse);
        $estado = $response['SII:RESPUESTA']['SII:RESP_HDR']['ESTADO'] ?? null;
        $semilla = $response['SII:RESPUESTA']['SII:RESP_BODY']['SEMILLA'] ?? null;

        if ($estado !== '00' || $semilla === null) {
            throw new SiiTokenManagerException('No fue posible obtener la semilla.');
        }

        return (int) $semilla;
    }

    /**
     * Entrega una instancia con la implementación de una caché para ser
     * utilizada en la biblioteca.
     *
     * @return CacheInterface Implementación de caché PSR-16.
     */
    private function getCache(): CacheInterface
    {
        // Si no está asignada la caché se asigna a una por defecto.
        if (!isset($this->cache)) {
            // Se busca cuál es el tipo de caché por defecto que se debe usar.
            $defaultCache = $this->lazyWorker->getConnectionOptions()->getDefaultCache();

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
