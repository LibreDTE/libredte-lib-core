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

namespace libredte\lib\Core\Service;

/**
 * Clase para administrar las rutas que utiliza la biblioteca para sus
 * archivos.
 */
class PathManager
{
    /**
     * Ubicación base, es la ruta donde está la biblioteca.
     */
    private const BASE_PATH = __DIR__ . '/../..';

    /**
     * Ubicación de los recursos.
     */
    private const RESOURCES_PATH = self::BASE_PATH . '/resources';

    /**
     * Ubicación de los tests.
     */
    private const TESTS_PATH = self::BASE_PATH . '/tests';

    /**
     * Ubicación de los datos variables que puede escribir la biblioteca en su
     * ciclo de ejecución normal.
     */
    private const VAR_PATH = self::BASE_PATH . '/var';

    /**
     * Ubicación de los certificados dentro del directorio de recursos.
     */
    private const CERTIFICATES_PATH = self::RESOURCES_PATH . '/certificates';

    /**
     * Ubicación de los archivos PHP de datos dentro del directorio de recursos.
     */
    private const DATA_PATH = self::RESOURCES_PATH . '/data';

    /**
     * Ubicación de los esquemas XML dentro del directorio de recursos.
     */
    private const SCHEMAS_PATH = self::RESOURCES_PATH . '/schemas';

    /**
     * Ubicación de las plantillas dentro del directorio de recursos.
     */
    private const TEMPLATES_PATH = self::RESOURCES_PATH . '/templates';

    /**
     * Ubicación de los WSDL de API SOAP dentro del directorio de recursos.
     */
    private const WSDL_PATH = self::RESOURCES_PATH . '/wsdl';

    /**
     * Ubicación del directorio de caché dentro del directorio de datos
     * variables.
     */
    private const CACHE_PATH = self::VAR_PATH . '/cache';

    /**
     * Obtiene la ruta completa del directorio de certificados o de un
     * certificado en específico si fue pasado.
     *
     * @param string $filename Nombre del archivo del certificado.
     * @return string|null Ubicación del certificado o `null` si no se encontró.
     */
    public static function getCertificatesPath(?string $filename = null): ?string
    {
        if ($filename === null) {
            return realpath(self::CERTIFICATES_PATH);
        }

        $filepath = sprintf('%s/%s', self::CERTIFICATES_PATH, $filename);
        return self::checkFilepath($filepath);
    }

    /**
     * Obtiene la ruta completa de un archivo PHP de datos.
     *
     * @param string $key Clave del archivo de datos.
     * @return string|null Ubicación del archivo o `null` si no se encontró.
     */
    public static function getDataPath(string $key): ?string
    {
        $filepath = sprintf('%s/%s.php', self::DATA_PATH, $key);
        return self::checkFilepath($filepath);
    }

    /**
     * Obtiene la ruta completa del directorio de esquemas XML o de un
     * esquema XML en específico si fue pasado.
     *
     * @param string $filename Nombre del archivo del esquema XML.
     * @return string|null Ubicación del esquema XML o `null` si no se encontró.
     */
    public static function getSchemasPath(?string $filename = null): ?string
    {
        if ($filename === null) {
            return realpath(self::SCHEMAS_PATH);
        }

        $filepath = sprintf('%s/%s', self::SCHEMAS_PATH, $filename);
        return self::checkFilepath($filepath);
    }

    /**
     * Obtiene la ruta completa del directorio de plantillas.
     *
     * @return string
     */
    public static function getTemplatesPath(): string
    {
        return realpath(self::TEMPLATES_PATH);
    }

    /**
     * Obtiene la ruta completa a un archivo WSDL en el almacenamiento local.
     *
     * @param string $server Servidor del SII al que se busca un WSDL.
     * @param string $service Servicio para el que se busca su WSDL.
     * @return string|null Ubicación del WSDL o `null` si no se encontró.
     */
    public static function getWsdlPath(string $server, string $service): ?string
    {
        $filepath = sprintf('%s/%s/%s.wsdl', self::WSDL_PATH, $server, $service);
        return self::checkFilepath($filepath);
    }

    /**
     * Obtiene la ruta completa del directorio de caché.
     *
     * @return string
     */
    public static function getCachePath(): string
    {
        return realpath(self::CACHE_PATH);
    }

    /**
     * Obtiene la ruta completa del directorio de pruebas.
     *
     * @return string
     */
    public static function getTestsPath(): string
    {
        return realpath(self::TESTS_PATH);
    }

    /**
     * Valida que una ruta a un archivo se pueda leer (y por ende que exista).
     *
     * @param string $filepath Ruta completa al archivo a verificar.
     * @return string|null Ruta al archivo si es válida o `null` si no lo es.
     */
    private static function checkFilepath(string $filepath): ?string
    {
        return is_readable($filepath) ? realpath($filepath) : null;
    }
}
