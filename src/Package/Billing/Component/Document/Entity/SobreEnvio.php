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

namespace libredte\lib\Core\Package\Billing\Component\Document\Entity;

use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SobreEnvioInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\AutorizacionDteInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;

/**
 * Entidad que representa un sobre de envío de documentos al SII o intercambio
 * entre contribuyentes.
 */
class SobreEnvio implements SobreEnvioInterface
{
    /**
     * Instancia del documento XML asociado a los datos.
     *
     * @var XmlInterface
     */
    protected readonly XmlInterface $xmlDocument;

    /**
     * Constructor del sobre de documentos tributarios para su envío.
     *
     * @param XmlInterface $xmlDocument
     * @return void
     */
    public function __construct(XmlInterface $xmlDocument)
    {
        $this->xmlDocument = $xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocument(): XmlInterface
    {
        return $this->xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function saveXml(): string
    {
        return $this->xmlDocument->saveXml();
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return 'LibreDTE_SetDoc';
    }

    /**
     * {@inheritDoc}
     */
    public function getRutEmisor(): string
    {
        return $this->xmlDocument->query('//SetDTE/Caratula/RutEmisor');
    }

    /**
     * {@inheritDoc}
     */
    public function getRunMandatario(): string
    {
        return $this->xmlDocument->query('//SetDTE/Caratula/RutEnvia');
    }

    /**
     * {@inheritDoc}
     */
    public function getRutReceptor(): string
    {
        return $this->xmlDocument->query('//SetDTE/Caratula/RutReceptor');
    }

    /**
     * {@inheritDoc}
     */
    public function getAutorizacionDte(): AutorizacionDteInterface
    {
        $fechaResolucion = $this->xmlDocument->query('//SetDTE/Caratula/FchResol');
        $numeroResolucion = (int) $this->xmlDocument->query('//SetDTE/Caratula/NroResol');

        return new AutorizacionDte($fechaResolucion, $numeroResolucion);
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaFirma(): string
    {
        return $this->xmlDocument->query('//SetDTE/Caratula/TmstFirmaEnv');
    }

    /**
     * {@inheritDoc}
     */
    public function getResumen(): array
    {
        $SubTotDTE = $this->xmlDocument->query('//SetDTE/Caratula/SubTotDTE');

        return isset($SubTotDTE[0]) ? $SubTotDTE : [$SubTotDTE];
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocumentos(): array
    {
        $documentos = [];

        $documentsNodes = $this->getXmlDocument()->getElementsByTagName('DTE');

        foreach ($documentsNodes as $documentNode) {
            $documentos[] = $documentNode->C14N();
        }

        return $documentos;
    }
}
