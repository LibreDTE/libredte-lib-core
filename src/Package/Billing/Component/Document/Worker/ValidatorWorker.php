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

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Contract\SignatureComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Entity\Xml as XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ValidatorException;

/**
 * Clase para los validadores de documentos.
 */
class ValidatorWorker extends AbstractWorker implements ValidatorWorkerInterface
{
    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager,
        private XmlComponentInterface $xmlComponent,
        private SignatureComponentInterface $signatureComponent,
        iterable $jobs = [],
        iterable $handlers = [],
        iterable $strategies = []
    ) {
        parent::__construct(
            jobs: $jobs,
            handlers: $handlers,
            strategies: $strategies
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validate(
        DocumentBagInterface|XmlInterface|string $source
    ): void {
        // Asignar la bolsa del DTE a partir de la fuente.
        if (is_string($source)) {
            // Importante: Esto quitará la firma, no sirve para otras
            // validaciones (de esquema o firma). Solo para esta validación que
            // solo valida los datos contenidos en el documento.
            $source = 'parser.strategy.default.xml:' . $source;
        }
        $bag = $source instanceof DocumentBagInterface
            ? $source
            : $this->documentBagManager->create($source)
        ;

        // Si no hay tipo de documento no se podrá normalizar.
        if (!$bag->getTipoDocumento()) {
            throw new ValidatorException(
                'No es posible validar sin un TipoDocumento en la $bag.'
            );
        }

        // Buscar la estrategia para validar el tipo de documento tributario.
        $strategy = $this->getStrategy($bag->getTipoDocumento()->getAlias());
        assert($strategy instanceof ValidatorStrategyInterface);

        // Validar el documento usando la estrategia.
        $strategy->validate($bag);
    }

    /**
     * {@inheritDoc}
     */
    public function validateSchema(
        DocumentBagInterface|XmlInterface|string $source
    ): void {
        // Obtener el documento XML.
        if ($source instanceof DocumentBagInterface) {
            $xmlDocument = $source->getXmlDocument();
        } elseif ($source instanceof XmlInterface) {
            $xmlDocument = $source;
        } else {
            // Importante: Hacerlo de esta forma garantiza que no se pierda el
            // nodo Signature. Pues se carga el XML completo a XmlDocument.
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadXml($source);
        }

        // Crear una bolsa con el documento XML. Con esto se obtendrá en la
        // bolsa el tipo de documento asociado al DTE.
        $bag = $this->documentBagManager->create($xmlDocument, normalizeAll: false);

        // Las boletas no se validan de manera individual (el DTE). Se validan
        // a través del EnvioBOLETA.
        if ($bag->getTipoDocumento()->esBoleta()) {
            return;
        }

        // Validar esquema de otros DTE (no boletas).
        $schema = dirname(__DIR__, 6) . '/resources/schemas/DTE_v10.xsd';
        $this->xmlComponent->getValidatorWorker()->validateSchema(
            $bag->getXmlDocument(),
            $schema
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validateSignature(
        DocumentBagInterface|XmlInterface|string $source
    ): void {
        $xml = $source instanceof DocumentBagInterface
            ? $source->getXmlDocument()
            : $source
        ;

        $this->signatureComponent->getValidatorWorker()->validateXml($xml);
    }
}
