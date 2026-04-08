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

namespace libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
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
    public function build(AecBag $bag): Aec
    {
        try {
            return $this->buildAecJob->build($bag);
        } catch (Throwable $e) {
            throw new OwnershipTransferException(
                message: sprintf(
                    'No fue posible construir el AEC: %s',
                    $e->getMessage()
                ),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
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
    public function validateSignature(
        Aec|XmlDocumentInterface|string $source
    ): array {
        if ($source instanceof Aec) {
            $source = $source->getXml();
        }

        return $this->signatureService->validateXml($source);
    }

    /**
     * Convierte la fuente en un `XmlDocument`.
     */
    private function toXmlDocument(Aec|XmlDocumentInterface|string $source): XmlDocument
    {
        if ($source instanceof Aec) {
            $xml = $source->getXml();
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
