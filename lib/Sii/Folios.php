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

namespace sasco\LibreDTE\Sii;

/**
 * Clase para realizar operaciones con lo Folios autorizados por el SII
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-11
 */
class Folios
{

    private $xml; ///< Objeto XML que representa el CAF

    /**
     * Constructor de la clase
     * @param xml Datos XML del código de autorización de folios (CAF)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-19
     */
    public function __construct($xml)
    {
        $this->xml = new \sasco\LibreDTE\XML();
        $this->xml->loadXML($xml);
        if (!$this->check())
            $this->xml = null;
    }

    /**
     * Método que verifica el código de autorización de folios
     * @return =true si está ok el XML cargado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-31
     */
    public function check()
    {
        // validar firma del SII sobre los folios
        $firma = base64_decode($this->getFirma());
        $pub_key = \sasco\LibreDTE\Sii::cert($this->getIDK());
        if (openssl_verify($this->xml->getFlattened('/AUTORIZACION/CAF/DA'), $firma, $pub_key)!==1)
            return false;
        // validar clave privada y pública proporcionada por el SII
        $plain = md5(date('U'));
        if (!openssl_private_encrypt($plain, $crypt, $this->getPrivateKey()))
            return false;
        if (!openssl_public_decrypt($crypt, $plain_firmado, $this->getPublicKey()))
            return false;
        return $plain === $plain_firmado;
    }

    /**
     * Método que entrega el nodo CAF
     * @return DomElement
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-31
     */
    public function getCaf()
    {
        if (!$this->xml)
            return false;
        return $this->xml->getElementsByTagName('CAF')->item(0);
    }

    /**
     * Método que entrega el primer folio autorizado en el CAF
     * @return Número del primer folio
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-19
     */
    public function getDesde()
    {
        if (!$this->xml)
            return false;
        return (int)$this->xml->getElementsByTagName('D')->item(0)->nodeValue;
    }

    /**
     * Método que entrega el últimmo folio autorizado en el CAF
     * @return Número del último folio
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-19
     */
    public function getHasta()
    {
        if (!$this->xml)
            return false;
        return (int)$this->xml->getElementsByTagName('H')->item(0)->nodeValue;
    }

    /**
     * Método que entrega la firma del SII sobre el nodo DA
     * @return Firma en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-30
     */
    private function getFirma()
    {
        if (!$this->xml)
            return false;
        return $this->xml->getElementsByTagName('FRMA')->item(0)->nodeValue;
    }

    /**
     * Método que entrega el IDK (serial number) de la clave pública del SII
     * utilizada para firmar el CAF
     * @return Serial number
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-31
     */
    private function getIDK()
    {
        if (!$this->xml)
            return false;
        return (int)$this->xml->getElementsByTagName('IDK')->item(0)->nodeValue;
    }

    /**
     * Método que entrega la clave privada proporcionada por el SII para el CAF
     * @return Clave privada en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-19
     */
    public function getPrivateKey()
    {
        if (!$this->xml)
            return false;
        return $this->xml->getElementsByTagName('RSASK')->item(0)->nodeValue;
    }

    /**
     * Método que entrega la clave pública proporcionada por el SII para el CAF
     * @return Clave pública en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-19
     */
    public function getPublicKey()
    {
        if (!$this->xml)
            return false;
        return $this->xml->getElementsByTagName('RSAPUBK')->item(0)->nodeValue;
    }

    /**
     * Método que indica si el CAF es de certificación o no
     * @return =true si los folios son del ambiente de certificación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-11
     */
    public function getCertificacion()
    {
        return $this->getIDK() === 100;
    }

}
