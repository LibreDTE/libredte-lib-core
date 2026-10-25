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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
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
    #[Operation(
        parameters: [
            'source' => ['example' => ''],
        ],
    )]
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
            $xmlDocument = $this->toXmlDocument($source->getBook()->saveXml());
        } else {
            $xmlDocument = $this->toXmlDocument($source);
            $schemaFile = $xmlDocument->getSchema();

            if ($schemaFile === null) {
                throw new BookException(
                    'The book XML does not declare the xsi:schemaLocation attribute.'
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
    #[Operation(
        parameters: [
            'source' => ['example' => ''],
        ],
    )]
    public function validateSignature(
        BookBagInterface|XmlDocumentInterface|string $source
    ): SignatureValidationResultInterface {
        if ($source instanceof BookBagInterface) {
            $source = $source->getBook()->saveXml();
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
