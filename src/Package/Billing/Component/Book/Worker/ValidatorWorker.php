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
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;

/**
 * Worker que valida los libros tributarios electrónicos.
 *
 * Realiza dos tipos de validación:
 *   - Esquema XSD: obtiene el nombre del XSD desde `BookInterface::getSchema()`
 *     (bag) o desde el atributo `xsi:schemaLocation` del propio XML (XmlDocument
 *     o string). El XML es siempre la fuente de verdad del esquema que declara.
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
    ): XmlDocumentInterface {
        if ($source instanceof BookBagInterface) {
            $schema = dirname(__DIR__, 6)
                . '/resources/schemas/'
                . $source->getBook()->getSchema();

            // Se pasa el XML como string para forzar un re-parseo que garantice
            // las declaraciones de namespace en el DOM, necesario cuando el libro
            // no pasó por el ciclo de firma (ej: libros simplificados).
            $xmlDocument = $this->toXmlDocument($source->getBook()->getXml());
        } else {
            $xmlDocument = $this->toXmlDocument($source);
            $schemaFile = $xmlDocument->getSchema();

            if ($schemaFile === null) {
                throw new BookException(
                    'El XML del libro no declara el atributo xsi:schemaLocation.'
                );
            }

            $schema = dirname(__DIR__, 6) . '/resources/schemas/' . $schemaFile;
        }

        $this->xmlService->validate($xmlDocument, $schema);

        return $xmlDocument;
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
