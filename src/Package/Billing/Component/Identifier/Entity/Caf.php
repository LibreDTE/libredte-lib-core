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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Entity;

use DateTime;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;

/**
 * Entidad que representa un Código de Autorización de Folios (CAF).
 *
 * Un CAF es un archivo XML que contiene los folios autorizados por el Servicio
 * de Impuestos Internos (SII) de Chile para la emisión de Documentos
 * Tributarios Electrónicos (DTE).
 */
class Caf implements CafInterface
{
    /**
     * Ambiente de certificación del SII.
     *
     * Este valor se utiliza para identificar que el CAF pertenece al ambiente
     * de pruebas o certificación.
     */
    private const IDK_CERTIFICACION = 100;

    /**
     * Ambiente de producción del SII.
     *
     * Este valor se utiliza para identificar que el CAF pertenece al ambiente
     * de producción.
     */
    private const IDK_PRODUCCION = 300;

    /**
     * Mapa de ambientes disponibles para el CAF.
     *
     * Asocia los valores de los ambientes con las configuraciones
     * correspondientes de conexión al SII (certificación o producción).
     *
     * @var array<int, SiiAmbiente>
     */
    private const AMBIENTES = [
        self::IDK_CERTIFICACION => SiiAmbiente::CERTIFICACION,
        self::IDK_PRODUCCION => SiiAmbiente::PRODUCCION,
    ];

    /**
     * Documento XML del CAF.
     *
     * Este objeto representa el XML cargado del CAF, utilizado para
     * interactuar con el contenido y extraer los datos necesarios.
     *
     * @var XmlDocumentInterface
     */
    private XmlDocumentInterface $xmlDocument;

    /**
     * Constructor del CAF.
     *
     * @param string|XmlDocumentInterface $xml Documento XML del CAF.
     */
    public function __construct(string|XmlDocumentInterface $xml)
    {
        $this->loadXml($xml);
    }

    /**
     * Carga un documento XML de un CAF en la instancia de la entidad Caf.
     *
     * @param string|XmlDocumentInterface $xml Documento XML del CAF.
     * @return static
     */
    private function loadXml(string|XmlDocumentInterface $xml): static
    {
        if (is_string($xml)) {
            $this->xmlDocument = new XmlDocument();
            $this->xmlDocument->loadXml($xml);
        } else {
            $this->xmlDocument = $xml;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocument(): XmlDocumentInterface
    {
        return $this->xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function getXml(): string
    {
        return $this->xmlDocument->saveXml();
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return sprintf(
            'CAF%dD%dH%d',
            $this->getTipoDocumento(),
            $this->getFolioDesde(),
            $this->getFolioHasta()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getEmisor(): array
    {
        return [
            'rut' => $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RE'),
            'razon_social' => $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RS'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTipoDocumento(): int
    {
        return (int) $this->xmlDocument->query('//AUTORIZACION/CAF/DA/TD');
    }

    /**
     * {@inheritDoc}
     */
    public function getFolioDesde(): int
    {
        return (int) $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RNG/D');
    }

    /**
     * {@inheritDoc}
     */
    public function getFolioHasta(): int
    {
        return (int) $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RNG/H');
    }

    /**
     * {@inheritDoc}
     */
    public function getCantidadFolios(): int
    {
        $desde = $this->getFolioDesde();
        $hasta = $this->getFolioHasta();

        return $hasta - $desde + 1;
    }

    /**
     * {@inheritDoc}
     */
    public function enRango(int $folio): bool
    {
        return $folio >= $this->getFolioDesde() && $folio <= $this->getFolioHasta();
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaAutorizacion(): string
    {
        return $this->xmlDocument->query('//AUTORIZACION/CAF/DA/FA');
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function vence(): bool
    {
        $vencen = [33, 43, 46, 56, 61];

        return in_array($this->getTipoDocumento(), $vencen);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdk(): int
    {
        return (int) $this->xmlDocument->query('//AUTORIZACION/CAF/DA/IDK');
    }

    /**
     * {@inheritDoc}
     */
    public function getAmbiente(): ?SiiAmbiente
    {
        $idk = $this->getIDK();

        return $idk === CafFaker::IDK ? null : self::AMBIENTES[$idk];
    }

    /**
     * {@inheritDoc}
     */
    public function getCertificacion(): ?int
    {
        return $this->getAmbiente()?->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getAutorizacion(): array
    {
        return $this->xmlDocument->query('//AUTORIZACION/CAF');
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicKey(): string
    {
        $publicKey = $this->xmlDocument->query('//AUTORIZACION/RSAPUBK');

        // Restaurar el formato PEM correcto con saltos de línea.
        if (
            !str_contains($publicKey, "\n")
            && str_contains($publicKey, '-----BEGIN PUBLIC KEY-----')
        ) {
            // Extraer la parte codificada en base64 (entre los headers).
            $start = strpos($publicKey, '-----BEGIN PUBLIC KEY-----') + 26;
            $end = strpos($publicKey, '-----END PUBLIC KEY-----');
            $base64Content = substr($publicKey, $start, $end - $start);

            // Limpiar espacios en blanco y caracteres extra.
            $base64Content = trim($base64Content);

            // Dividir en líneas de 64 caracteres.
            $chunks = str_split($base64Content, 64);
            $formattedContent = implode("\n", $chunks);

            // Reconstruir la clave con formato PEM correcto.
            $publicKey = "-----BEGIN PUBLIC KEY-----\n"
                . $formattedContent
                . "\n-----END PUBLIC KEY-----"
            ;
        }

        return $publicKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrivateKey(): string
    {
        $privateKey = $this->xmlDocument->query('//AUTORIZACION/RSASK');

        // Restaurar el formato PEM correcto con saltos de línea.
        if (
            !str_contains($privateKey, "\n")
            && str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')
        ) {
            // Extraer la parte codificada en base64 (entre los headers).
            $start = strpos($privateKey, '-----BEGIN PRIVATE KEY-----') + 27;
            $end = strpos($privateKey, '-----END PRIVATE KEY-----');
            $base64Content = substr($privateKey, $start, $end - $start);

            // Limpiar espacios en blanco y caracteres extra.
            $base64Content = trim($base64Content);

            // Dividir en líneas de 64 caracteres.
            $chunks = str_split($base64Content, 64);
            $formattedContent = implode("\n", $chunks);

            // Reconstruir la clave con formato PEM correcto.
            $privateKey = "-----BEGIN PRIVATE KEY-----\n"
                . $formattedContent
                . "\n-----END PRIVATE KEY-----"
            ;
        }

        return $privateKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getFirma(): string
    {
        return $this->xmlDocument->query('//AUTORIZACION/CAF/FRMA');
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'emisor' => $this->getEmisor(),
            'tipoDocumento' => $this->getTipoDocumento(),
            'folioDesde' => $this->getFolioDesde(),
            'folioHasta' => $this->getFolioHasta(),
            'cantidadFolios' => $this->getCantidadFolios(),
            'fechaAutorizacion' => $this->getFechaAutorizacion(),
            'fechaVencimiento' => $this->getFechaVencimiento(),
            'mesesAutorizacion' => $this->getMesesAutorizacion(),
            'vigente' => $this->vigente(),
            'vence' => $this->vence(),
            'idk' => $this->getIdk(),
            'ambiente' => $this->getAmbiente(),
            'certificacion' => $this->getCertificacion(),
            'publicKey' => $this->getPublicKey(),
            'privateKey' => $this->getPrivateKey(),
            'xml' => $this->getXmlDocument()->saveXml(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        $array['xml'] = base64_encode($array['xml']);

        return $array;
    }
}
