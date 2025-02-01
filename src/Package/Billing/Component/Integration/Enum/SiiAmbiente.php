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
enum SiiAmbiente: int
{
    /**
     * Ambiente de producción en el SII.
     */
    case PRODUCCION = 0;

    /**
     * Ambiente de certificación/pruebas en el SII.
     */
    case CERTIFICACION = 1;

    /**
     * Por defecto se asignan los servidores estándars de documentos tributarios
     * electrónicos (DTE) para producción (palena) y certificación/pruebas
     * (maullin).
     *
     * @var array
     */
    private const SERVIDORES = [
        'default' => [
            self::PRODUCCION->value => 'palena',
            self::CERTIFICACION->value => 'maullin',
        ],
        'www4' => [
            self::PRODUCCION->value => 'www4',
            self::CERTIFICACION->value => 'www4c',
        ],
    ];

    /**
     * URL de los WSDL de diferentes servicios del SII. Se define una regla
     * por defecto y otras específicas para ciertos servicios que tienen el WSDL
     * en otra ruta.
     *
     * @var array
     */
    private const WSDL_URLS = [
        'default' => 'https://%s.sii.cl/DTEWS/%s.jws?WSDL',
        'QueryEstDteAv' => 'https://%s.sii.cl/DTEWS/services/%s?WSDL',
        'wsDTECorreo' => 'https://%s.sii.cl/DTEWS/services/%s?WSDL',
    ];

    /**
     * Indica si el ambiente es el de certificación.
     *
     * @return bool
     */
    public function isCertificacion(): bool
    {
        return $this === self::CERTIFICACION;
    }

    /**
     * Entrega el servidor del SII según el tipo solicitado.
     *
     * @param string $tipo Es el tipo de servidor que se está solicitando.
     * @return string Nombre del servidor al que se debe conectar en el SII.
     */
    public function getServidor(string $tipo = 'default'): string
    {
        return self::SERVIDORES[$tipo][$this->value]
            ?? self::SERVIDORES['default'][$this->value]
        ;
    }

    /**
     * Entrega la URL de un WSDL según su servicio.
     *
     * @param string $servicio El servicio para el que se desea su WSDL.
     * @return string WSDL del servicio si fue encontrado o el WSDL por defecto
     * en el caso que no exista un WSDL específico para el servicio.
     */
    public function getWsdl(string $servicio): string
    {
        $wsdl = self::WSDL_URLS[$servicio] ?? self::WSDL_URLS['default'];
        $servidor = $this->getServidor();

        return sprintf($wsdl, $servidor, $servicio);
    }

    /**
     * Obtiene la ruta completa a un archivo WSDL.
     *
     * @param string $service Servicio para el que se busca su WSDL.
     * @return string|null Ubicación del WSDL o `null` si no se encontró.
     */
    public function getWsdlPath(string $service): ?string
    {
        $server = $this->getServidor();
        $wsdlPath = dirname(__DIR__, 6);
        $filepath = sprintf('%s/%s/%s.wsdl', $wsdlPath, $server, $service);

        return is_readable($filepath) ? realpath($filepath) : null;
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
}
