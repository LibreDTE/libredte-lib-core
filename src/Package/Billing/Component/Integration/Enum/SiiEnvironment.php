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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Enum;

/**
 * Enum del ambiente del SII cuando está disponible más de uno.
 *
 * Además permite obtener datos asociados al ambiente como el nombre del
 * servidor o armar una URL.
 */
enum SiiEnvironment: int
{
    /**
     * Ambiente de producción en el SII.
     */
    case PRODUCTION = 0;

    /**
     * Ambiente de certificación/pruebas en el SII.
     */
    case STAGING = 1;

    /**
     * Por defecto se asignan los servidores estándars de documentos tributarios
     * electrónicos (DTE) para producción (palena) y certificación/pruebas
     * (maullin).
     *
     * @var array
     */
    private const SERVERS = [
        'default' => [
            self::PRODUCTION->value => 'palena',
            self::STAGING->value => 'maullin',
        ],
        'www4' => [
            self::PRODUCTION->value => 'www4',
            self::STAGING->value => 'www4c',
        ],
    ];

    /**
     * URL de los WSDL de diferentes servicios del SII. Se define una regla
     * por defecto y otras específicas para ciertos servicios que tienen el WSDL
     * en otra ruta.
     *
     * @var array<string, string|array<int, string>>
     */
    private const WSDL_URLS = [
        'default' => 'https://%s.sii.cl/DTEWS/%s.jws?WSDL',
        'QueryEstDteAv' => 'https://%s.sii.cl/DTEWS/services/%s?WSDL',
        'wsDTECorreo' => 'https://%s.sii.cl/DTEWS/services/%s?WSDL',
        // El servicio del RCV usa servidores y rutas distintos al patrón DTEWS.
        'registroreclamodteservice' => [
            self::PRODUCTION->value => 'https://ws1.sii.cl/WSREGISTRORECLAMODTE/registroreclamodteservice?wsdl',
            self::STAGING->value => 'https://ws2.sii.cl/WSREGISTRORECLAMODTECERT/registroreclamodteservice?wsdl',
        ],
    ];

    /**
     * Mapa de IDKs de CAF con los ambientes del SII.
     *
     * @var array<int, int>
     */
    private const CAF_IDKS = [
        self::PRODUCTION->value => 300,
        self::STAGING->value => 100,
    ];

    /**
     * Obtiene el código del ambiente.
     *
     * El resultado puede ser:
     *
     *   - 'prod' para el ambiente de producción (production).
     *   - 'cert' para el ambiente de certificación (staging).
     *
     * @return string Código del ambiente.
     */
    public function getCode(): string
    {
        return match ($this) {
            self::PRODUCTION => 'prod',
            self::STAGING => 'cert',
        };
    }

    /**
     * Obtiene el nombre del ambiente.
     *
     * @return string Nombre del ambiente.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PRODUCTION => 'Producción',
            self::STAGING => 'Certificación',
        };
    }

    /**
     * Indica si el ambiente es el de producción.
     *
     * @return bool `true` si el ambiente es el de producción, `false` en caso
     * contrario.
     */
    public function isProduction(): bool
    {
        return $this === self::PRODUCTION;
    }

    /**
     * Indica si el ambiente es el de certificación.
     *
     * @return bool `true` si el ambiente es el de certificación, `false` en
     * caso contrario.
     */
    public function isStaging(): bool
    {
        return $this === self::STAGING;
    }

    /**
     * Entrega el servidor del SII según el tipo solicitado.
     *
     * @param string $type Es el tipo de servidor que se está solicitando.
     * @return string Nombre del servidor al que se debe conectar en el SII.
     */
    public function getServer(string $type = 'default'): string
    {
        return self::SERVERS[$type][$this->value]
            ?? self::SERVERS['default'][$this->value]
        ;
    }

    /**
     * Entrega la URL de un WSDL según su servicio.
     *
     * @param string $service El servicio para el que se desea su WSDL.
     * @return string WSDL del servicio si fue encontrado o el WSDL por defecto
     * en el caso que no exista un WSDL específico para el servicio.
     */
    public function getWsdl(string $service): string
    {
        $wsdl = self::WSDL_URLS[$service] ?? self::WSDL_URLS['default'];

        // Algunos servicios (p. ej. el RCV) definen URLs completas por ambiente
        // en vez de una plantilla con el nombre del servidor.
        if (is_array($wsdl)) {
            return $wsdl[$this->value];
        }

        $server = $this->getServer();

        return sprintf($wsdl, $server, $service);
    }

    /**
     * Obtiene la ruta completa a un archivo WSDL.
     *
     * @param string $service Servicio para el que se busca su WSDL.
     * @return string|null Ubicación del WSDL o `null` si no se encontró.
     */
    public function getWsdlPath(string $service): ?string
    {
        $server = $this->getServer();
        $wsdlPath = dirname(__DIR__, 6);
        $filepath = sprintf('%s/%s/%s.wsdl', $wsdlPath, $server, $service);

        return is_readable($filepath) ? realpath($filepath) : null;
    }

    /**
     * Método que entrega la URL de un recurso en el SII según el ambiente que
     * se esté usando.
     *
     * @param string $resource Recurso del sitio de SII que se desea su URL.
     * @return string URL del recurso solicitado.
     */
    public function getUrl(string $resource): string
    {
        $server = $resource === '/anulacionMsvDteInternet'
            ? $this->getServer('www4')
            : $this->getServer()
        ;

        return sprintf('https://%s.sii.cl%s', $server, $resource);
    }

    /**
     * Obtiene el IDK del CAF para el ambiente.
     *
     * @return int IDK del CAF para el ambiente.
     */
    public function getCafIdk(): int
    {
        return self::CAF_IDKS[$this->value];
    }

    /**
     * Intenta obtener el ambiente del SII a partir de un IDK.
     *
     * @param int $idk IDK del CAF.
     * @return self|null Ambiente del SII o `null` si no se encontró.
     */
    public static function tryFromCafIdk(int $idk): ?self
    {
        foreach (self::CAF_IDKS as $environmentValue => $cafIdk) {
            if ($idk === $cafIdk) {
                return self::from($environmentValue);
            }
        }

        return null;
    }

    /**
     * Intenta obtener el ambiente del SII a partir de un código.
     *
     * @param string $code Código del ambiente.
     * @return self|null Ambiente del SII o `null` si no se encontró.
     */
    public static function tryFromCode(string $code): ?self
    {
        return match ($code) {
            'prod' => self::PRODUCTION,
            'cert' => self::STAGING,
            default => null,
        };
    }
}
