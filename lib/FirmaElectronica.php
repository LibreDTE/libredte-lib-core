<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sasco\LibreDTE;

/**
 * Clase para trabajar con firma electrónica, permite firmar y verificar firmas.
 * Provee los métodos: sign(), verify(), signXML() y verifyXML()
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2014-12-08
 */
class FirmaElectronica
{

    private $config; ///< Configuración de la firma electrónica
    private $certs; ///< Certificados digitales de la firma

    /**
     * Constructor para la clase: crea configuración y carga certificado digital
     *
     * Si se desea pasar una configuración específica para la firma electrónica
     * se debe hacer a través de un arreglo con los índices file y pass, donde
     * file es la ruta hacia el archivo .p12 que contiene tanto la clave privada
     * como la pública y pass es la contraseña para abrir dicho archivo.
     * Ejemplo:
     *
     * \code{.php}
     *   $firma_config = ['file'=>'/ruta/al/certificado.p12', 'pass'=>'contraseña'];
     *   $firma = new \sasco\LibreDTE\FirmaElectronica($firma_config);
     * \endcode
     *
     * @param config Configuración para la cllase, si no se especifica se trarará de determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-08
     */
    public function __construct($config = [])
    {
        // crear configuración
        if (!$config and class_exists('\sowerphp\core\Configure')) {
            $config = (array)\sowerphp\core\Configure::read('firma_electronica.default');
        }
        $this->config = array_merge([
            'file' => (defined('DIR_PROJECT') ? DIR_PROJECT.'/data/firma_electronica/' : '').'default.p12',
            'pass' => '',
            'wordwrap' => 64,
        ], $config);
        // cargar certificado digital
        if (file_exists($this->config['file'])) {
            $pkcs12 = file_get_contents($this->config['file']);
            if (openssl_pkcs12_read($pkcs12, $this->certs, $this->config['pass'])===false) {
                $this->error('Contraseña incorrecta para la firma electrónica '.basename($this->config['file']));
            }
        } else {
            $this->error('Archivo de la firma electrónica '.basename($this->config['file']).' no existe');
        }
    }

    /**
     * Método para generar un error usando una excepción de SowerPHP o terminar
     * el script si no se está usando el framework
     * @param msg Mensaje del error
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-06
     */
    private function error($msg)
    {
        if (class_exists('\sowerphp\core\Exception')) {
            throw new \sowerphp\core\Exception($msg);
        } else {
            die($msg);
        }
    }

    /**
     * Método que quita el inicio y fin de un certificado (clave pública)
     * @param cert Certificado que se desea limpiar
     * @return Contenido del certificado, clave pública, del certificado digital
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-08
     */
    private function cleanCert($cert)
    {
        return trim(str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'],
            '',
            $cert
        ));
    }

    /**
     * Método que agrega el inicio y fin de un certificado (clave pública)
     * @param cert Certificado que se desea normalizar
     * @return Certificado con el inicio y fin correspondiente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-08
     */
    private function normalizeCert($cert)
    {
        if (strpos($cert, '-----BEGIN CERTIFICATE-----')===false) {
            $body = trim($cert);
            $cert = '-----BEGIN CERTIFICATE-----'."\n";
            $cert .= $body."\n";
            $cert .= '-----END CERTIFICATE-----'."\n";
        }
        return $cert;
    }

    /**
     * Método que obtiene el módulo de la clave privada
     * @return Módulo en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-07
     */
    private function getModulus()
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->certs['pkey']));
        return wordwrap(base64_encode($details['rsa']['n']), $this->config['wordwrap'], "\n", true);
    }

    /**
     * Método que obtiene el exponente público de la clave privada
     * @return Exponente público en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-06
     */
    private function getExponent()
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->certs['pkey']));
        return wordwrap(base64_encode($details['rsa']['e']), $this->config['wordwrap'], "\n", true);
    }

    /**
     * Método para realizar la firma de datos
     * @param data Datos que se desean firmar
     * @param signature_alg Algoritmo que se utilizará para firmar (por defect SHA1)
     * @return Firma digital de los datos en base64 o =false si no se pudo firmar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-08
     */
    public function sign($data, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        $signature = null;
        if (openssl_sign($data, $signature, $this->certs['pkey'], $signature_alg)==false)
            return false;
        return base64_encode($signature);
    }

    /**
     * Método que verifica la firma digital de datos
     * @param data Datos que se desean verificar
     * @param signature Firma digital de los datos en base64
     * @param pub_key Certificado digital, clave pública, de la firma
     * @param signature_alg Algoritmo que se usó para firmar (por defect SHA1)
     * @return =true si la firma está ok, =false si está mal o no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2014-12-08
     */
    public function verify($data, $signature, $pub_key = null, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        if ($pub_key === null)
            $pub_key = $this->certs['cert'];
        $pub_key = $this->normalizeCert($pub_key);
        return openssl_verify($data, base64_decode($signature), $pub_key, $signature_alg) == 1 ? true : false;
    }

    /**
     * Método que firma un XML utilizando RSA y SHA1
     *
     * Referencia: http://www.di-mgt.com.au/xmldsig2.html
     *
     * @param xml Datos XML que se desean firmar
     * @param reference Referencia a la que hace la firma
     * @return XML firmado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-05
     */
    public function signXML($xml, $reference = '')
    {
        $doc = new \DomDocument();
        $doc->loadXML($xml);
        $dom = $doc->documentElement;
        // crear nodo para la firma
        $Signature = $doc->importNode((new XML())->generate([
            'Signature' => [
                '@attributes' => [
                    'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
                ],
                'SignedInfo' => [
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
                                    'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
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
        ])->documentElement, true);
        // calcular DigestValue y SignatureValue
        $digest = base64_encode(sha1($dom->C14N(), true));
        $Signature->getElementsByTagName('DigestValue')[0]->nodeValue = $digest;
        $SignedInfo = $Signature->getElementsByTagName('SignedInfo')[0];
        $SignedInfo->setAttribute('xmlns', $Signature->getAttribute('xmlns'));
        $signature = wordwrap($this->sign($doc->saveHTML($SignedInfo)), $this->config['wordwrap'], "\n", true);
        // reemplazar valores en la firma de
        $Signature->getElementsByTagName('SignatureValue')[0]->nodeValue = $signature;
        $Signature->getElementsByTagName('Modulus')[0]->nodeValue = $this->getModulus();
        $Signature->getElementsByTagName('Exponent')[0]->nodeValue = $this->getExponent();
        $Signature->getElementsByTagName('X509Certificate')[0]->nodeValue = $this->cleanCert($this->certs['cert']);
        // agregar y entregar firma
        $dom->appendChild($Signature);
        return $doc->saveXML();
    }

    /**
     * Método que verifica la validez de la firma de un XML utilizando RSA y SHA1
     * @param xml_data Archivo XML que se desea validar
     * @return =true si la firma del documento XML es válida o =false si no lo es
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-07-29
     */
    public function verifyXML($xml_data)
    {
        $doc = new \DomDocument();
        $doc->loadXML($xml_data);
        $dom = $doc->documentElement;
        // preparar datos que se verificarán
        $SignaturesElements = $dom->getElementsByTagName('Signature');
        $Signature = $dom->removeChild($SignaturesElements->item($SignaturesElements->length-1));
        $SignedInfo = $Signature->getElementsByTagName('SignedInfo')[0];
        $SignedInfo->setAttribute('xmlns', $Signature->getAttribute('xmlns'));
        $signed_info = $doc->saveHTML($SignedInfo);
        $signature = $Signature->getElementsByTagName('SignatureValue')[0]->nodeValue;
        $pub_key = $Signature->getElementsByTagName('X509Certificate')[0]->nodeValue;
        // verificar firma
        if (!$this->verify($signed_info, $signature, $pub_key))
            return false;
        // verificar digest
        $digest_original = $Signature->getElementsByTagName('DigestValue')[0]->nodeValue;
        $digest_calculado = base64_encode(sha1($dom->C14N(), true));
        if ($digest_original != $digest_calculado)
            return false;
        return true;
    }

}
