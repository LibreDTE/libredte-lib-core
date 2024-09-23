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

use DateTime;
use phpseclib3\File\X509;

/**
 * Clase que representa un certificado digital.
 */
class Certificate
{
    /**
     * Clave pública (certificado).
     *
     * @var string
     */
    private string $publicKey;

    /**
     * Clave privada.
     *
     * @var string
     */
    private string $privateKey;

    /**
     * Detalles de la clave privada.
     *
     * @var array
     */
    private array $privateKeyDetails;

    /**
     * Datos parseados del certificado X509.
     *
     * @var array
     */
    private array $data;

    /**
     * Contructor del certificado digital.
     *
     * @param string $publicKey Clave pública (certificado).
     * @param string $privateKey Clave privada.
     */
    public function __construct(string $publicKey, string $privateKey)
    {
        $this->publicKey = CertificateUtils::normalizePublicKey($publicKey);
        $this->privateKey = CertificateUtils::normalizePrivateKey($privateKey);
    }

    /**
     * Entrega la clave pública (certificado) de la firma.
     *
     * @param bool $clean Si se limpia el contenido del certificado.
     * @return string Contenido del certificado, clave pública del certificado
     * digital, en base64.
     */
    public function getPublicKey(bool $clean = false): string
    {
        if ($clean) {
            return trim(str_replace(
                ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'],
                '',
                $this->publicKey
            ));
        }

        return $this->publicKey;
    }

    /**
     * Entrega la clave pública (certificado) de la firma.
     *
     * @param bool $clean Si se limpia el contenido del certificado.
     * @return string Contenido del certificado, clave pública del certificado
     * digital, en base64.
     */
    public function getCertificate(bool $clean = false): string
    {
        return $this->getPublicKey($clean);
    }

    /**
     * Entrega la clave privada de la firma.
     *
     * @param bool $clean Si se limpia el contenido de la clave privada.
     * @return string Contenido de la clave privada del certificado digital
     * en base64.
     */
    public function getPrivateKey(bool $clean = false): string
    {
        if ($clean) {
            return trim(str_replace(
                ['-----BEGIN PRIVATE KEY-----', '-----END PRIVATE KEY-----'],
                '',
                $this->privateKey
            ));
        }

        return $this->privateKey;
    }

    /**
     * Entrega los detalles de la llave privada.
     *
     * @return array
     */
    public function getPrivateKeyDetails(): array
    {
        if (!isset($this->privateKeyDetails)) {
            $this->privateKeyDetails = openssl_pkey_get_details(
                openssl_pkey_get_private($this->privateKey)
            );
        }

        return $this->privateKeyDetails;
    }

    /**
     * Entrega los datos del certificado.
     *
     * Alias de getCertX509().
     *
     * @return array Arreglo con todos los datos del certificado.
     */
    public function getData(): array
    {
        if (!isset($this->data)) {
            $this->data = openssl_x509_parse($this->publicKey);
        }

        return $this->data;
    }

    /**
     * Entrega el ID asociado al certificado.
     *
     * El ID es el RUN que debe estar en una extensión, esto es lo estándar.
     * También podría estar en el campo `serialNumber`, algunos proveedores lo
     * colocan en este campo, también es más fácil para pruebas
     *
     * @param bool $force_upper Si se fuerza a mayúsculas.
     * @return string ID asociado al certificado en formato: 11222333-4.
     */
    public function getID(bool $force_upper = true): string
    {
        // Verificar el serialNumber en el subject del certificado.
        $serialNumber = $this->getData()['subject']['serialNumber'] ?? null;
        if ($serialNumber !== null) {
            $serialNumber = ltrim(trim($serialNumber), '0');
            return $force_upper ? strtoupper($serialNumber) : $serialNumber;
        }

        // Obtener las extensiones del certificado.
        $x509 = new X509();
        $cert = $x509->loadX509($this->publicKey);
        if (isset($cert['tbsCertificate']['extensions'])) {
            foreach ($cert['tbsCertificate']['extensions'] as $extension) {
                if (
                    $extension['extnId'] === 'id-ce-subjectAltName'
                    && isset($extension['extnValue'][0]['otherName']['value']['ia5String'])
                ) {
                    $id = ltrim(
                        trim($extension['extnValue'][0]['otherName']['value']['ia5String']),
                        '0'
                    );
                    return $force_upper ? strtoupper($id) : $id;
                }
            }
        }

        // No se encontró el ID, se lanza excepción.
        throw new CertificateException(
            'No fue posible obtener el ID (RUN) del certificado digital (firma electrónica). Se recomienda verificar el formato y contraseña del certificado.'
        );
    }

    /**
     * Entrega el CN del subject.
     *
     * @return string CN del subject.
     */
    public function getName(): string
    {
        $name = $this->getData()['subject']['CN'] ?? null;
        if ($name === null) {
            throw new CertificateException(
                'No fue posible obtener el Name (subject.CN) de la firma.'
            );
        }

        return $name;
    }

    /**
     * Entrega el emailAddress del subject.
     *
     * @return string EmailAddress del subject.
     */
    public function getEmail(): string
    {
        $email = $this->getData()['subject']['emailAddress'] ?? null;
        if ($email === null) {
            throw new CertificateException(
                'No fue posible obtener el Email (subject.emailAddress) de la firma.'
            );
        }

        return $email;
    }

    /**
     * Entrega desde cuando es válida la firma.
     *
     * @return string Fecha y hora desde cuando es válida la firma.
     */
    public function getFrom(): string
    {
        return date('Y-m-d\TH:i:s', $this->getData()['validFrom_time_t']);
    }

    /**
     * Entrega hasta cuando es válida la firma.
     *
     * @return string Fecha y hora hasta cuando es válida la firma.
     */
    public function getTo(): string
    {
        return date('Y-m-d\TH:i:s', $this->getData()['validTo_time_t']);
    }

    /**
     * Entrega los días totales que la firma es válida.
     *
     * @return int Días totales en que la firma es válida.
     */
    public function getTotalDays(): int
    {
        $start = new DateTime($this->getFrom());
        $end = new DateTime($this->getTo());
        $diff = $start->diff($end);
        return (int) $diff->format('%a');
    }

    /**
     * Entrega los días que faltan para que la firma expire.
     *
     * @param string|null $desde Fecha desde la que se calcula.
     * @return int Días que faltan para que la firma expire.
     */
    public function getExpirationDays(?string $desde = null): int
    {
        if ($desde === null) {
            $desde = date('Y-m-d\TH:i:s');
        }
        $start = new DateTime($desde);
        $end = new DateTime($this->getTo());
        $diff = $start->diff($end);
        return (int) $diff->format('%a');
    }

    /**
     * Indica si la firma está vigente o vencida.
     *
     * NOTE: Este método también validará que la firma no esté vigente en el
     * futuro. O sea, que la fecha desde cuándo está vigente debe estar en el
     * pasado.
     *
     * @param string|null $when Fecha de referencia para validar la vigencia.
     * @return bool `true` si la firma está vigente, `false` si está vencida.
     */
    public function isActive(?string $when = null): bool
    {
        if ($when === null) {
            $when = date('Y-m-d').'T23:59:59';
        }

        if (!isset($when[10])) {
            $when .= 'T23:59:59';
        }

        return $when >= $this->getFrom() && $when <= $this->getTo();
    }

    /**
     * Entrega el nombre del emisor de la firma.
     *
     * @return string CN del issuer.
     */
    public function getIssuer(): string
    {
        return $this->getData()['issuer']['CN'];
    }

    /**
     * Obtiene el módulo de la clave privada.
     *
     * @return string Módulo en base64.
     */
    public function getModulus(int $wordwrap = CertificateUtils::WORDWRAP): string
    {
        $modulus = $this->getPrivateKeyDetails()['rsa']['n'] ?? null;

        if ($modulus === null) {
            throw new CertificateException(
                'No fue posible obtener el módulo de la clave privada.'
            );
        }

        return CertificateUtils::wordwrap(base64_encode($modulus), $wordwrap);
    }

    /**
     * Obtiene el exponente público de la clave privada.
     *
     * @return string Exponente público en base64.
     */
    public function getExponent(int $wordwrap = CertificateUtils::WORDWRAP): string
    {
        $exponent = $this->getPrivateKeyDetails()['rsa']['e'] ?? null;

        if ($exponent === null) {
            throw new CertificateException(
                'No fue posible obtener el exponente de la clave privada.'
            );
        }

        return CertificateUtils::wordwrap(base64_encode($exponent), $wordwrap);
    }
}
