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

namespace libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Contract\AecWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\Aec;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Exception\OwnershipTransferException;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Support\AecBag;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\Aec\Job\BuildAecJob;
use Throwable;

/**
 * Worker "billing.ownership_transfer.aec".
 *
 * Genera y valida el Archivo Electrónico de Cesión (AEC).
 */
#[Worker(name: 'aec', component: 'ownership_transfer', package: 'billing')]
class AecWorker extends AbstractWorker implements AecWorkerInterface
{
    public function __construct(
        private BuildAecJob $buildAecJob,
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
                    'source' => '',
                    'cedente' => [
                        'RUT' => '76192083-9',
                        'RazonSocial' => 'SASCO SpA',
                        'Direccion' => 'Santa Cruz, Chile',
                        'eMail' => 'cedente@example.com',
                        'RUTAutorizado' => [
                            'RUT' => '76192083-9',
                            'Nombre' => 'Administrador',
                        ],
                    ],
                    'cesionario' => [
                        'RUT' => '76354771-K',
                        'RazonSocial' => 'Factoring S.A.',
                        'Direccion' => 'Providencia, Santiago',
                        'eMail' => 'factoring@example.com',
                    ],
                    'cesion' => [
                        'MontoCesion' => 119000,
                        'UltimoVencimiento' => '2024-02-14',
                    ],
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                ],
            ],
        ],
    )]
    public function build(AecBag $bag): Aec
    {
        try {
            return $this->buildAecJob->build($bag);
        } catch (Throwable $e) {
            throw new OwnershipTransferException(
                message: [
                    'Could not build the AEC: {detail}',
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
        Aec|XmlDocumentInterface|string $source
    ): XmlDocumentInterface {
        $xmlDocument = $this->toXmlDocument($source);

        $schema = dirname(__DIR__, 6) . '/resources/schemas/AEC_v10.xsd';
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
        Aec|XmlDocumentInterface|string $source
    ): array {
        if ($source instanceof Aec) {
            $source = $source->saveXml();
        }

        return $this->signatureService->validateXml($source);
    }

    /**
     * Convierte la fuente en un `XmlDocument`.
     */
    private function toXmlDocument(Aec|XmlDocumentInterface|string $source): XmlDocument
    {
        if ($source instanceof Aec) {
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
