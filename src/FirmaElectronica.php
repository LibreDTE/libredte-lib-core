<?php

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib;

/**
 * Clase para trabajar con firma electrónica, permite firmar y verificar firmas.
 * Provee los métodos: sign(), verify(), signXML() y verifyXML()
 */
class FirmaElectronica
{

    private $config; ///< Configuración de la firma electrónica
    private $certs; ///< Certificados digitales de la firma
    private $data; ///< Datos del certificado digial

    /**
     * Constructor para la clase: crea configuración y carga certificado digital.
     *
     * Para pasar una configuración para la firma electrónica, existen 2 opciones.
     * Se puede pasar a través de un arreglo con los índices 'file' y 'pass', donde
     * 'file' es la ruta hacia el archivo .p12 que contiene tanto la clave privada
     * como la pública, y 'pass' es la contraseña para abrir dicho archivo. Ejemplo:
     *
     * ```php
     * $firma_config = ['file' => '/ruta/al/certificado.p12', 'pass' => 'contraseña'];
     * $firma = new \libredte\lib\FirmaElectronica($firma_config);
     * ```
     *
     * También se permite que, en vez de pasar la ruta al certificado .p12, se pase
     * el contenido del certificado. Esto servirá, por ejemplo, si los datos del
     * archivo están almacenados en una base de datos. Ejemplo:
     *
     * ```php
     * $firma_config = ['data' => file_get_contents('/ruta/al/certificado.p12'), 'pass' => 'contraseña'];
     * $firma = new \libredte\lib\FirmaElectronica($firma_config);
     * ```
     *
     * Finalmente, es posible pasar directamente los datos de la firma en los índices:
     * cert y pkey.
     *
     * @param array $config Configuración para la firma electrónica (ruta/datos y clave).
     */
    public function __construct(array $config)
    {
        // crear configuración
        $this->config = array_merge([
            'file' => null,
            'pass' => null,
            'wordwrap' => 64,
        ], $config);
        // leer datos de la firma electrónica desde configuración con índices: cert y pkey
        if (!empty($this->config['cert']) and !empty($this->config['pkey'])) {
            $this->certs = [
                'cert' => $this->config['cert'],
                'pkey' => $this->config['pkey'],
            ];
            unset($this->config['cert'], $this->config['pkey']);
        }
        // se pasó el archivo de la firma o bien los datos de la firma
        else {
            // cargar firma electrónica desde el contenido del archivo .p12 si no
            // se pasaron como datos del arreglo de configuración
            if (empty($this->config['data']) && $this->config['file']) {
                if (is_readable($this->config['file'])) {
                    $this->config['data'] = file_get_contents($this->config['file']);
                } else {
                    return $this->error('Archivo de la firma electrónica '.basename($this->config['file']).' no puede ser leído.');
                }
            }
            // leer datos de la firma desde el archivo que se ha pasado
            if (!empty($this->config['data'])) {
                if (openssl_pkcs12_read($this->config['data'], $this->certs, $this->config['pass']) === false) {
                    return $this->error('No fue posible leer los datos de la firma electrónica (verificar la contraseña).');
                }
                unset($this->config['data']);
            }
        }
        $this->data = openssl_x509_parse($this->certs['cert']);
    }

    /**
     * Método que realiza diferentes validaciones de la firma electrónica
     * para determinar si es posible o no usarla.
     */
    public function check()
    {
        // si hay algún log, hubo un error (por defecto se leen los severity = LOG_ERR)
        $logs = Log::readAll();
        if (!empty($logs)) {
            foreach ($logs as $Log) {
                if ($Log->code == Estado::FIRMA_ERROR) {
                    throw new \Exception($Log);
                }
            }
        }
        // validar que venga el RUN de la firma y que si termina en K no sea minúscula
        $run = trim($this->getID(false));
        if (empty($run)) {
            throw new \Exception('No fue posible obtener el RUN de la firma electrónica (verificar contraseña).');
        }
        if (explode('-', $run)[1] == 'k') {
            throw new \Exception('El RUN '.$run.' asociado a la firma no es válido, termina en "k" (minúscula). Debe adquirir una nueva firma y al comprarla corroborar que la "K" sea mayúscula. Se recomienda no comprar con el mismo proveedor: '.$this->getIssuer().'.');
        }
        // validar que la firma está vigente
        if (!$this->isActive()) {
            throw new \Exception('La firma venció el '.$this->getTo().', debe usar una firma vigente. Si no posee una, debe adquirirla con un proveedor autorizado por el SII.');
        }
    }

    /**
     * Método para generar un error usando una excepción de SowerPHP o terminar
     * el script si no se está usando el framework
     * @param msg Mensaje del error
     */
    private function error($msg)
    {
        $msg = Estado::get(Estado::FIRMA_ERROR, $msg);
        Log::write(Estado::FIRMA_ERROR, $msg);
        return false;
    }

    /**
     * Método que agrega el inicio y fin de un certificado (clave pública).
     * @param cert Certificado que se desea normalizar.
     * @return string Certificado con el inicio y fin correspondiente.
     */
    private function normalizeCert(string $cert): string
    {
        if (strpos($cert, '-----BEGIN CERTIFICATE-----') === false) {
            $body = trim($cert);
            $cert = '-----BEGIN CERTIFICATE-----'."\n";
            $cert .= wordwrap($body, $this->config['wordwrap'], "\n", true)."\n";
            $cert .= '-----END CERTIFICATE-----'."\n";
        }
        return $cert;
    }

    /**
     * Método que entrega el RUN/RUT asociado al certificado.
     * @return RUN/RUT asociado al certificado en formato: 11222333-4
     */
    public function getID($force_upper = true)
    {
        // RUN/RUT se encuentra en la extensión del certificado, esto de acuerdo
        // a Ley 19.799 sobre documentos electrónicos y firma electrónica
        $x509 = new \phpseclib\File\X509();
        $cert = $x509->loadX509($this->certs['cert']);
        if (isset($cert['tbsCertificate']['extensions'])) {
            foreach ($cert['tbsCertificate']['extensions'] as $e) {
                if ($e['extnId'] == 'id-ce-subjectAltName') {
                    $id = ltrim($e['extnValue'][0]['otherName']['value']['ia5String'], '0');
                    return $force_upper ? strtoupper($id) : $id;
                }
            }
        }
        // se obtiene desde serialNumber (esto es solo para que funcione la firma para tests)
        if (isset($this->data['subject']['serialNumber'])) {
            $id = ltrim($this->data['subject']['serialNumber'], '0');
            return $force_upper ? strtoupper($id) : $id;
        }
        // no se encontró el RUN
        return $this->error('No fue posible obtener el ID de la firma.');
    }

    /**
     * Método que entrega el CN del subject.
     * @return string CN del subject.
     */
    public function getName()
    {
        if (isset($this->data['subject']['CN'])) {
            return $this->data['subject']['CN'];
        }
        return $this->error('No fue posible obtener el Name (subject.CN) de la firma.');
    }

    /**
     * Método que entrega el emailAddress del subject.
     * @return string emailAddress del subject.
     */
    public function getEmail()
    {
        if (isset($this->data['subject']['emailAddress'])) {
            return $this->data['subject']['emailAddress'];
        }
        return $this->error('No fue posible obtener el Email (subject.emailAddress) de la firma.');
    }

    /**
     * Método que entrega desde cuando es válida la firma.
     * @return string validFrom_time_t
     */
    public function getFrom(): string
    {
        return date('Y-m-d H:i:s', $this->data['validFrom_time_t']);
    }

    /**
     * Método que entrega hasta cuando es válida la firma.
     * @return string validTo_time_t
     */
    public function getTo(): string
    {
        return date('Y-m-d H:i:s', $this->data['validTo_time_t']);
    }

    /**
     * Método que entrega los días totales que la firma es válida.
     *
     * Calcula y devuelve el número total de días durante los cuales la firma es válida,
     * basado en las fechas de inicio y fin de la validez de la firma.
     *
     * @return int Días totales en que la firma es válida.
     */
    public function getTotalDays(): int
    {
        $start = new \DateTime($this->getFrom());
        $end = new \DateTime($this->getTo());
        $diff = $start->diff($end);
        return (int)$diff->format('%a');
    }

    /**
     * Método que entrega los días que faltan para que la firma expire.
     * @return int Días que faltan para que la firma expire
     */
    public function getExpirationDays($desde = null): int
    {
        if (!$desde) {
            $desde = date('Y-m-d H:i:s');
        }
        $start = new \DateTime($desde);
        $end = new \DateTime($this->getTo());
        $diff = $start->diff($end);
        return (int)$diff->format('%a');
    }

    /**
     * Método que indica si la firma está vigente o vencida
     * @return bool =true si la firma está vigente, =false si está vencida
     */
    public function isActive($when = null): bool
    {
        if (!$when) {
            $when = date('Y-m-d').' 00:00:00';
        }
        return $this->getTo() > $when;
    }

    /**
     * Método que entrega el nombre del emisor de la firma.
     * @return string CN del issuer.
     */
    public function getIssuer(): string
    {
        return $this->data['issuer']['CN'];
    }

    /**
     * Método que entrega los datos del certificado
     * @return array Arreglo con todo los datos del certificado
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Método que obtiene el módulo de la clave privada.
     * @return string Módulo en base64.
     */
    public function getModulus(): string
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->certs['pkey']));
        return wordwrap(base64_encode($details['rsa']['n']), $this->config['wordwrap'], "\n", true);
    }

    /**
     * Método que obtiene el exponente público de la clave privada.
     * @return string Exponente público en base64.
     */
    public function getExponent(): string
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->certs['pkey']));
        return wordwrap(base64_encode($details['rsa']['e']), $this->config['wordwrap'], "\n", true);
    }

    /**
     * Método que entrega el certificado de la firma.
     * @return string Contenido del certificado, clave pública del certificado digital, en base64.
     */
    public function getCertificate(bool $clean = false): string
    {
        if ($clean) {
            return trim(str_replace(
                ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'],
                '',
                $this->certs['cert']
            ));
        } else {
            return $this->certs['cert'];
        }
    }

    /**
     * Método que entrega la clave privada de la firma.
     * @return string Contenido de la clave privada del certificado digital en base64.
     */
    public function getPrivateKey(bool $clean = false): string
    {
        if ($clean) {
            return trim(str_replace(
                ['-----BEGIN PRIVATE KEY-----', '-----END PRIVATE KEY-----'],
                '',
                $this->certs['pkey']
            ));
        } else {
            return $this->certs['pkey'];
        }
    }

    /**
     * Método para realizar la firma de datos.
     * @param data Datos que se desean firmar.
     * @param signature_alg Algoritmo que se utilizará para firmar (por defect SHA1).
     * @return string Firma digital de los datos en base64 o =false si no se pudo firmar.
     */
    public function sign($data, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        $signature = null;
        if (!openssl_sign($data, $signature, $this->certs['pkey'], $signature_alg)) {
            return $this->error('No fue posible firmar los datos.');
        }
        return base64_encode($signature);
    }

    /**
     * Método que verifica la firma digital de datos.
     * @param data Datos que se desean verificar.
     * @param signature Firma digital de los datos en base64.
     * @param pub_key Certificado digital, clave pública, de la firma.
     * @param signature_alg Algoritmo que se usó para firmar (por defect SHA1).
     * @return bool =true si la firma está ok, =false si está mal o no se pudo determinar
     */
    public function verify($data, $signature, $pub_key = null, $signature_alg = OPENSSL_ALGO_SHA1): bool
    {
        if ($pub_key === null) {
            $pub_key = $this->certs['cert'];
        }
        $pub_key = $this->normalizeCert($pub_key);
        return openssl_verify($data, base64_decode($signature), $pub_key, $signature_alg) == 1 ? true : false;
    }

    /**
     * Método que firma un XML utilizando RSA y SHA1.
     * @link http://www.di-mgt.com.au/xmldsig2.html
     * @param xml Datos XML que se desean firmar.
     * @param reference Referencia a la que hace la firma.
     * @return string XML firmado o =false si no se pudo fimar.
     */
    public function signXML(string $xml, string $reference = '', $tag = null, $xmlns_xsi = false)
    {
        // normalizar 4to parámetro que puede ser boolean o array
        if (is_array($xmlns_xsi)) {
            $namespace = $xmlns_xsi;
            $xmlns_xsi = false;
        } else {
            $namespace = null;
        }
        // obtener objeto del XML que se va a firmar
        $doc = new XML();
        $doc->loadXML($xml);
        if (!$doc->documentElement) {
            return $this->error('No se pudo obtener el documentElement desde el XML a firmar (posible XML mal formado).');
        }
        // crear nodo para la firma
        $Signature = $doc->importNode((new XML())->generate([
            'Signature' => [
                '@attributes' => $namespace ? false : [
                    'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
                ],
                'SignedInfo' => [
                    '@attributes' => $namespace ? false : [
                        'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
                        'xmlns:xsi' => $xmlns_xsi ? 'http://www.w3.org/2001/XMLSchema-instance' : false,
                    ],
                    'CanonicalizationMethod' => [
                        '@attributes' => [
                            'Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                        ],
                    ],
                    'SignatureMethod' => [
                        '@attributes' => [
                            'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                        ],
                    ],
                    'Reference' => [
                        '@attributes' => [
                            'URI' => $reference,
                        ],
                        'Transforms' => [
                            'Transform' => [
                                '@attributes' => [
                                    'Algorithm' => $namespace ? 'http://www.altova.com' : 'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
                                ],
                            ],
                        ],
                        'DigestMethod' => [
                            '@attributes' => [
                                'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                            ],
                        ],
                        'DigestValue' => null,
                    ],
                ],
                'SignatureValue' => null,
                'KeyInfo' => [
                    'KeyValue' => [
                        'RSAKeyValue' => [
                            'Modulus' => null,
                            'Exponent' => null,
                        ],
                    ],
                    'X509Data' => [
                        'X509Certificate' => null,
                    ],
                ],
            ],
        ], $namespace)->documentElement, true);
        // calcular DigestValue
        if ($tag) {
            $item = $doc->documentElement->getElementsByTagName($tag)->item(0);
            if (!$item) {
                return $this->error('No fue posible obtener el nodo con el tag '.$tag.'.');
            }
            $digest = base64_encode(sha1($item->C14N(), true));
        } else {
            $digest = base64_encode(sha1($doc->C14N(), true));
        }
        $Signature->getElementsByTagName('DigestValue')->item(0)->nodeValue = $digest;
        // calcular SignatureValue
        $SignedInfo = $doc->saveHTML($Signature->getElementsByTagName('SignedInfo')->item(0));
        $firma = $this->sign($SignedInfo);
        if (!$firma) {
            return false;
        }
        $signature = wordwrap($firma, $this->config['wordwrap'], "\n", true);
        // reemplazar valores en la firma de
        $Signature->getElementsByTagName('SignatureValue')->item(0)->nodeValue = $signature;
        $Signature->getElementsByTagName('Modulus')->item(0)->nodeValue = $this->getModulus();
        $Signature->getElementsByTagName('Exponent')->item(0)->nodeValue = $this->getExponent();
        $Signature->getElementsByTagName('X509Certificate')->item(0)->nodeValue = $this->getCertificate(true);
        // agregar y entregar firma
        $doc->documentElement->appendChild($Signature);
        return $doc->saveXML();
    }

    /**
     * Método que verifica la validez de la firma de un XML utilizando RSA y SHA1.
     * @param xml_data Archivo XML que se desea validar.
     * @return bool =true si la firma del documento XML es válida o =false si no lo es.
     */
    public function verifyXML(string $xml_data, ?string $tag = null): bool
    {
        $doc = new XML();
        $doc->loadXML($xml_data);
        // preparar datos que se verificarán
        $SignaturesElements = $doc->documentElement->getElementsByTagName('Signature');
        $Signature = $doc->documentElement->removeChild($SignaturesElements->item($SignaturesElements->length-1));
        $SignedInfo = $Signature->getElementsByTagName('SignedInfo')->item(0);
        $SignedInfo->setAttribute('xmlns', $Signature->getAttribute('xmlns'));
        $signed_info = $doc->saveHTML($SignedInfo);
        $signature = $Signature->getElementsByTagName('SignatureValue')->item(0)->nodeValue;
        $pub_key = $Signature->getElementsByTagName('X509Certificate')->item(0)->nodeValue;
        // verificar firma
        if (!$this->verify($signed_info, $signature, $pub_key)) {
            return false;
        }
        // verificar digest
        $digest_original = $Signature->getElementsByTagName('DigestValue')->item(0)->nodeValue;
        if ($tag) {
            $digest_calculado = base64_encode(sha1($doc->documentElement->getElementsByTagName($tag)->item(0)->C14N(), true));
        } else {
            $digest_calculado = base64_encode(sha1($doc->C14N(), true));
        }
        return $digest_original == $digest_calculado;
    }

    /**
     * Método que obtiene la clave asociada al módulo y exponente entregados.
     * @param modulus Módulo de la clave.
     * @param exponent Exponente de la clave.
     * @return string Entrega la clave asociada al módulo y exponente.
     */
    public static function getFromModulusExponent($modulus, $exponent): string
    {
        $rsa = new \phpseclib\Crypt\RSA();
        $modulus = new \phpseclib\Math\BigInteger(base64_decode($modulus), 256);
        $exponent = new \phpseclib\Math\BigInteger(base64_decode($exponent), 256);
        $rsa->loadKey(['n' => $modulus, 'e' => $exponent]);
        $rsa->setPublicKey();
        return $rsa->getPublicKey();
    }

}
