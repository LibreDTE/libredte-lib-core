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

/**
 * Clase que se encarga de generar certificados autofirmados y retornarlos como
 * un string de datos, un arreglo o una instancia de Certificate.
 */
class CertificateFaker
{
    /**
     * Datos del sujeto del certificado.
     *
     * @var array
     */
    private array $subject;

    /**
     * Datos del emisor del certificado.
     *
     * @var array
     */
    private array $issuer;

    /**
     * Validez del certificado en formato UNIX timestamp.
     *
     * @var array
     */
    private array $validity;

    /**
     * Contraseña para proteger la clave privada en el certificado.
     *
     * @var string
     */
    private string $password;

    /**
     * Constructor de la clase.
     *
     * Establece valores por defecto para el sujeto, emisor, validez y
     * contraseña.
     */
    public function __construct()
    {
        $this->setSubject();
        $this->setIssuer();
        $this->setValidity();
        $this->setPassword();
    }

    /**
     * Configura los datos del sujeto del certificado.
     *
     * @param string $C País del sujeto.
     * @param string $ST Estado o provincia del sujeto.
     * @param string $L Localidad del sujeto.
     * @param string $O Organización del sujeto.
     * @param string $OU Unidad organizativa del sujeto.
     * @param string $CN Nombre común del sujeto.
     * @param string $emailAddress Correo electrónico del sujeto.
     * @param string $serialNumber Número de serie del sujeto.
     * @param string $title Título del sujeto.
     * @return self
     */
    public function setSubject(
        string $C = 'CL',
        string $ST = 'Colchagua',
        string $L = 'Santa Cruz',
        string $O = 'Organización Intergaláctica de Robots',
        string $OU = 'Tecnología',
        string $CN = 'Daniel',
        string $emailAddress = 'daniel.bot@example.com',
        string $serialNumber = '11222333-9',
        string $title = 'Bot',
    ): self
    {
        if (empty($CN) || empty($emailAddress) || empty($serialNumber)) {
            throw new CertificateException(
                'El CN, emailAddress y serialNumber son obligatorios.'
            );
        }

        $this->subject = [
            'C' => $C,
            'ST' => $ST,
            'L' => $L,
            'O' => $O,
            'OU' => $OU,
            'CN' => $CN,
            'emailAddress' => $emailAddress,
            'serialNumber' => strtoupper($serialNumber),
            'title' => $title,
        ];

        return $this;
    }

    /**
     * Configura los datos del emisor del certificado.
     *
     * @param string $C País del emisor.
     * @param string $ST Estado o provincia del emisor.
     * @param string $L Localidad del emisor.
     * @param string $O Organización del emisor.
     * @param string $OU Unidad organizativa del emisor.
     * @param string $CN Nombre común del emisor.
     * @param string $emailAddress Correo electrónico del emisor.
     * @param string $serialNumber Número de serie del emisor.
     * @return self
     */
    public function setIssuer(
        string $C = 'CL',
        string $ST = 'Colchagua',
        string $L = 'Santa Cruz',
        string $O = 'LibreDTE',
        string $OU = 'Facturación Electrónica',
        string $CN = 'LibreDTE Autoridad Certificadora de Pruebas',
        string $emailAddress = 'fakes-certificates@libredte.cl',
        string $serialNumber = '76192083-9',
    ): self
    {
        $this->issuer = [
            'C' => $C,
            'ST' => $ST,
            'L' => $L,
            'O' => $O,
            'OU' => $OU,
            'CN' => $CN,
            'emailAddress' => $emailAddress,
            'serialNumber' => strtoupper($serialNumber),
        ];

        return $this;
    }

    /**
     * Configura la validez del certificado.
     *
     * @param string|null $validTo Fecha de validez hasta, en formato 'Y-m-d'.
     * Si no se proporciona, se establece un año a partir de la fecha actual.
     * @return self
     */
    public function setValidity(string $validTo = null): self
    {
        $validFrom = (int) date('U');

        if ($validTo === null) {
            $validTo = date('Y-m-d', strtotime('+1 year', $validFrom));
        }
        $validTo = strtotime($validTo);

        $days = (int) (($validTo - $validFrom) / (60 * 60 * 24));

        $this->validity = [
            'from' => $validFrom,
            'to' => $validTo,
            'days' => $days,
        ];

        return $this;
    }

    /**
     * Configura la contraseña para proteger la clave privada.
     *
     * @param string $password Contraseña para proteger la clave privada.
     * @return void
     */
    public function setPassword(string $password = 'i_love_libredte')
    {
        $this->password = $password;
    }

    /**
     * Obtiene la contraseña configurada.
     *
     * @return string Contraseña configurada.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Genera un certificado digital en formato PKCS#12 y lo devuelve como un
     * string.
     *
     * @return string Certificado digital en formato PKCS#12.
     */
    public function createAsString(): string
    {
        // Días de validez del certificado (emisor y sujeto).
        $days = $this->validity['days'];

        // Crear clave privada y CSR para el emisor.
        $issuerPrivateKey = openssl_pkey_new();
        $issuerCsr = openssl_csr_new($this->issuer, $issuerPrivateKey);

        // Crear certificado autofirmado para el emisor.
        $issuerCert = openssl_csr_sign(
            $issuerCsr,         // CSR del emisor.
            null,               // Certificado emisor (null indica que es autofirmado).
            $issuerPrivateKey,  // Clave privada del emisor.
            $days,              // Número de días de validez (misma sujeto).
            [],                 // Opciones adicionales.
            666                 // Número de serie del certificado.
        );

        // Crear clave privada y CSR para el sujeto.
        $subjectPrivateKey = openssl_pkey_new();
        $subjectCsr = openssl_csr_new($this->subject, $subjectPrivateKey);

        // Usar el certificado del emisor para firmar el CSR del sujeto.
        $subjectCert = openssl_csr_sign(
            $subjectCsr,        // La solicitud de firma del certificado (CSR).
            $issuerCert,        // Certificado emisor.
            $issuerPrivateKey,  // Clave privada del emisor.
            $days,              // Número de días de validez.
            [],                 // Opciones adicionales.
            69                  // Número de serie del certificado.
        );

        // Exportar el certificado final en formato PKCS#12.
        openssl_pkcs12_export(
            $subjectCert,
            $data,
            $subjectPrivateKey,
            $this->password
        );

        // Entregar los datos del certificado digital.
        return $data;
    }

    /**
     * Genera un certificado digital en formato PKCS#12 y lo devuelve como un
     * arreglo.
     *
     * @return array Certificado digital en formato PKCS#12.
     */
    public function createAsArray(): array
    {
        $data = $this->createAsString();
        $array = [];
        openssl_pkcs12_read($data, $array, $this->password);

        return $array;
    }

    /**
     * Genera un certificado digital y lo devuelve como una instancia de
     * Certificate.
     *
     * @return Certificate Instancia de Certificate.
     */
    public function create(): Certificate
    {
        $array = $this->createAsArray();

        return CertificateLoader::createFromArray($array);
    }
}
