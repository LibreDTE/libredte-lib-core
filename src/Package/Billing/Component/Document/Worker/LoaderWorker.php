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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;

/**
 * Clase para cargar documentos tributarios desde su XML.
 */
#[Worker(name: 'loader', component: 'document', package: 'billing')]
class LoaderWorker extends AbstractWorker implements LoaderWorkerInterface
{
    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'xml' => ['example' => ''],
        ],
    )]
    public function loadXml(XmlDocumentInterface|string $xml): DocumentBagInterface
    {
        if (is_string($xml)) {
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadXml($xml);
        } else {
            $xmlDocument = $xml;
        }

        $bag = new DocumentBag();
        $bag->setXmlDocument($xmlDocument);

        return $this->documentBagManager->normalize($bag, all: true);
    }
}
