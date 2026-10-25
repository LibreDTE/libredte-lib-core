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

use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlQueryException;
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
        $value = $this->xmlDocument->query('//SetDTE/Caratula/RutEmisor');

        if ($value === null) {
            throw new XmlQueryException(
                'The shipping envelope XML does not include an assigned issuer RUT.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//SetDTE/Caratula/RutEmisor'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getRunMandatario(): string
    {
        $value = $this->xmlDocument->query('//SetDTE/Caratula/RutEnvia');

        if ($value === null) {
            throw new XmlQueryException(
                'The shipping envelope XML does not include an assigned agent RUN.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//SetDTE/Caratula/RutEnvia'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getRutReceptor(): string
    {
        $value = $this->xmlDocument->query('//SetDTE/Caratula/RutReceptor');

        if ($value === null) {
            throw new XmlQueryException(
                'The shipping envelope XML does not include an assigned recipient RUT.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//SetDTE/Caratula/RutReceptor'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getAutorizacionDte(): AutorizacionDteInterface
    {
        $fecha_resolucion = $this->xmlDocument->query('//SetDTE/Caratula/FchResol');

        if ($fecha_resolucion === null) {
            throw new XmlQueryException(
                'The shipping envelope XML does not include an assigned resolution date.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//SetDTE/Caratula/FchResol'
            );
        }

        $numero_resolucion = $this->xmlDocument->query('//SetDTE/Caratula/NroResol');

        if ($numero_resolucion === null) {
            throw new XmlQueryException(
                'The shipping envelope XML does not include an assigned resolution number.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//SetDTE/Caratula/NroResol'
            );
        }

        return new AutorizacionDte($fecha_resolucion, (int) $numero_resolucion);
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaFirma(): string
    {
        $value = $this->xmlDocument->query('//SetDTE/Caratula/TmstFirmaEnv');

        if ($value === null) {
            throw new XmlQueryException(
                'The shipping envelope XML does not include an assigned signature date.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//SetDTE/Caratula/TmstFirmaEnv'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getResumen(): array
    {
        $SubTotDTE = $this->xmlDocument->query('//SetDTE/Caratula/SubTotDTE');

        if ($SubTotDTE === null) {
            throw new XmlQueryException(
                'The shipping envelope XML does not include an assigned summary.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//SetDTE/Caratula/SubTotDTE'
            );
        }

        return isset($SubTotDTE[0]) ? $SubTotDTE : [$SubTotDTE];
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
