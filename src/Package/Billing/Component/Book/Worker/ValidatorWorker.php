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
        name: 'validate_schema',
        summary: 'Valida el XML de un libro contra su esquema',
        description: <<<'MD'
            Valida el XML de un libro tributario electrónico contra su esquema XSD del SII. El esquema se toma del atributo `xsi:schemaLocation` que declara el propio XML.

            ## Cómo se usa

            Se envía en `source` el XML del libro, por ejemplo el que entrega `billing.book.builder::build` en `book.xml`. Si el XML cumple el esquema, la respuesta es el mismo documento. Si no lo cumple, la operación falla con un error que indica el esquema usado y la línea y la columna del problema.
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del libro codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'El XML es válido. Se devuelve el documento como árbol: el elemento raíz del libro con su carátula, resumen, detalle y firma.',
                'example' => [
                    'LibroCompraVenta' => [
                        'EnvioLibro' => [
                            'Caratula' => [
                                'RutEmisorLibro' => '76192083-9',
                                'RutEnvia' => '12345678-2',
                                'PeriodoTributario' => '2024-01',
                                'FchResol' => '2014-08-22',
                                'NroResol' => '80',
                                'TipoOperacion' => 'VENTA',
                                'TipoLibro' => 'MENSUAL',
                                'TipoEnvio' => 'TOTAL',
                            ],
                            'ResumenPeriodo' => [
                                'TotalesPeriodo' => [
                                    'TpoDoc' => '33',
                                    'TotDoc' => '1',
                                    'TotMntExe' => '0',
                                    'TotMntNeto' => '100000',
                                    'TotMntIVA' => '19000',
                                    'TotMntTotal' => '119000',
                                ],
                            ],
                            'Detalle' => [
                                'TpoDoc' => '33',
                                'NroDoc' => '1',
                                'TasaImp' => '19',
                                'FchDoc' => '2024-01-10',
                                'RUTDoc' => '66666666-6',
                                'RznSoc' => 'Cliente de Prueba',
                                'MntNeto' => '100000',
                                'MntIVA' => '19000',
                                'MntTotal' => '119000',
                            ],
                            'TmstFirma' => '2024-02-01T10:00:00',
                        ],
                        'Signature' => [
                            'SignedInfo' => [
                                'CanonicalizationMethod' => '',
                                'SignatureMethod' => '',
                                'Reference' => [
                                    'Transforms' => [
                                        'Transform' => '',
                                    ],
                                    'DigestMethod' => '',
                                    'DigestValue' => 'zfRoDyFx1Hzt++GV1R0FJtfuiCw=',
                                ],
                            ],
                            'SignatureValue' => 'XOs4lQZku6vLebe6...',
                            'KeyInfo' => [
                                'KeyValue' => [
                                    'RSAKeyValue' => [
                                        'Modulus' => '3g4+1EwG74k7vOsa...',
                                        'Exponent' => 'AQAB',
                                    ],
                                ],
                                'X509Data' => [
                                    'X509Certificate' => 'MIID8zCCAtugAwIBAgIB...',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function validateSchema(
        BookBagInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface {
        if ($source instanceof BookBagInterface) {
            $schema = dirname(__DIR__, 6)
                . '/resources/schemas/'
                . $source->requireBook()->getSchema();

            // Se pasa el XML como string para forzar un re-parseo que garantice
            // las declaraciones de namespace en el DOM, necesario cuando el libro
            // no pasó por el ciclo de firma (ej: libros simplificados).
            $xmlDocument = $this->toXmlDocument($source->requireBook()->saveXml());
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
        name: 'validate_signature',
        summary: 'Valida la firma electrónica de un libro',
        description: <<<'MD'
            Valida la firma electrónica (XML DSIG) del XML de un libro tributario electrónico y entrega los datos de la firma y del certificado del firmante.

            ## Cómo se usa

            Se envía en `source` el XML del libro firmado, por ejemplo el que entrega `billing.book.builder::build` en `book.xml`.

            La operación responde con éxito tanto si la firma es válida como si no lo es: el resultado está en el campo `valid`. Si es `false`, el campo `error` explica el motivo (por ejemplo, que el contenido se modificó después de firmarlo).
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del libro firmado, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => '`valid` indica si la firma es válida y `error` trae el motivo cuando no lo es (`null` si es válida). `signature` trae los datos de la firma y `signature.signer` los del certificado del firmante.',
                'example' => [
                    'valid' => true,
                    'error' => null,
                    'signature' => [
                        'reference' => 'LibreDTE_LIBRO_VENTA_761920839_202401_1794490668',
                        'canonicalization_method' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                        'signature_method' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                        'digest_method' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                        'digest_value' => '3YF6yPBrfNdJIs7oewSUbu+EXZ0=',
                        'signature_value' => 'XCx/cVHQxiUg8/UI/pXb+iUh...',
                        'modulus' => 'hUNOQsNO9kb6QPfMeDTzDGruGejPM2O0...',
                        'exponent' => 'AQAB',
                        'certificate' => 'MIID8zCCAtugAwIBAgIBADANBgkqhkiG9w0B...',
                        'certificate_chain' => [],
                        'signer' => [
                            'id' => '76192083-9',
                            'name' => 'Empresa de Ejemplo SpA',
                            'email' => 'contacto@example.com',
                            'from' => '2024-01-01T00:00:00',
                            'to' => '2025-01-01T00:00:00',
                            'total_days' => 366,
                            'expiration_days' => 300,
                            'is_active' => true,
                            'issuer' => 'Empresa de Ejemplo SpA',
                            'modulus' => 'hUNOQsNO9kb6QPfMeDTzDGruGejPM2O0...',
                            'exponent' => 'AQAB',
                            'cert' => "-----BEGIN CERTIFICATE-----\nMIID8zCCAtugAwIBAgIB...",
                            'chain' => [],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function validateSignature(
        BookBagInterface|XmlDocumentInterface|string $source
    ): SignatureValidationResultInterface {
        if ($source instanceof BookBagInterface) {
            $source = $source->requireBook()->saveXml();
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
