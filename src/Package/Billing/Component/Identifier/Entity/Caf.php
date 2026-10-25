<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Entity;

use DateTime;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlQueryException;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;

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
            // El SII nunca emite CAFs en UTF-8; usa ISO-8859-1 y con frecuencia
            // omite (o declara mal) el atributo `encoding` en la cabecera. Se
            // fuerza la cabecera para que el parser decodifique los bytes
            // originales correctamente, sin alterar el contenido del documento.
            $xml = preg_replace(
                '/^\s*<\?xml[^?]*\?>/',
                '<?xml version="1.0" encoding="ISO-8859-1"?>',
                $xml,
                1
            );

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
    public function saveXml(): string
    {
        return $this->xmlDocument->setEncoding('ISO-8859-1')->saveXml();
    }

    /**
     * {@inheritDoc}
     */
    public function getXml(): string
    {
        return $this->xmlDocument->setEncoding('ISO-8859-1')->getXml();
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
        $RE = $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RE');

        if ($RE === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned issuer RUT.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/CAF/DA/RE'
            );
        }

        $RS = $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RS');

        if ($RS === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned issuer business name.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/CAF/DA/RS'
            );
        }

        return [
            'rut' => $RE,
            'razon_social' => $RS,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTipoDocumento(): int
    {
        $value = $this->xmlDocument->query('//AUTORIZACION/CAF/DA/TD');

        if ($value === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned document type.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/CAF/DA/TD'
            );
        }

        return (int) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getFolioDesde(): int
    {
        $value = $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RNG/D');

        if ($value === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned starting document number.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/CAF/DA/RNG/D'
            );
        }

        return (int) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getFolioHasta(): int
    {
        $value = $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RNG/H');

        if ($value === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned ending document number.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/CAF/DA/RNG/H'
            );
        }

        return (int) $value;
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
    public function isEnRango(int $folio): bool
    {
        return $folio >= $this->getFolioDesde() && $folio <= $this->getFolioHasta();
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaAutorizacion(): string
    {
        $value = $this->xmlDocument->query('//AUTORIZACION/CAF/DA/FA');

        if ($value === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned authorization date.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/CAF/DA/FA'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaVencimiento(): ?string
    {
        if (!$this->hasVencimiento()) {
            return null;
        }

        $fecha_autorizacion = $this->getFechaAutorizacion();
        if (!$fecha_autorizacion) {
            throw new CafException([
                'Could not obtain the CAF {id} authorization date.',
                'id' => $this->getID(),
            ]);
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
    public function isVigente(?string $timestamp = null): bool
    {
        if (!$this->hasVencimiento()) {
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
    public function hasVencimiento(): bool
    {
        $vencen = [33, 43, 46, 56, 61];

        return in_array($this->getTipoDocumento(), $vencen);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdk(): int
    {
        $value = $this->xmlDocument->query('//AUTORIZACION/CAF/DA/IDK');

        if ($value === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned IDK.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/CAF/DA/IDK'
            );
        }

        return (int) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvironment(): ?SiiEnvironment
    {
        $idk = $this->getIDK();

        return $idk === CafFaker::IDK ? null : SiiEnvironment::tryFromCafIdk($idk);
    }

    /**
     * {@inheritDoc}
     */
    public function getCertificacion(): ?int
    {
        return $this->getEnvironment()?->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getAutorizacion(): array
    {
        $value = $this->xmlDocument->query('//AUTORIZACION/CAF');

        if ($value === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned authorization.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/CAF'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicKey(): string
    {
        $publicKey = $this->xmlDocument->query('//AUTORIZACION/RSAPUBK');

        if ($publicKey === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned public key.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/RSAPUBK'
            );
        }

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

        if ($privateKey === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned private key.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/RSASK'
            );
        }

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
        $value = $this->xmlDocument->query('//AUTORIZACION/CAF/FRMA');

        if ($value === null) {
            throw new XmlQueryException(
                'The CAF XML does not include an assigned signature.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//AUTORIZACION/CAF/FRMA'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>&array{
     *     xml: string
     * }
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
            'vigente' => $this->isVigente(),
            'vence' => $this->hasVencimiento(),
            'idk' => $this->getIdk(),
            'ambiente' => $this->getEnvironment(),
            'certificacion' => $this->getCertificacion(),
            'publicKey' => $this->getPublicKey(),
            'privateKey' => $this->getPrivateKey(),
            'xml' => $this->saveXml(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        $array['xml'] = base64_encode($array['xml']);

        return $array;
    }
}
