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

namespace libredte\lib\Sii;

/**
 * Clase para realizar operaciones con lo Folios autorizados por el SII.
 */
class Folios
{

    private static $vencen = [33, 43, 46, 56, 61];

    private $xml; ///< Objeto XML que representa el CAF

    /**
     * Constructor de la clase.
     * @param xml Datos XML del código de autorización de folios (CAF).
     */
    public function __construct(string $xml)
    {
        $this->xml = new \libredte\lib\XML();
        $xml_utf8 = mb_convert_encoding($xml, 'UTF-8', 'ISO-8859-1');
        $this->xml->loadXML($xml_utf8);
        if (!$this->check()) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::FOLIOS_ERROR_CHECK,
                \libredte\lib\Estado::get(\libredte\lib\Estado::FOLIOS_ERROR_CHECK)
            );
            $this->xml = null;
        }
    }

    /**
     * Método que verifica el código de autorización de folios.
     * @return bool =true si está ok el XML cargado, =false si existe algún problema.
     */
    public function check(): bool
    {
        // validar firma del SII sobre los folios
        $firma = $this->getFirma();
        $idk = $this->getIDK();
        if ($firma === false || $idk === false) {
            return false;
        }
        $pub_key = \libredte\lib\Sii::cert($idk);
        if ($pub_key === false || openssl_verify($this->xml->getFlattened('/AUTORIZACION/CAF/DA'), base64_decode($firma), $pub_key) !== 1) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::FOLIOS_ERROR_FIRMA,
                \libredte\lib\Estado::get(\libredte\lib\Estado::FOLIOS_ERROR_FIRMA)
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
            \libredte\lib\Log::write(
                \libredte\lib\Estado::FOLIOS_ERROR_ENCRIPTAR,
                \libredte\lib\Estado::get(\libredte\lib\Estado::FOLIOS_ERROR_ENCRIPTAR)
            );
            return false;
        }
        $public_key = $this->getPublicKey();
        if (!$public_key) {
            return false;
        }
        if (!openssl_public_decrypt($crypt, $plain_firmado, $public_key)) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::FOLIOS_ERROR_DESENCRIPTAR,
                \libredte\lib\Estado::get(\libredte\lib\Estado::FOLIOS_ERROR_DESENCRIPTAR)
            );
            return false;
        }
        return $plain === $plain_firmado;
    }

    /**
     * Método que entrega el nodo CAF.
     * @return DomElement|false
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
     * Método que entrega el RUT de a quién se está autorizando el CAF.
     * @return string|false RUT del emisor del CAF.
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
     * Método que entrega el primer folio autorizado en el CAF.
     * @return int|false Número del primer folio.
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
     * Método que entrega el últimmo folio autorizado en el CAF.
     * @return int|false Número del último folio.
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
     * Método que entrega la cantidad de folios que vienen en el CAF.
     * @return int|false Cantidad total de folios del CAF.
     */
    public function getCantidad()
    {
        $desde = $this->getDesde();
        $hasta = $this->getHasta();
        if (!$desde || !$hasta) {
            return false;
        }
        return $hasta - $desde + 1;
    }

    /**
     * Método que entrega la firma del SII sobre el nodo DA.
     * @return string|false Firma en base64.
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
     * utilizada para firmar el CAF.
     * @return int|false Serial number.
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
     * Método que entrega la clave privada proporcionada por el SII para el CAF.
     * @return string|false Clave privada en base64.
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
     * Método que entrega la clave pública proporcionada por el SII para el CAF.
     * @return string|false Clave pública en base64.
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
     * Método que entrega el tipo de DTE para el cual se emitió el CAF.
     * @return int|false Código de tipo de DTE.
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
     * Método que entrega la fecha de autorización con la que se emitió el CAF.
     * @return string|false Fecha de autorización del CAF.
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
     * Método que entrega la fecha de vencimiento del CAF.
     *
     * Retorna la fecha de vencimiento calculada desde la fecha de autorización.
     * Devuelve null si el CAF no tiene vencimiento,
     * y false si no se puede determinar la fecha de autorización.
     *
     * @return string|false|null Fecha de vencimiento del CAF en formato 'Y-m-d', null si el CAF no vence, o false si la fecha de autorización no está disponible.
     */
    public function getFechaVencimiento()
    {
        if (!$this->vence()) {
            return null;
        }
        $fecha_autorizacion = $this->getFechaAutorizacion();
        if (!$fecha_autorizacion) {
            return false;
        }
        return date('Y-m-d', strtotime($fecha_autorizacion. ' + 180 days')); // 6 meses = 6 * 30 días
    }

    /**
     * Método que entrega la cantidad de meses que han pasado desde la solicitud del CAF.
     */
    public function getMesesAutorizacion(): int
    {
        $d1 = new \DateTime($this->getFechaAutorizacion());
        $d2 = new \DateTime(date('Y-m-d'));
        $diff = $d1->diff($d2);
        $meses = $diff->m + ($diff->y*12);
        if ($diff->d) {
            $meses += round($diff->d / 30, 2);
        }
        return $meses;
    }

    /**
     * Método que indica si el CAF está o no vigente.
     * @return bool =true si el CAF está vigente, =false si no está vigente.
     */
    public function vigente(): bool
    {
        if (!$this->vence()) {
            return true;
        }
        return date('Y-m-d') < $this->getFechaVencimiento();
    }

    /**
     * Método que indica si el CAF de este tipo de documento vence o no.
     * @return bool =true si los folios de este tipo vencen, =false si no vencen.
     */
    private function vence(): bool
    {
        return in_array($this->getTipo(), self::$vencen);
    }

    /**
     * Método que indica si el CAF es de certificación o no.
     * @return bool|null =true folios certificación, =false folios producción, =null no se pudo determinar.
     */
    public function getCertificacion()
    {
        $idk = $this->getIDK();
        if (!$idk) {
            return null;
        }
        return $idk === 100;
    }

    /**
     * Método que entrega el XML completo del archivo CAF.
     * @return string XML del CAF.
     */
    public function saveXML(): string
    {
        return $this->xml->saveXML();
    }

}
