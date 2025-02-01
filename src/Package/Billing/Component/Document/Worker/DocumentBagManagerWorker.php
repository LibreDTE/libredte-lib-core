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
use Derafu\Lib\Core\Package\Prime\Component\Entity\Contract\EntityComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\NormalizerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SanitizerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentBagManagerException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorFactoryInterface;

/**
 * Clase para el administrador de la bolsa con los datos de un DTE.
 */
class DocumentBagManagerWorker extends AbstractWorker implements DocumentBagManagerWorkerInterface
{
    protected $documentBagClass = DocumentBag::class;

    public function __construct(
        private XmlComponentInterface $xmlComponent,
        private BuilderWorkerInterface $builderWorker,
        private ParserWorkerInterface $parserWorker,
        private NormalizerWorkerInterface $normalizerWorker,
        private SanitizerWorkerInterface $sanitizerWorker,
        private ValidatorWorkerInterface $validatorWorker,
        private EntityComponentInterface $entityComponent,
        private EmisorFactoryInterface $emisorFactory,
        private ReceptorFactoryInterface $receptorFactory
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function create(
        string|array|XmlInterface|DocumentInterface $source,
        bool $normalizeAll = true
    ): DocumentBagInterface {
        // Asignar (si se pasó) o crear la bolsa.
        $class = $this->documentBagClass;
        $bag = new $class();

        // Si los datos vienen como string se deben parsear para asignar.
        // Además se normalizarán.
        if (is_string($source)) {
            $aux = explode(':', $source, 1);
            $parserStrategy = str_replace('parser.strategy.', '', $aux[0]);
            $inputData = $aux[1] ?? '';
            $bag->setInputData($inputData);
            $bag->getOptions()->set('parser.strategy', $parserStrategy);
            $$this->parserWorker->parse($bag);
        }

        // Si los datos vienen como arreglo son los datos normalizados.
        if (is_array($source)) {
            $bag->setNormalizedData($source);
        }

        // Si los datos vienen como documento XML es un XML cargado desde un
        // string XML (carga realizada por LoaderWorker). Ya viene normalizado.
        if ($source instanceof XmlInterface) {
            $bag->setXmlDocument($source);
        }

        // Si los datos vienen como documento tributario entonces es un
        // documento que ya está creado. Puede estar o no timbrado y firmado,
        // eso no se determina ni valida acá. Si debe estará normalizado.
        if ($source instanceof DocumentInterface) {
            $bag->setDocument($source);
        }

        // Normalizar los datos de la bolsa según otros datos que contenga.
        $bag = $this->normalize($bag, all: $normalizeAll);

        // Entregar la bolsa creada.
        return $bag;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(
        DocumentBagInterface $bag,
        bool $all = false
    ): DocumentBagInterface {
        // Datos esenciales que se normalizan.
        $this->ensureParsedData($bag);
        $this->ensureTipoDocumento($bag);

        // Datos extras que se pueden normalizar si se solicitó normalizar todo.
        if ($all === true) {
            $this->ensureNormalizedData($bag);
            $this->ensureXmlDocument($bag);
            $this->ensureDocument($bag);
            $this->ensureEmisor($bag);
            $this->ensureReceptor($bag);
        }

        // Entregar la bolsa normalizada.
        return $bag;
    }

    /**
     * Asegura que existan datos parseados en la bolsa si existen datos de
     * entrada para poder determinarlo y no está asignado previamente.
     *
     * Requiere: $bag->getInputData().
     *
     * Además, si los datos no usan la estrategia por defecto de parseo se debe
     * indicar en las opciones de la bolsa.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function ensureParsedData(DocumentBagInterface $bag): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($bag->getParsedData() || !$bag->getInputData()) {
            return;
        }

        // Parsear los datos.
        $this->parserWorker->parse($bag);
    }

    /**
     * Asegura que exista un tipo de documento en la bolsa si existen datos para
     * poder determinarlo y no está asignado previamente.
     *
     * Requiere: $bag->getParsedData().
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function ensureTipoDocumento(DocumentBagInterface $bag): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($bag->getTipoDocumento() || !$bag->getCodigoTipoDocumento()) {
            return;
        }

        // Buscar el tipo de documento tributario que se desea construir.
        $codigoTipoDocumento = $bag->getCodigoTipoDocumento();
        $tipoDocumento = $this->entityComponent
            ->getRepository(TipoDocumentoInterface::class)
            ->find($codigoTipoDocumento)
        ;

        // Si el documento no existe error.
        if (!$tipoDocumento) {
            throw new DocumentBagManagerException(sprintf(
                'No se encontró un código de documento tributario válido en los datos del DTE.'
            ));
        }

        // Asignar el tipo documento a la bolsa.
        $bag->setTipoDocumento($tipoDocumento);
    }

    /**
     * Asegura que existan los datos normalizados en la bolsa si existen datos
     * parseados para poder determinarlo y no está asignado previamente.
     *
     * Los datos normalizados también se pueden crear si existe un XmlDocument
     * o un DTE.
     *
     * Requiere: $bag->getParsedData() o $bag->getXmlDocument() o $bag->getDocument().
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function ensureNormalizedData(DocumentBagInterface $bag): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($bag->getNormalizedData()) {
            return;
        }

        // Construir los datos normalizados a partir de los datos parseados.
        if ($bag->getParsedData()) {
            // Normalizar los datos del documento de la bolsa si corresponde.
            $normalize = $bag->getNormalizerOptions()['normalize'] ?? true;
            if ($normalize) {
                // Normalizar los datos parseados de la bolsa.
                $this->normalizerWorker->normalize($bag);

                // Sanitizar los datos normalizados de la bolsa.
                $this->sanitizerWorker->sanitize($bag);

                // Validar los datos normalizados y sanitizados de la bolsa.
                $this->validatorWorker->validate($bag);
            }
        }

        // Construir los datos normalizados a partir del documento XML.
        // Importante: se asume que el documento XML se cargó desde un XML que
        // es válido y por ende normalizado.
        elseif ($bag->getXmlDocument()) {
            $normalizedData = $this->xmlComponent->getDecoderWorker()->decode(
                $bag->getXmlDocument()
            );
            $bag->setNormalizedData($normalizedData);
        }

        // Construir los datos normalizados a partir del DTE.
        // Importante: se asume que el DTE se cargó desde un XML que
        // es válido y por ende normalizado.
        elseif ($bag->getDocument()) {
            $normalizedData = $this->xmlComponent->getDecoderWorker()->decode(
                $bag->getDocument()->getXmlDocument()
            );
            $bag->setNormalizedData($normalizedData);
        }
    }

    /**
     * Asegura que existan un documento XML en la bolsa si existen datos
     * normalizados para poder determinarlo y no está asignado previamente.
     *
     * El XmlDocument también se puede crear si existe DTE.
     *
     * Requiere: $bag->getNormalizedData() o $bag->getDocument().
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function ensureXmlDocument(DocumentBagInterface $bag): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($bag->getXmlDocument()) {
            return;
        }

        // Construir el XmlDocument a partir de los datos normalizados.
        if ($bag->getNormalizedData()) {
            $tagXml = $bag->getTipoDocumento()->getTagXml()->getNombre();
            $xmlDocumentData = [
                'DTE' => [
                    '@attributes' => [
                        'version' => '1.0',
                        'xmlns' => 'http://www.sii.cl/SiiDte',
                    ],
                    $tagXml => array_merge([
                        '@attributes' => [
                            'ID' => $bag->getId(),
                        ],
                    ], $bag->getNormalizedData()),
                ],
            ];
            $xmlDocument = $this->xmlComponent->getEncoderWorker()->encode(
                $xmlDocumentData
            );
            $bag->setXmlDocument($xmlDocument);
        }

        // Asignar el XmlDocument a partir del DTE.
        elseif ($bag->getDocument()) {
            $bag->setXmlDocument($bag->getDocument()->getXmlDocument());
        }
    }

    /**
     * Asegura que existan un DTE en la bolsa si existe un XmlDocument
     * para poder crearlo y no está asignado previamente.
     *
     * Requiere: $bag->getXmlDocument() y $bag->getTipoDocumento().
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function ensureDocument(DocumentBagInterface $bag): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($bag->getDocument() || !$bag->getXmlDocument() || !$bag->getTipoDocumento()) {
            return;
        }

        // Crear el DTE.
        $document = $this->builderWorker->create($bag);
        $bag->setDocument($document);
    }

    /**
     * Asegura que existan el emisor en la bolsa si existe el dTE
     * para poder determinarlo y no está asignado previamente.
     *
     * El emisor también se pueden crear si existen un XmlDocument, datos
     * normalizados o datos parseados.
     *
     * Requiere: $bag->getDocument(), $bag->getXmlDocument(),
     * $bag->getNormalizedData() o $bag->getParsedData().
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function ensureEmisor(DocumentBagInterface $bag): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($bag->getEmisor()) {
            return;
        }

        // Crear a partir de los datos del DTE (sería lo más normal).
        if ($bag->getDocument()) {
            $emisor = $this->emisorFactory->create(
                $bag->getDocument()->getEmisor()
            );
            $bag->setEmisor($emisor);
        }

        // Crear a partir de los datos del XmlDocument.
        elseif ($bag->getXmlDocument()) {
            $emisor = $this->emisorFactory->create(
                $bag->getXmlDocument()->query(
                    '/Encabezado/Emisor'
                )
            );
            $bag->setEmisor($emisor);
        }

        // Crear a partir de los datos normalizados.
        elseif ($bag->getNormalizedData()) {
            $emisor = $this->emisorFactory->create(
                $bag->getNormalizedData()['Encabezado']['Emisor']
            );
            $bag->setEmisor($emisor);
        }

        // Crear a partir de los datos parseados.
        elseif ($bag->getParsedData()) {
            $emisor = $this->emisorFactory->create(
                $bag->getParsedData()['Encabezado']['Emisor']
            );
            $bag->setEmisor($emisor);
        }
    }

    /**
     * Asegura que existan el emisor en la bolsa si existen datos
     * normalizados para poder determinarlo y no está asignado previamente.
     *
     * El emisor también se pueden crear si existe un XmlDocument o un DTE.
     *
     * Requiere: $bag->getNormalizedData() o $bag->getXmlDocument() o $bag->getDocument().
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function ensureReceptor(DocumentBagInterface $bag): void
    {
        // Si ya está asignado el receptor no se hace nada.
        if ($bag->getReceptor()) {
            return;
        }

        // Crear a partir de los datos del DTE (sería lo más normal).
        if ($bag->getDocument()) {
            $emisor = $this->receptorFactory->create(
                $bag->getDocument()->getReceptor()
            );
            $bag->setReceptor($emisor);
        }

        // Crear a partir de los datos del XmlDocument.
        elseif ($bag->getXmlDocument()) {
            $emisor = $this->receptorFactory->create(
                $bag->getXmlDocument()->query(
                    '/Encabezado/Receptor'
                )
            );
            $bag->setReceptor($emisor);
        }

        // Crear a partir de los datos normalizados.
        elseif ($bag->getNormalizedData()) {
            $emisor = $this->receptorFactory->create(
                $bag->getNormalizedData()['Encabezado']['Receptor']
            );
            $bag->setReceptor($emisor);
        }

        // Crear a partir de los datos parseados.
        elseif ($bag->getParsedData()) {
            $emisor = $this->receptorFactory->create(
                $bag->getParsedData()['Encabezado']['Receptor']
            );
            $bag->setReceptor($emisor);
        }
    }
}
