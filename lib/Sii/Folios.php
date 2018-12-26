<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
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

namespace sasco\LibreDTE\Sii;

/**
 * Clase para realizar operaciones con lo Folios autorizados por el SII
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2018-03-20
 */
class Folios
{

    private $xml; ///< Objeto XML que representa el CAF

    /**
     * Constructor de la clase
     * @param xml Datos XML del código de autorización de folios (CAF)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-11-21
     */
    public function __construct($xml)
    {
        $this->xml = new \sasco\LibreDTE\XML();
        $this->xml->loadXML(utf8_encode($xml));
        if (!$this->check()) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::FOLIOS_ERROR_CHECK,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::FOLIOS_ERROR_CHECK)
            );
            $this->xml = null;
        }
    }

    /**
     * Método que verifica el código de autorización de folios
     * @return bool =true si está ok el XML cargado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-03-20
     */
    public function check()
    {
        // validar firma del SII sobre los folios
        $firma = $this->getFirma();
        $idk = $this->getIDK();
        if ($firma === false || $idk === false) {
            return false;
        }
        $pub_key = \sasco\LibreDTE\Sii::cert($idk);
        if ($pub_key === false || openssl_verify($this->xml->getFlattened('/AUTORIZACION/CAF/DA'), base64_decode($firma), $pub_key)!==1) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::FOLIOS_ERROR_FIRMA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::FOLIOS_ERROR_FIRMA)
            );
            return false;
        }
        // validar clave privada y pública proporcionada por el SII
        $private_key = $this->getPrivateKey();
        if (!$private_key) {
            return false;
        }
        $plain = md5(date('U'));
        if (!openssl_private_encrypt($plain, $crypt, $private_key)) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::FOLIOS_ERROR_ENCRIPTAR,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::FOLIOS_ERROR_ENCRIPTAR)
            );
            return false;
        }
        $public_key = $this->getPublicKey();
        if (!$public_key) {
            return false;
        }
        if (!openssl_public_decrypt($crypt, $plain_firmado, $public_key)) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::FOLIOS_ERROR_DESENCRIPTAR,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::FOLIOS_ERROR_DESENCRIPTAR)
            );
            return false;
        }
        return $plain === $plain_firmado;
    }

    /**
     * Método que entrega el nodo CAF
     * @return DomElement
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    public function getCaf()
    {
        if (!$this->xml) {
            return false;
        }
        $CAF = $this->xml->getElementsByTagName('CAF')->item(0);
        return $CAF ? $CAF : false;
    }

    /**
     * Método que entrega el RUT de a quién se está autorizando el CAF
     * @return string RUT del emisor del CAF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    public function getEmisor()
    {
        if (!$this->xml) {
            return false;
        }
        $RE = $this->xml->getElementsByTagName('RE')->item(0);
        return $RE ? $RE->nodeValue : false;
    }

    /**
     * Método que entrega el primer folio autorizado en el CAF
     * @return int Número del primer folio
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    public function getDesde()
    {
        if (!$this->xml) {
            return false;
        }
        $D = $this->xml->getElementsByTagName('D')->item(0);
        return $D ? (int)$D->nodeValue : false;
    }

    /**
     * Método que entrega el últimmo folio autorizado en el CAF
     * @return int Número del último folio
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    public function getHasta()
    {
        if (!$this->xml) {
            return false;
        }
        $H = $this->xml->getElementsByTagName('H')->item(0);
        return $H ? (int)$H->nodeValue : false;
    }

    /**
     * Método que entrega la firma del SII sobre el nodo DA
     * @return string Firma en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    private function getFirma()
    {
        if (!$this->xml) {
            return false;
        }
        $FRMA = $this->xml->getElementsByTagName('FRMA')->item(0);
        return $FRMA ? $FRMA->nodeValue : false;
    }

    /**
     * Método que entrega el IDK (serial number) de la clave pública del SII
     * utilizada para firmar el CAF
     * @return int Serial number
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    private function getIDK()
    {
        if (!$this->xml) {
            return false;
        }
        $IDK = $this->xml->getElementsByTagName('IDK')->item(0);
        return $IDK ? (int)$IDK->nodeValue : false;
    }

    /**
     * Método que entrega la clave privada proporcionada por el SII para el CAF
     * @return string Clave privada en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    public function getPrivateKey()
    {
        if (!$this->xml) {
            return false;
        }
        $RSASK = $this->xml->getElementsByTagName('RSASK')->item(0);
        return $RSASK ? $RSASK->nodeValue : false;
    }

    /**
     * Método que entrega la clave pública proporcionada por el SII para el CAF
     * @return string Clave pública en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    public function getPublicKey()
    {
        if (!$this->xml) {
            return false;
        }
        $RSAPUBK = $this->xml->getElementsByTagName('RSAPUBK')->item(0);
        return $RSAPUBK ? $RSAPUBK->nodeValue : false;
    }

    /**
     * Método que entrega el tipo de DTE para el cual se emitió el CAF
     * @return int Código de tipo de DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    public function getTipo()
    {
        if (!$this->xml) {
            return false;
        }
        $TD = $this->xml->getElementsByTagName('TD')->item(0);
        return $TD ? (int)$TD->nodeValue : false;
    }

    /**
     * Método que entrega la fecha de autorización con la que se emitió el CAF
     * @return string Fecha de autorización del CAF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-07-19
     */
    public function getFechaAutorizacion()
    {
        if (!$this->xml) {
            return false;
        }
        $FA = $this->xml->getElementsByTagName('FA')->item(0);
        return $FA ? $FA->nodeValue : false;
    }

    /**
     * Método que indica si el CAF es de certificación o no
     * @return bool =true si los folios son del ambiente de certificación, =null si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-30
     */
    public function getCertificacion()
    {
        $idk = $this->getIDK();
        return $idk ?  $idk === 100 : null;
    }

    /**
     * Método que indica si el CAF está o no vigente
     * @return bool =true si el CAF está vigente, =false si no está vigente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-12-21
     */
    public function vigente()
    {
        if (!in_array($this->getTipo(), [33, 43, 46, 56, 61])) {
            return true;
        }
        $fecha_autorizacion = $this->getFechaAutorizacion();
        $hoy = date('Y-m-d');
        if ($fecha_autorizacion < '2018-07-01' and $hoy > '2018-12-31') {
            return false;
        }
        $vigencia = $fecha_autorizacion >= '2018-07-01' ? 6 : 18;
        $d1 = new \DateTime($fecha_autorizacion);
        $d2 = new \DateTime($hoy);
        $meses = $d1->diff($d2)->m + ($d1->diff($d2)->y*12);
        return $meses <= $vigencia;
    }

    /**
     * Método que entrega el XML completo del archivo CAF
     * @return string XML del CAF
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-24
     */
    public function saveXML()
    {
        return $this->xml->saveXML();
    }

}
