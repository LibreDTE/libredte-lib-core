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

namespace libredte\lib\Core\Sii\HttpClient;

use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentUploader;
use libredte\lib\Core\Sii\HttpClient\WebService\DocumentValidator;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Clase que funciona como "punto de entrada" para la comunicación entre la
 * biblioteca de LibreDTE y el Servicio de Impuestos Internos (SII) de Chile.
 */
class SiiClient
{
    /**
     * Certificado digital.
     *
     * @var Certificate
     */
    private Certificate $certificate;

    /**
     * Configuración de la conexión al SII.
     *
     * @var ConnectionConfig
     */
    private ConnectionConfig $config;

    /**
     * Instancia con la implementación de la caché para usar en el cliente.
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
     * Administrador de tokens de autenticación del SII.
     *
     * @var TokenManager
     */
    private TokenManager $tokenManager;

    /**
     * Instancia que envía un documento al SII y valida su estado.
     *
     * @var DocumentUploader
     */
    private DocumentUploader $documentUploader;

    /**
     * Instancia para la validación de documentos tributarios en el SII.
     *
     * @var DocumentValidator
     */
    private DocumentValidator $documentValidator;

    /**
     * Constructor del cliente del SII.
     *
     * @param Certificate $certificate
     * @param array $config
     * @param CacheInterface $cache
     */
    public function __construct(
        Certificate $certificate,
        array $config = [],
        ?CacheInterface $cache = null,
    ) {
        $this->certificate = $certificate;
        $this->config = new ConnectionConfig($config);
        $this->cache = $cache ?? $this->getCache();
        $this->wsdlConsumer = new WsdlConsumer(
            $this->certificate,
            $this->config,
        );
        $this->tokenManager = new TokenManager(
            $this->cache,
            $this->wsdlConsumer,
        );
        $this->documentUploader = new DocumentUploader(
            $this->certificate,
            $this->config,
            $this->tokenManager,
        );
        $this->documentValidator = new DocumentValidator(
            $this->certificate,
            $this->wsdlConsumer,
            $this->tokenManager,
        );
    }

    /**
     * Entrega la instancia de ConnectionConfig asociada al cliente del SII.
     *
     * @return ConnectionConfig
     */
    public function getConfig(): ConnectionConfig
    {
        return $this->config;
    }

    /**
     * Entrega la instancia de WsdlConsumer asociada al cliente del SII.
     *
     * @return WsdlConsumer
     */
    public function getWsdlConsumer(): WsdlConsumer
    {
        return $this->wsdlConsumer;
    }

    /**
     * Entrega la instancia de DocumentUploader asociada al cliente del SII.
     *
     * @return DocumentUploader
     */
    public function getDocumentUploader(): DocumentUploader
    {
        return $this->documentUploader;
    }

    /**
     * Entrega la instancia de DocumentValidator asociada al cliente del SII.
     *
     * @return DocumentValidator
     */
    public function getDocumentValidator(): DocumentValidator
    {
        return $this->documentValidator;
    }

    /**
     * Entrega una instancia con la implementación de una caché para ser
     * utilizada en la biblioteca.
     *
     * NOTE: Este método a propósito tiene las clases con su FQCN y no utiliza
     * los use. Esto es para que PHP no cargue las clases automáticamente a
     * menos que realmente se vayan a utilizar. Pues en una situación normal,
     * la caché debería ser inyectada y no usarse las opciones por defecto.
     *
     * @return CacheInterface Implementación de caché PSR-16.
     */
    private function getCache(): CacheInterface
    {
        // Si no está asignada la caché se asigna a una por defecto.
        if (!isset($this->cache)) {
            // Se busca cuál es el tipo de caché por defecto que se debe usar.
            $defaultCache = $this->config->getDefaultCache();

            // Asignar una implementación de caché en el sistema de archivos.
            if ($defaultCache === 'filesystem') {
                $adapter = new FilesystemAdapter(
                    'libredte_lib',
                    3600, // TTL por defecto a una hora (3600 segundos).
                    PathManager::getCachePath()
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
