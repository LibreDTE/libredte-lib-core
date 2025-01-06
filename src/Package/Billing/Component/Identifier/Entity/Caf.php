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
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Entity\Xml as XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Integration\Entity\Ambiente;

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
     * @var array<int, Ambiente>
     */
    private const AMBIENTES = [
        self::IDK_CERTIFICACION => Ambiente::CERTIFICACION,
        self::IDK_PRODUCCION => Ambiente::PRODUCCION,
    ];

    /**
     * Documento XML del CAF.
     *
     * Este objeto representa el XML cargado del CAF, utilizado para
     * interactuar con el contenido y extraer los datos necesarios.
     *
     * @var XmlInterface
     */
    private XmlInterface $xmlDocument;

    /**
     * Constructor del CAF.
     *
     * @param string|XmlInterface $xml Documento XML del CAF.
     */
    public function __construct(string|XmlInterface $xml)
    {
        $this->loadXml($xml);
    }

    /**
     * Carga un documento XML de un CAF en la instancia de la entidad Caf.
     *
     * @param string|XmlInterface $xml Documento XML del CAF.
     * @return static
     */
    private function loadXml(string|XmlInterface $xml): static
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
     * {@inheritdoc}
     */
    public function getXmlDocument(): XmlInterface
    {
        return $this->xmlDocument;
    }

    /**
     * {@inheritdoc}
     */
    public function getXml(): string
    {
        return $this->xmlDocument->saveXml();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getEmisor(): array
    {
        return [
            'rut' => $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RE'),
            'razon_social' => $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RS'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTipoDocumento(): int
    {
        return (int) $this->xmlDocument->query('//AUTORIZACION/CAF/DA/TD');
    }

    /**
     * {@inheritdoc}
     */
    public function getFolioDesde(): int
    {
        return (int) $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RNG/D');
    }

    /**
     * {@inheritdoc}
     */
    public function getFolioHasta(): int
    {
        return (int) $this->xmlDocument->query('//AUTORIZACION/CAF/DA/RNG/H');
    }

    /**
     * {@inheritdoc}
     */
    public function getCantidadFolios(): int
    {
        $desde = $this->getFolioDesde();
        $hasta = $this->getFolioHasta();

        return $hasta - $desde + 1;
    }

    /**
     * {@inheritdoc}
     */
    public function enRango(int $folio): bool
    {
        return $folio >= $this->getFolioDesde() && $folio <= $this->getFolioHasta();
    }

    /**
     * {@inheritdoc}
     */
    public function getFechaAutorizacion(): string
    {
        return $this->xmlDocument->query('//AUTORIZACION/CAF/DA/FA');
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function vence(): bool
    {
        $vencen = [33, 43, 46, 56, 61];

        return in_array($this->getTipoDocumento(), $vencen);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdk(): int
    {
        return (int) $this->xmlDocument->query('//AUTORIZACION/CAF/DA/IDK');
    }

    /**
     * {@inheritdoc}
     */
    public function getAmbiente(): ?Ambiente
    {
        $idk = $this->getIDK();

        return $idk === CafFaker::IDK ? null : self::AMBIENTES[$idk];
    }

    /**
     * {@inheritdoc}
     */
    public function getCertificacion(): ?int
    {
        return $this->getAmbiente()->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getAutorizacion(): array
    {
        return $this->xmlDocument->query('//AUTORIZACION/CAF');
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicKey(): string
    {
        return $this->xmlDocument->query('//AUTORIZACION/RSAPUBK');
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey(): string
    {
        return $this->xmlDocument->query('//AUTORIZACION/RSASK');
    }

    /**
     * {@inheritdoc}
     */
    public function getFirma(): string
    {
        return $this->xmlDocument->query('//AUTORIZACION/CAF/FRMA');
    }
}
