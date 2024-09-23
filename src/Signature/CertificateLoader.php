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

namespace libredte\lib\Core\Signature;

/**
 * Clase que maneja la configuración y carga de certificados digitales para la
 * firma electrónica.
 */
class CertificateLoader
{
    /**
     * Crea una instancia de Certificate desde un archivo que contiene el
     * certificado digital en formato PKCS#12.
     *
     * @param string $filepath Ruta al archivo que contiene el certificado
     * digital.
     * @param string $password Contraseña para acceder al contenido del
     * certificado.
     * @return Certificate Instancia de la clase Certificate que contiene la
     * clave privada y el certificado público.
     * @throws CertificateException Si no se puede leer el archivo o cargar el
     * certificado.
     */
    public static function createFromFile(string $filepath, string $password): Certificate
    {
        if (!is_readable($filepath)) {
            throw new CertificateException(sprintf(
                'No fue posible leer el archivo del certificado digital desde %s',
                $filepath
            ));
        }

        $data = file_get_contents($filepath);

        return self::createFromData($data, $password);
    }

    /**
     * Crea una instancia de Certificate desde un string que contiene los datos
     * del certificado digital en formato PKCS#12.
     *
     * @param string $data String que contiene los datos del certificado
     * digital.
     * @param string $password Contraseña para acceder al contenido del
     * certificado.
     * @return Certificate Instancia de la clase Certificate que contiene la
     * clave privada y el certificado público.
     * @throws CertificateException Si no se puede cargar el certificado desde
     * los datos.
     */
    public static function createFromData(string $data, string $password): Certificate
    {
        $certs = [];

        if (openssl_pkcs12_read($data, $certs, $password) === false) {
            throw new CertificateException(sprintf(
                'No fue posible leer los datos del certificado digital.',
            ));
        }

        return self::createFromKeys($certs['cert'], $certs['pkey']);
    }

    /**
     * Crea una instancia de Certificate desde un arreglo que contiene las
     * claves pública y privada.
     *
     * @param array $data Arreglo que contiene las claves 'publicKey'
     * (o 'cert') y 'privateKey' (o 'pkey').
     * @return Certificate Instancia de la clase Certificate que contiene la
     * clave privada y el certificado público.
     */
    public static function createFromArray(array $data): Certificate
    {
        $publicKey = $data['publicKey'] ?? $data['cert'] ?? null;
        $privateKey = $data['privateKey'] ?? $data['pkey'] ?? null;

        if ($publicKey === null) {
            throw new CertificateException(
                'La clave pública del certificado no fue encontrada.'
            );
        }

        if ($privateKey === null) {
            throw new CertificateException(
                'La clave privada del certificado no fue encontrada.'
            );
        }

        return self::createFromKeys($publicKey, $privateKey);
    }

    /**
     * Crea una instancia de Certificate a partir de una clave pública y una
     * clave privada.
     *
     * @param string $publicKey Clave pública del certificado.
     * @param string $privateKey Clave privada asociada al certificado.
     * @return Certificate Instancia de la clase Certificate que contiene la
     * clave privada y el certificado público.
     */
    public static function createFromKeys(string $publicKey, string $privateKey): Certificate
    {
        return new Certificate($publicKey, $privateKey);
    }
}
