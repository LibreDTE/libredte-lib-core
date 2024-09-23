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

namespace libredte\lib\Core\Signature;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;

/**
 * Clase que proporciona utilidades comunes para la firma electrónica.
 */
class CertificateUtils
{
    /**
     * Ancho por defecto al aplicar la función wordwrap().
     */
    public const WORDWRAP = 64;

    /**
     * Normaliza una clave pública (certificado) añadiendo encabezados y pies
     * si es necesario.
     *
     * @param string $publicKey Clave pública que se desea normalizar.
     * @param int $wordwrap Largo al que se debe dejar cada línea del archivo.
     * @return string Clave pública normalizada.
     */
    public static function normalizePublicKey(
        string $publicKey,
        int $wordwrap = self::WORDWRAP
    ): string
    {
        if (!str_contains($publicKey, '-----BEGIN CERTIFICATE-----')) {
            $body = trim($publicKey);
            $publicKey = '-----BEGIN CERTIFICATE-----' . "\n";
            $publicKey .= self::wordwrap($body, $wordwrap) . "\n";
            $publicKey .= '-----END CERTIFICATE-----' . "\n";
        }

        return $publicKey;
    }

    /**
     * Normaliza una clave privada añadiendo encabezados y pies si es necesario.
     *
     * @param string $privateKey Clave privada que se desea normalizar.
     * @param int $wordwrap Largo al que se debe dejar cada línea del archivo.
     * @return string Clave privada normalizada.
     */
    public static function normalizePrivateKey(
        string $privateKey,
        int $wordwrap = self::WORDWRAP
    ): string
    {
        if (!str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')) {
            $body = trim($privateKey);
            $privateKey = '-----BEGIN PRIVATE KEY-----' . "\n";
            $privateKey .= self::wordwrap($body, $wordwrap) . "\n";
            $privateKey .= '-----END PRIVATE KEY-----' . "\n";
        }

        return $privateKey;
    }

    /**
     * Corta el string a un largo fijo por línea.
     *
     * @param string $string String a recortar.
     * @param integer $width Ancho, o largo, máximo de cada línea.
     * @param string $break Caracter para el "corte" o salto de línea.
     * @param boolean $cut_long_words Si se deben cortar igual palabras largas.
     * @return string String ajustado al largo solicitado.
     */
    public static function wordwrap(
        string $string,
        int $width = self::WORDWRAP,
        string $break = "\n",
        bool $cut_long_words = true
    ): string
    {
        return wordwrap($string, $width, $break, $cut_long_words);
    }

    /**
     * Genera una clave pública a partir de un módulo y un exponente.
     *
     * @param string $modulus Módulo de la clave.
     * @param string $exponent Exponente de la clave.
     * @return string Clave pública generada.
     */
    public static function generatePublicKeyFromModulusExponent(
        string $modulus,
        string $exponent
    ): string
    {
        $modulus = new BigInteger(base64_decode($modulus), 256);
        $exponent = new BigInteger(base64_decode($exponent), 256);

        $rsa = PublicKeyLoader::load([
            'n' => $modulus,
            'e' => $exponent
        ]);

        return $rsa->toString('PKCS1');
    }
}
