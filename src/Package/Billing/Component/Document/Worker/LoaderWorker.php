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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
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
