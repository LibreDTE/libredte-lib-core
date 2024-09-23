<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Sii\Dte\AutorizacionFolio;

use DateTime;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Signature\SignatureException;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Sii\Contribuyente\Contribuyente;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;
use libredte\lib\Core\Sii\HttpClient\ConnectionConfig;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlConverter;

/**
 * Clase para representar un Código de Autorización de Folios (CAF).
 *
 * Un CAF es un archivo XML que contiene los folios autorizados por el Servicio
 * de Impuestos Internos (SII) de Chile para la emisión de Documentos
 * Tributarios Electrónicos (DTE).
 */
class Caf
{

    /**
     * Ambiente de certificación del SII.
     *
     * Este valor se utiliza para identificar que el CAF pertenece al ambiente
     * de pruebas o certificación.
     */
    private const CERTIFICACION = 100;

    /**
     * Ambiente de producción del SII.
     *
     * Este valor se utiliza para identificar que el CAF pertenece al ambiente
     * de producción.
     */
    private const PRODUCCION = 300;

    /**
     * Mapa de ambientes disponibles para el CAF.
     *
     * Asocia los valores de los ambientes con las configuraciones
     * correspondientes de conexión al SII (certificación o producción).
     *
     * @var array<int, int>
     */
    private const AMBIENTES = [
        self::CERTIFICACION => ConnectionConfig::CERTIFICACION,
        self::PRODUCCION => ConnectionConfig::PRODUCCION,
    ];

    /**
     * Datos del CAF en formato de arreglo.
     *
     * Este arreglo contiene la estructura completa del XML del CAF convertido
     * a un formato de arreglo asociativo.
     *
     * @var array
     */
    private array $data;

    /**
     * Documento XML del CAF.
     *
     * Este objeto representa el XML cargado del CAF, utilizado para
     * interactuar con el contenido y extraer los datos necesarios.
     *
     * @var XmlDocument
     */
    private XmlDocument $xmlDocument;

    /**
     * Contribuyente emisor del CAF.
     *
     * Este objeto representa al contribuyente que está autorizado para
     * utilizar los folios del CAF.
     *
     * @var Contribuyente
     */
    private Contribuyente $emisor;

    /**
     * Tipo de documento tributario del CAF.
     *
     * Este objeto representa el tipo de documento tributario (DTE) asociado al
     * CAF, como facturas, boletas, notas de crédito, etc.
     *
     * @var DocumentoTipo
     */
    private DocumentoTipo $tipoDocumento;

    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    protected DataProviderInterface $dataProvider;

    /**
     * Constructor de la clase.
     *
     * @param DataProviderInterface|null $dataProvider Proveedor de datos.
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Carga un string XML de un CAF en la clase.
     *
     * @param string $xml Contenido del archivo XML del CAF.
     */
    public function loadXML(string $xml): void
    {
        $this->xmlDocument = new XmlDocument();
        $this->xmlDocument->loadXML($xml);
    }

    /**
     * Retorna el XML cargado como string.
     *
     * @return string Contenido del XML.
     */
    public function getXML(): string
    {
        return $this->xmlDocument->saveXML();
    }

    /**
     * Método que valida el código de autorización de folios (CAF).
     *
     * Valida la firma y las claves públicas y privadas asociadas al CAF.
     */
    public function validate(): void
    {
        // Verificar firma del CAF con la clave pública del SII.
        $public_key_sii = $this->getCertificateSII();
        if ($public_key_sii !== null) {
            $firma = $this->getFirma();
            $signed_da = $this->xmlDocument->C14NWithIsoEncodingFlattened('/AUTORIZACION/CAF/DA');
            if (openssl_verify($signed_da, base64_decode($firma), $public_key_sii) !== 1) {
                throw new CafException(sprintf(
                    'La firma del CAF %s no es válida (no está autorizado por el SII).',
                    $this->getID()
                ));
            }
        }

        // Verificar que la clave pública y privada sean válidas. Esto se hace
        // encriptando un texto random y desencriptándolo.
        $private_key = $this->getPrivateKey();
        $test_plain = md5(date('U'));
        if (!openssl_private_encrypt($test_plain, $test_encrypted, $private_key)) {
            throw new CafException(sprintf(
                'El CAF %s no pasó la validación de su clave privada (posible archivo CAF corrupto).',
                $this->getID()
            ));
        }
        $public_key = $this->getPublicKey();
        if (!openssl_public_decrypt($test_encrypted, $test_decrypted, $public_key)) {
            throw new CafException(sprintf(
                'El CAF %s no pasó la validación de su clave pública (posible archivo CAF corrupto).',
                $this->getID()
            ));
        }
        if ($test_plain !== $test_decrypted) {
            throw new CafException(sprintf(
                'El CAF %s no logró encriptar y desencriptar correctamente un texto de prueba (posible archivo CAF corrupto).',
                $this->getID()
            ));
        }
    }

    /**
     * Entrega un ID para el CAF generado a partir de los datos del mismo.
     *
     * @return string
     */
    public function getID(): string
    {
        return sprintf(
            'CAF%dD%dH%d',
            $this->getTipoDocumento()->getCodigo(),
            $this->getFolioDesde(),
            $this->getFolioHasta()
        );
    }

    /**
     * Obtiene el contribuyente emisor del CAF.
     *
     * @return Contribuyente Instancia de Contribuyente que representa al emisor.
     */
    public function getEmisor(): Contribuyente
    {
        if (!isset($this->emisor)) {
            $data = $this->getData();

            $this->emisor = new Contribuyente(
                rut: $data['AUTORIZACION']['CAF']['DA']['RE'],
                razon_social: $data['AUTORIZACION']['CAF']['DA']['RS'],
                dataProvider: $this->dataProvider
            );
        }

        return $this->emisor;
    }

    /**
     * Obtiene el tipo de documento tributario del CAF.
     *
     * @return DocumentoTipo Instancia de DocumentoTipo.
     */
    public function getTipoDocumento(): DocumentoTipo
    {
        if (!isset($this->tipoDocumento)) {
            $data = $this->getData();
            $this->tipoDocumento = new DocumentoTipo(
                codigo: (int) $data['AUTORIZACION']['CAF']['DA']['TD'],
                dataProvider: $this->dataProvider
            );
        }

        return $this->tipoDocumento;
    }

    /**
     * Obtiene el folio inicial autorizado en el CAF.
     *
     * @return int Folio inicial.
     */
    public function getFolioDesde(): int
    {
        $data = $this->getData();

        return (int) $data['AUTORIZACION']['CAF']['DA']['RNG']['D'];
    }

    /**
     * Obtiene el folio final autorizado en el CAF.
     *
     * @return int Folio final.
     */
    public function getFolioHasta(): int
    {
        $data = $this->getData();

        return (int) $data['AUTORIZACION']['CAF']['DA']['RNG']['H'];
    }

    /**
     * Obtiene la cantidad de folios autorizados en el CAF.
     *
     * @return int Cantidad de folios.
     */
    public function getCantidadFolios(): int
    {
        $desde = $this->getFolioDesde();
        $hasta = $this->getFolioHasta();

        return $hasta - $desde + 1;
    }

    /**
     * Determina si el folio pasado como argumento está o no dentro del rango
     * del CAF.
     *
     * NOTE: Esta validación NO verifica si el folio ya fue usado, solo si está
     * dentro del rango de folios disponibles en el CAF.
     *
     * @param integer $folio
     * @return boolean
     */
    public function enRango(int $folio): bool
    {
        return $folio >= $this->getFolioDesde() && $folio <= $this->getFolioHasta();
    }

    /**
     * Obtiene la fecha de autorización del CAF.
     *
     * @return string Fecha de autorización en formato YYYY-MM-DD.
     */
    public function getFechaAutorizacion(): string
    {
        $data = $this->getData();

        return $data['AUTORIZACION']['CAF']['DA']['FA'];
    }

    /**
     * Obtiene la fecha de vencimiento del CAF.
     *
     * @return string|null Fecha de vencimiento en formato YYYY-MM-DD o `null`
     * si no aplica.
     */
    public function getFechaVencimiento(): ?string
    {
        if (!$this->vence()) {
            return null;
        }

        $fecha_autorizacion = $this->getFechaAutorizacion();
        if (!$fecha_autorizacion) {
            throw new CafException(sprintf(
                'No fue posible obtener la fecha de autorización del CAF %s.',
                $this->getID()
            ));
        }

        // Los folios vencen en 6 meses (6 * 30 días).
        return date('Y-m-d', strtotime($fecha_autorizacion. ' + 180 days'));
    }

    /**
     * Entrega la cantidad de meses que han pasado desde la solicitud del CAF.
     *
     * @return float Cantidad de meses transcurridos.
     */
    public function getMesesAutorizacion(): float
    {
        $d1 = new DateTime($this->getFechaAutorizacion());
        $d2 = new DateTime(date('Y-m-d'));
        $diff = $d1->diff($d2);
        $meses = $diff->m + ($diff->y * 12);

        if ($diff->d) {
            $meses += round($diff->d / 30, 2);
        }

        return $meses;
    }

    /**
     * Indica si el CAF está o no vigente.
     *
     * @param string $timestamp Marca de tiempo para consultar vigencia en un
     * momento específico. Si no se indica, por defecto es la fecha y hora
     * actual.
     * @return bool `true` si el CAF está vigente, `false` si no está vigente.
     */
    public function vigente(?string $timestamp = null): bool
    {
        if (!$this->vence()) {
            return true;
        }

        if ($timestamp === null) {
            $timestamp = date('Y-m-d\TH:i:s');
        }

        if (!isset($timestamp[10])) {
            $timestamp .= 'T00:00:00';
        }

        return $timestamp < ($this->getFechaVencimiento() . 'T00:00:00');
    }

    /**
     * Indica si el CAF de este tipo de documento vence o no.
     *
     * @return bool `true` si los folios de este tipo de documento vencen,
     * `false` si no vencen.
     */
    public function vence(): bool
    {
        $vencen = [33, 43, 46, 56, 61];

        return in_array($this->getTipoDocumento()->getCodigo(), $vencen);
    }

    /**
     * Entrega el ambiente del SII asociado al CAF.
     *
     * El resultado puede ser:
     *
     *   - ConnectionConfig::CERTIFICACION el ambiente del CAF es certificación.
     *   - ConnectionConfig::PRODUCCION el ambiente del CAF es producción.
     *   - `null`: no hay ambiente, pues el Caf es falso y tiene IDK CafFaker::IDK
     *
     * @return int|null
     */
    public function getAmbiente(): ?int
    {
        $idk = $this->getIDK();

        return $idk === CafFaker::IDK ? null : self::AMBIENTES[$idk];
    }

    /**
     * Indica si el CAF es de certificación o producción.
     *
     * El resultado puede ser:
     *
     *   - ConnectionConfig::CERTIFICACION es CAF de certificación.
     *   - ConnectionConfig::PRODUCCION es CAF de producción.
     *   - `null`: indicando que el Caf es falso y tiene IDK CafFaker::IDK
     *
     * @return int|null
     */
    public function getCertificacion(): ?int
    {
        return $this->getAmbiente();
    }

    /**
     * Entrega los datos del código de autorización de folios (CAF).
     *
     * @return array
     */
    public function getAutorizacion(): array
    {
        $data = $this->getData();

        return $data['AUTORIZACION']['CAF'];
    }

    /**
     * Timbra los datos con la clave privada del CAF.
     *
     * En estricto rigor, esto es una firma electrónica. Por lo que se usa
     * directamente el método SignatureGenerator::sign().
     *
     * @param string $data String con el nodo DD a timbrar.
     * @return string Timbre (firma) codificado en base64.
     */
    public function timbrar(string $data): string
    {
        $privateKey = $this->getPrivateKey();
        $signatureAlgorithm = OPENSSL_ALGO_SHA1;

        try {
            return SignatureGenerator::sign(
                $data,
                $privateKey,
                $signatureAlgorithm
            );
        } catch (SignatureException) {
            throw new CafException('No fue posible timbrar los datos.');
        }
    }

    /**
     * Obtiene la clave privada proporcionada en el CAF.
     *
     * @return string Clave privada.
     */
    public function getPrivateKey(): string
    {
        $data = $this->getData();

        return $data['AUTORIZACION']['RSASK'];
    }

    /**
     * Obtiene la clave pública proporcionada en el CAF.
     *
     * @return string Clave pública.
     */
    public function getPublicKey(): string
    {
        $data = $this->getData();

        return $data['AUTORIZACION']['RSAPUBK'];
    }

    /**
     * Obtiene el identificador del certificado utilizado en el CAF.
     *
     * @return int ID del certificado.
     */
    private function getIdk(): int
    {
        $data = $this->getData();

        return (int) $data['AUTORIZACION']['CAF']['DA']['IDK'];
    }

    /**
     * Obtiene la firma del SII sobre el nodo DA del CAF.
     *
     * @return string Firma en base64.
     */
    private function getFirma(): string
    {
        $data = $this->getData();

        return $data['AUTORIZACION']['CAF']['FRMA']['@value'];
    }

    /**
     * Obtiene los datos del CAF en formato de arreglo.
     *
     * @return array Datos del CAF.
     */
    private function getData(): array
    {
        if (!isset($this->data)) {
            $this->data = XmlConverter::xmlToArray($this->xmlDocument);
        }

        return $this->data;
    }

    /**
     * Método para obtener la clave pública del CAF a partir del módulo y el
     * exponente.
     *
     * @return string|null Contenido de la clave pública o `null` si es un CAF
     * falso.
     */
    // private function getPublicKeyFromModulusExponent(): ?string
    // {
    //     $idk = $this->getIDK();
    //     if ($idk === CafFaker::IDK) {
    //         return null;
    //     }

    //     $data = $this->getData();

    //     $modulus = $data['AUTORIZACION']['CAF']['DA']['RSAPK']['M'];
    //     $exponent = $data['AUTORIZACION']['CAF']['DA']['RSAPK']['E'];

    //     $public_key = CertificateUtils::generatePublicKeyFromModulusExponent(
    //         $modulus,
    //         $exponent
    //     );

    //     return $public_key;
    // }

    /**
     * Método para obtener el certificado X.509 del SII para la validación del
     * XML del CAF.
     *
     * @return string|null Contenido del certificado o `null` si es un CAF
     * falso.
     * @throws CafException Si no es posible obtener el certificado del SII.
     */
    private function getCertificateSII(): ?string
    {
        $idk = $this->getIDK();
        if ($idk === CafFaker::IDK) {
            return null;
        }

        $filename = $idk . '.cer';
        $filepath = PathManager::getCertificatesPath($filename);

        if ($filepath === null) {
            throw new CafException(sprintf(
                'No fue posible obtener el certificado del SII %s para validar el CAF.',
                $filename
            ));
        }

        return file_get_contents($filepath);
    }
}
