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

namespace libredte\lib\Core\Package\Billing\Component\Document\Entity;

use Derafu\Value\ValueType;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\XPathReader;
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
     * @var XmlDocumentInterface
     */
    protected readonly XmlDocumentInterface $xmlDocument;

    /**
     * Constructor del sobre de documentos tributarios para su envío.
     *
     * @param XmlDocumentInterface $xmlDocument
     * @return void
     */
    public function __construct(XmlDocumentInterface $xmlDocument)
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
    public function getXmlDocument(): XmlDocumentInterface
    {
        return $this->xmlDocument;
    }

    /**
     * Obtiene un lector del documento XML.
     *
     * @return XPathReader
     */
    private function reader(): XPathReader
    {
        return $this->xmlDocument->getReader(source: 'shipping envelope XML');
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
    public function getId(): string
    {
        return 'LibreDTE_SetDoc';
    }

    /**
     * {@inheritDoc}
     */
    public function getRutEmisor(): string
    {
        return $this->reader()->requireString('//SetDTE/Caratula/RutEmisor');
    }

    /**
     * {@inheritDoc}
     */
    public function getRunMandatario(): string
    {
        return $this->reader()->requireString('//SetDTE/Caratula/RutEnvia');
    }

    /**
     * {@inheritDoc}
     */
    public function getRutReceptor(): string
    {
        return $this->reader()->requireString('//SetDTE/Caratula/RutReceptor');
    }

    /**
     * {@inheritDoc}
     */
    public function getAutorizacionDte(): AutorizacionDteInterface
    {
        $reader = $this->reader();

        return new AutorizacionDte(
            $reader->requireString('//SetDTE/Caratula/FchResol'),
            $reader->requireAsInt('//SetDTE/Caratula/NroResol')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaFirma(): string
    {
        return $this->reader()->requireString('//SetDTE/Caratula/TmstFirmaEnv');
    }

    /**
     * {@inheritDoc}
     */
    public function getResumen(): array
    {
        return $this->reader()->requireListOf(
            '//SetDTE/Caratula/SubTotDTE',
            ValueType::map()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocumentos(): array
    {
        $documentos = [];

        $documentsNodes = $this->getXmlDocument()->getDomDocument()->getElementsByTagName('DTE');

        foreach ($documentsNodes as $documentNode) {
            $documentos[] = $documentNode->C14N();
        }

        return $documentos;
    }
}
