<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

use libredte\lib\Core\Helper\Arr;

/**
 * Clase para administrar todos los parámetros o configuraciones de la conexión
 * al sitio web del SII (Servicio de Impuestos Internos) de Chile.
 */
class ConnectionConfig
{
    /**
     * Constante para indicar ambiente de producción.
     *
     * @var int
     */
    public const PRODUCCION = 0;

    /**
     * Constante para indicar ambiente de certificación (pruebas).
     *
     * @var int
     */
    public const CERTIFICACION = 1;

    /**
     * Constante para indicar los reintentos por defecto, y máximos, que se
     * permiten al consumir un servicio web del SII.
     */
    public const REINTENTOS = 10;

    /**
     * Configuración por defecto de la conexión al SII.
     *
     * @var array
     */
    private array $config = [
        // Por defecto nos conectamos a los servidores de producción.
        'ambiente' => self::PRODUCCION,

        // Por defecto se asignan los servidores estándars de documentos
        // tributarios electrónicos (DTE) para producción (palena) y
        // certificación/pruebas (maullin).
        'servidores' => [
            'default' => [
                self::PRODUCCION => 'palena',
                self::CERTIFICACION => 'maullin',
            ],
            'www4' => [
                self::PRODUCCION => 'www4',
                self::CERTIFICACION => 'www4c',
            ]
        ],

        // URL de los WSDL de diferentes servicios del SII. Se define una regla
        // comodín "*" y otras específicas para ciertos servicios que tienen el
        // WSDL en otra ruta.
        'wsdl' => [
            'default' => 'https://%s.sii.cl/DTEWS/%s.jws?WSDL',
            'QueryEstDteAv' => 'https://%s.sii.cl/DTEWS/services/%s?WSDL',
            'wsDTECorreo' => 'https://%s.sii.cl/DTEWS/services/%s?WSDL',
        ],

        // Especifica cuántos reintentos se realizarán de manera automática al
        // hacer una solicitud al SII. El reintento se hará utilizando
        // "exponential backoff", por lo que un número muy grande implicará un
        // tiempo de ejecución alto.
        'reintentos' => self::REINTENTOS,

        // Especifica si se debe o no realizar la validación del certificado
        // SSL del SII. A veces, en pruebas sobre todo, ha resultado útil poder
        // desactivar esta validación. Sin embargo, se desaconseja hacerlo por
        // motivos de seguridad.
        'verificar_ssl' => true,

        // Esta es la caché por defecto que se utilizará al solicitar una caché
        // que implemente PSR-16 para la biblioteca (ej: TokenManager).
        'cache' => [
            'default' => 'memory', // Disponibles: "memory" o "filesystem".
        ],
    ];

    /**
     * Constructor de la configuración de conexión.
     *
     * @param array $config Arreglo con una configuración que reemplazará la
     * por defecto.
     */
    public function __construct(array $config = [])
    {
        $this->config = Arr::mergeRecursiveDistinct($this->config, $config);
    }

    /**
     * Entrega el ambiente que está configurado para realizar las conexiones al
     * Servicio de Impuestos Internos.
     *
     * @return int Ambiente que se utilizará en la conexión.
     */
    public function getAmbiente(): int
    {
        return (int) $this->config['ambiente'];
    }

    /**
     * Permite asignar el ambiente que se debe usar con las conexiones al
     * Servicio de Impuestos Internos.
     *
     * @return self
     */
    public function setAmbiente(int $ambiente = self::PRODUCCION): self
    {
        $this->config['ambiente'] = $ambiente;

        return $this;
    }

    /**
     * Entrega el servidor del SII según el tipo solicitado y ambiente.
     *
     * @param string $tipo Es el tipo de servidor que se está solicitando.
     * @return string Nombre del servidor al que se debe conectar en el SII.
     */
    public function getServidor(string $tipo = 'default'): string
    {
        $ambiente = $this->getAmbiente();

        return $this->config['servidores'][$tipo][$ambiente]
            ?? $this->config['servidores']['default'][$ambiente]
        ;
    }

    /**
     * Método que permite asignar el nombre del servidor del SII que se usará
     * para las consultas al SII.
     *
     * @param string $servidor Servidor que se usará. Ejemplo: maullin para
     * certificación o palena para producción.
     * @param int $ambiente Permite definir si se está cambiando el servidor de
     * certificación o el de producción.
     * @param string $tipo Permite definir el tipo de servidor que se está
     * asignando.
     * @return self
     */
    public function setServidor(
        string $servidor = 'palena',
        int $ambiente = self::PRODUCCION,
        string $tipo = 'default'
    ): self
    {
        $this->config['servidores'][$tipo][$ambiente] = $servidor;

        return $this;
    }

    /**
     * Entrega la URL de un WSDL según su servicio.
     *
     * @param string $servicio El servicio para el que se desea su WSDL.
     * @return string WSDL del servicio si fue encontrado o el WSDL por
     * defecto en el caso que no exista un WSDL específico para el servicio.
     */
    public function getWsdl(string $servicio): string
    {
        $wsdl = $this->config['wsdl'][$servicio]
            ?? $this->config['wsdl']['default']
        ;
        $servidor = $this->getServidor();

        return sprintf($wsdl, $servidor, $servicio);
    }

    /**
     * Entrega la cantidad de reintentos que se deben realizar al hacer una
     * consulta a un servicio web del SII.
     *
     * @return int
     */
    public function getReintentos(): int
    {
        return $this->config['reintentos'];
    }

    /**
     * Método que permite indicar si se debe o no verificar el certificado SSL
     * del SII.
     *
     * @param bool $verificar `true` si se quiere verificar certificado,
     * `false` en caso que no (por defecto se verifica).
     * @return self
     */
    public function setVerificarSsl(bool $verificar = true): self
    {
        $this->config['verificar_ssl'] = $verificar;

        return $this;
    }

    /**
     * Método que indica si se está o no verificando el SSL en las conexiones
     * al SII.
     *
     * @return bool `true` si se está verificando, `false` en caso contrario.
     */
    public function getVerificarSsl(): bool
    {
        return $this->config['verificar_ssl'];
    }

    /**
     * Método que entrega la URL de un recurso en el SII según el ambiente que
     * se esté usando.
     *
     * @param string $recurso Recurso del sitio de SII que se desea su URL.
     * @return string URL del recurso solicitado.
     */
    public function getUrl(string $recurso): string
    {
        $servidor = $recurso === '/anulacionMsvDteInternet'
            ? $this->getServidor('www4')
            : $this->getServidor()
        ;

        return sprintf('https://%s.sii.cl%s', $servidor, $recurso);
    }

    /**
     * Indica cuál es la caché por defecto que se debe utilizar.
     *
     * @return string Tipo de caché por defecto configurada.
     */
    public function getDefaultCache(): string
    {
        return $this->config['cache']['default'] === 'filesystem'
            ? 'filesystem'
            : 'memory'
        ;
    }
}
