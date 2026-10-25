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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\DocumentResponseWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\EnvioRecibos;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\RespuestaEnvio;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\TipoDocumentoRespuesta;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\DocumentResponseException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildEnvioRecibosJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildRespuestaEnvioJob;
use Throwable;

/**
 * Worker "billing.exchange.document_response".
 *
 * Genera los XML de respuesta al intercambio de DTE:
 *   - `EnvioRecibos`: recibo de mercaderías o servicios (acción ERM).
 *   - `RespuestaDTE`: acuse de recibo del envío o resultado de validación.
 */
#[Worker(name: 'document_response', component: 'exchange', package: 'billing')]
class DocumentResponseWorker extends AbstractWorker implements DocumentResponseWorkerInterface
{
    public function __construct(
        private BuildEnvioRecibosJob $buildEnvioRecibosJob,
        private BuildRespuestaEnvioJob $buildRespuestaEnvioJob,
        private XmlServiceInterface $xmlService,
        private SignatureServiceInterface $signatureService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'bag' => [
                'example' => [
                    'tipo' => TipoDocumentoRespuesta::ENVIO_RECIBOS,
                    'caratula' => [
                        'RutResponde' => '76192083-9',
                        'RutRecibe' => '88888888-8',
                    ],
                    'data' => [
                        [
                            'TipoDoc' => 33,
                            'Folio' => 1,
                            'FchEmis' => '2024-01-15',
                            'RUTEmisor' => '88888888-8',
                            'RUTRecep' => '76192083-9',
                            'MntTotal' => 100000,
                            'Recinto' => 'Oficina central',
                            'RutFirma' => '76192083-9',
                        ],
                    ],
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                ],
            ],
        ],
    )]
    public function buildEnvioRecibos(ExchangeDocumentBag $bag): EnvioRecibos
    {
        try {
            $document = $this->buildEnvioRecibosJob->build($bag);
            $bag->setDocument($document);

            return $document;
        } catch (Throwable $e) {
            throw new DocumentResponseException(
                message: [
                    'Could not build EnvioRecibos: {detail}',
                    'detail' => $e,
                ],
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'bag' => [
                'example' => [
                    'tipo' => TipoDocumentoRespuesta::RESPUESTA_ENVIO,
                    'caratula' => [
                        'RutResponde' => '76192083-9',
                        'RutRecibe' => '88888888-8',
                        'IdRespuesta' => 1,
                    ],
                    'data' => [
                        'resultado_dte' => [
                            [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                                'FchEmis' => '2024-01-15',
                                'RUTEmisor' => '88888888-8',
                                'RUTRecep' => '76192083-9',
                                'MntTotal' => 100000,
                                'CodEnvio' => 1,
                                'EstadoDTE' => 0,
                                'EstadoDTEGlosa' => 'ACEPTADO OK',
                            ],
                        ],
                    ],
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                ],
            ],
        ],
    )]
    public function buildRespuestaEnvio(ExchangeDocumentBag $bag): RespuestaEnvio
    {
        try {
            $document = $this->buildRespuestaEnvioJob->build($bag);
            $bag->setDocument($document);

            return $document;
        } catch (Throwable $e) {
            throw new DocumentResponseException(
                message: [
                    'Could not build RespuestaDTE: {detail}',
                    'detail' => $e,
                ],
                previous: $e
            );
        }
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
        AbstractExchangeDocument|XmlDocumentInterface|string $source
    ): XmlDocumentInterface {
        $xmlDocument = $this->toXmlDocument($source);

        if ($source instanceof AbstractExchangeDocument) {
            $schemaFile = $source->getSchema();
        } else {
            $root = $xmlDocument->getDomDocument()->documentElement->localName ?? '';
            $schemaFile = match ($root) {
                'EnvioRecibos' => 'EnvioRecibos_v10.xsd',
                'RespuestaDTE' => 'RespuestaEnvioDTE_v10.xsd',
                default => throw new DocumentResponseException([
                    'Root element "{root}" is not recognized as a response document.',
                    'root' => $root,
                ]),
            };
        }

        $schema = dirname(__DIR__, 6) . '/resources/schemas/' . $schemaFile;
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
        AbstractExchangeDocument|XmlDocumentInterface|string $source
    ): array {
        if ($source instanceof AbstractExchangeDocument) {
            $source = $source->saveXml();
        }

        return $this->signatureService->validateXml($source);
    }

    /**
     * Convierte la fuente en un `XmlDocument`.
     */
    private function toXmlDocument(
        AbstractExchangeDocument|XmlDocumentInterface|string $source
    ): XmlDocument {
        if ($source instanceof AbstractExchangeDocument) {
            $xml = $source->saveXml();
        } elseif ($source instanceof XmlDocumentInterface) {
            $xml = $source->saveXml();
        } else {
            $xml = $source;
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        return $xmlDocument;
    }
}
