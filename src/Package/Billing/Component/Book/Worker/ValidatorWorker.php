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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Signature\Contract\SignatureValidationResultInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use ValueError;

/**
 * Worker que valida los libros tributarios electrónicos.
 *
 * Realiza dos tipos de validación:
 *   - Esquema XSD: para bags usa `TipoLibro::getSchema()`; para XML directo
 *     detecta el esquema desde el elemento raíz vía `TipoLibro::schemaFromXmlRoot()`.
 *   - Firma electrónica: validación XML DSIG.
 */
#[Worker(name: 'validator', component: 'book', package: 'billing')]
class ValidatorWorker extends AbstractWorker implements ValidatorWorkerInterface
{
    public function __construct(
        private XmlServiceInterface $xmlService,
        private SignatureServiceInterface $signatureService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function validateSchema(
        BookBagInterface|XmlDocumentInterface|string $source
    ): void {
        if ($source instanceof BookBagInterface) {
            $schema = dirname(__DIR__, 6)
                . '/resources/schemas/'
                . $source->getTipo()->getSchema();

            $xmlDocument = $this->toXmlDocument($source->getBook()->getXmlDocument());
        } else {
            $xmlDocument = $this->toXmlDocument($source);
            $root = $xmlDocument->documentElement?->localName ?? '';

            try {
                $schemaFile = TipoLibro::fromTag($root)->getSchema();
            } catch (ValueError $e) {
                throw new BookException($e->getMessage(), previous: $e);
            }

            $schema = dirname(__DIR__, 6) . '/resources/schemas/' . $schemaFile;
        }

        $this->xmlService->validate($xmlDocument, $schema);
    }

    /**
     * {@inheritDoc}
     */
    public function validateSignature(
        BookBagInterface|XmlDocumentInterface|string $source
    ): SignatureValidationResultInterface {
        if ($source instanceof BookBagInterface) {
            $source = $source->getBook()->getXml();
        }

        return $this->signatureService->validateXml($source)[0];
    }

    /**
     * Convierte un string XML en XmlDocument.
     */
    private function toXmlDocument(XmlDocumentInterface|string $source): XmlDocument
    {
        if ($source instanceof XmlDocument) {
            return $source;
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml(
            $source instanceof XmlDocumentInterface ? $source->saveXml() : $source
        );

        return $xmlDocument;
    }
}
