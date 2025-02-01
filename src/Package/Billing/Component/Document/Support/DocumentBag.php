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

namespace libredte\lib\Core\Package\Billing\Component\Document\Support;

use Derafu\Lib\Core\Common\Trait\OptionsAwareTrait;
use Derafu\Lib\Core\Helper\Arr;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use Derafu\Lib\Core\Support\Store\Contract\DataContainerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;
use LogicException;
use stdClass;

/**
 * Contenedor de datos del documento tributario electrónico.
 *
 * Permite "mover" un documento, junto a otros datos asociados, por métodos de
 * manera sencilla y, sobre todo, extensible.
 */
class DocumentBag implements DocumentBagInterface
{
    use OptionsAwareTrait;

    /**
     * Reglas de esquema de las opciones del documento.
     *
     * Acá solo se indicarán los índices que deben pueden existir en las
     * opciones. No se define el esquema de cada opción pues cada clase que
     * utilice estas opciones deberá resolver y validar sus propias opciones.
     *
     * @var array
     */
    protected array $optionsSchema = [
        'builder' => [
            'types' => 'array',
            'default' => [],
        ],
        'normalizer' => [
            'types' => 'array',
            'default' => [],
        ],
        'parser' => [
            'types' => 'array',
            'default' => [],
        ],
        'renderer' => [
            'types' => 'array',
            'default' => [],
        ],
        'sanitizer' => [
            'types' => 'array',
            'default' => [],
        ],
        'validator' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * Datos originales de entrada que se utilizarán para construir el
     * documento tributario.
     *
     * El formato de estos datos puede ser cualquiera soportado por los parsers.
     *
     * @var string|null
     */
    private ?string $inputData;

    /**
     * Datos de entrada procesados (parseados).
     *
     * Están en el formato estándar de LibreDTE. Que es básicamente el oficial
     * del SII.
     *
     * Estos son los datos que se usarán para construir el documento. Estos
     * datos no están normaliados, solo parseados.
     *
     * @var array|null
     */
    private ?array $parsedData;

    /**
     * Datos normalizados del documento tributario.
     *
     * Son los datos con todos sus campos necesarios ya determinados, calculados
     * y validados.
     *
     * La estructura de estos datos depende de los normalizadores.
     *
     * Importante: si se desactiva la normalización este arreglo contendrá lo
     * mismo que $parsedData pues no se tocarán los datos de entrada procesados.
     *
     * @var array|null
     */
    private ?array $normalizedData;

    /**
     * Datos de LibreDTE asociados al documento tributario.
     *
     * Estos son datos que LibreDTE utiliza asociados al documento pero no son
     * parte de la estructura oficial que utiliza el SII.
     *
     * Por ejemplo se puede incluir:
     *
     *   - Tags de facturas en PDF de boletas. Ejemplo: TermPagoGlosa.
     *   - Datos adicionales para los PDF. Ejemplo: historial.
     *
     * @var array|null
     */
    private ?array $libredteData;

    /**
     * Instancia del documento XML asociada al DTE.
     *
     * @var XmlInterface|null
     */
    private ?XmlInterface $xmlDocument;

    /**
     * Código de Asignación de Folios (CAF) para timbrar el Documento Tributario
     * Electrónico (DTE) que se generará.
     *
     * @var CafInterface|null
     */
    private ?CafInterface $caf;

    /**
     * Certificado digital (firma electrónica) para la firma del documento.
     *
     * @var CertificateInterface|null
     */
    private ?CertificateInterface $certificate;

    /**
     * Entidad con el documento tributario electrónico generado.
     *
     * @var DocumentInterface|null
     */
    private ?DocumentInterface $document;

    /**
     * Entidad que representa al tipo de documento tributario que está contenido
     * en esta bolsa.
     *
     * @var TipoDocumentoInterface|null
     */
    private ?TipoDocumentoInterface $documentType = null;

    /**
     * Emisor del documento tributario.
     *
     * @var EmisorInterface|null
     */
    private ?EmisorInterface $emisor = null;

    /**
     * Receptor del documento tributario.
     *
     * @var ReceptorInterface|null
     */
    private ?ReceptorInterface $receptor = null;

    /**
     * Arreglo con la estructura del nodo TED del documento.
     *
     * @var array|null
     */
    private ?array $timbre = null;

    /**
     * Arreglo con los datos normalizados consolidados con el timbre y la firma
     * si existen en la bolsa.
     *
     * @var array|null
     */
    private ?array $data = null;

    /**
     * Constructor del contenedor.
     *
     * Recibe los datos en diferentes formatos para pasarlos a los setters que
     * los normalizan y asignan al contenedor.
     *
     * @param string|array|stdClass|null $inputData
     * @param array|null $parsedData
     * @param array|null $normalizedData
     * @param array|null $libredteData
     * @param array|DataContainerInterface $options
     * @param XmlInterface|null $xmlDocument
     * @param CafInterface|null $caf
     * @param CertificateInterface|null $certificate
     * @param DocumentInterface|null $document
     * @param TipoDocumentoInterface|null $documentType
     * @param EmisorInterface|null $emisor
     * @param ReceptorInterface|null $receptor
     */
    public function __construct(
        string|array|stdClass $inputData = null,
        array $parsedData = null,
        array $normalizedData = null,
        array $libredteData = null,
        array|DataContainerInterface $options = [],
        XmlInterface $xmlDocument = null,
        CafInterface $caf = null,
        CertificateInterface $certificate = null,
        DocumentInterface $document = null,
        TipoDocumentoInterface $documentType = null,
        EmisorInterface $emisor = null,
        ReceptorInterface $receptor = null
    ) {
        $this
            ->setInputData($inputData)
            ->setParsedData($parsedData)
            ->setNormalizedData($normalizedData)
            ->setLibredteData($libredteData)
            ->setOptions($options)
            ->setXmlDocument($xmlDocument)
            ->setCaf($caf)
            ->setCertificate($certificate)
            ->setDocument($document)
            ->setDocumentType($documentType)
            ->setEmisor($emisor)
            ->setReceptor($receptor)
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function setInputData(string|array|stdClass|null $inputData): static
    {
        if ($inputData === null) {
            $this->inputData = null;

            return $this;
        }

        if (!is_string($inputData)) {
            $inputData = json_encode($inputData);
        }

        $this->inputData = $inputData;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getInputData(): ?string
    {
        return $this->inputData;
    }

    /**
     * {@inheritDoc}
     */
    public function setParsedData(?array $parsedData): static
    {
        $this->parsedData = $parsedData;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getParsedData(): ?array
    {
        return $this->parsedData;
    }

    /**
     * {@inheritDoc}
     */
    public function setNormalizedData(?array $normalizedData): static
    {
        $this->normalizedData = $normalizedData;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getNormalizedData(): ?array
    {
        return $this->normalizedData;
    }

    /**
     * {@inheritDoc}
     */
    public function setLibredteData(?array $libredteData): static
    {
        $this->libredteData = $libredteData;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLibredteData(): ?array
    {
        return $this->libredteData;
    }

    /**
     * {@inheritDoc}
     */
    public function getParserOptions(): array
    {
        return (array) $this->getOptions()->get('parser');
    }

    /**
     * {@inheritDoc}
     */
    public function getBuilderOptions(): array
    {
        return (array) $this->getOptions()->get('builder');
    }

    /**
     * {@inheritDoc}
     */
    public function getNormalizerOptions(): array
    {
        return (array) $this->getOptions()->get('normalizer');
    }

    /**
     * {@inheritDoc}
     */
    public function getSanitizerOptions(): array
    {
        return (array) $this->getOptions()->get('sanitizer');
    }

    /**
     * {@inheritDoc}
     */
    public function getValidatorOptions(): array
    {
        return (array) $this->getOptions()->get('validator');
    }

    /**
     * {@inheritDoc}
     */
    public function getRendererOptions(): array
    {
        return (array) $this->getOptions()->get('renderer');
    }

    /**
     * {@inheritDoc}
     */
    public function setXmlDocument(?XmlInterface $xmlDocument): static
    {
        $this->xmlDocument = $xmlDocument;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocument(): ?XmlInterface
    {
        return $this->xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function setCaf(?CafInterface $caf): static
    {
        $this->caf = $caf;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCaf(): ?CafInterface
    {
        return $this->caf;
    }

    /**
     * {@inheritDoc}
     */
    public function setCertificate(?CertificateInterface $certificate): static
    {
        $this->certificate = $certificate;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCertificate(): ?CertificateInterface
    {
        return $this->certificate;
    }

    /**
     * {@inheritDoc}
     */
    public function setDocument(?DocumentInterface $document): static
    {
        $this->document = $document;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocument(): ?DocumentInterface
    {
        return $this->document;
    }

    /**
     * {@inheritDoc}
     */
    public function setDocumentType(?TipoDocumentoInterface $documentType): static
    {
        $this->documentType = $documentType;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTipoDocumento(?TipoDocumentoInterface $tipoDocumento): static
    {
        return $this->setDocumentType($tipoDocumento);
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentType(): ?TipoDocumentoInterface
    {
        return $this->documentType;
    }

    /**
     * {@inheritDoc}
     */
    public function getTipoDocumento(): ?TipoDocumentoInterface
    {
        return $this->getDocumentType();
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentTypeId(): ?int
    {
        $TipoDTE = $this->parsedData['Encabezado']['IdDoc']['TipoDTE']
            ?? $this->normalizedData['Encabezado']['IdDoc']['TipoDTE']
            ?? $this->xmlDocument?->query('//Encabezado/IdDoc/TipoDTE')
            ?? $this->document?->getCodigo()
            ?? null
        ;

        if (!$TipoDTE) {
            throw new DocumentException(
                'Falta indicar el tipo de documento (TipoDTE) en los datos del DTE.'
            );
        }

        return (int) $TipoDTE;
    }

    /**
     * {@inheritDoc}
     */
    public function getCodigoTipoDocumento(): ?int
    {
        return $this->getDocumentTypeId();
    }

    /**
     * {@inheritDoc}
     */
    public function setEmisor(?EmisorInterface $emisor): static
    {
        $this->emisor = $emisor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmisor(): ?EmisorInterface
    {
        return $this->emisor;
    }

    /**
     * {@inheritDoc}
     */
    public function setReceptor(?ReceptorInterface $receptor): static
    {
        $this->receptor = $receptor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getReceptor(): ?ReceptorInterface
    {
        return $this->receptor;
    }

    /**
     * {@inheritDoc}
     */
    public function setTimbre(?array $timbre): static
    {
        $this->timbre = $timbre;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTimbre(): ?array
    {
        return $this->timbre;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): ?array
    {
        // Si los datos ya estaban generados se entregan.
        if ($this->data !== null) {
            return $this->data;
        }

        // Si no hay datos normalizados se entrega `null`.
        if (!$this->getNormalizedData()) {
            return null;
        }

        // Se arma la estructura del nodo Documento.
        $tagXml = $this->getTipoDocumento()->getTagXml()->getNombre();
        $this->data = [
            'DTE' => [
                '@attributes' => [
                    'version' => '1.0',
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                ],
                $tagXml => array_merge(
                    [
                        '@attributes' => [
                            'ID' => $this->getId(),
                        ],
                    ],
                    $this->getNormalizedData(),
                    (array) $this->getTimbre(),
                ),
                //'Signature' => '', // Se agrega al firmar (NO INCLUIR ACÁ).
            ],
        ];

        // Se entrega la estructura con los datos.
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentData(): ?array
    {
        if (!isset($this->document)) {
            return null;
        }

        $documentData = $this->document->getDatos();
        $documentExtra = $this->libredteData['extra']['dte'] ?? null;

        if (empty($documentExtra)) {
            return $documentData;
        }

        return Arr::mergeRecursiveDistinct($documentData, $documentExtra);
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentExtra(): ?array
    {
        if (!isset($this->document)) {
            return null;
        }

        $extra = $this->libredteData['extra'] ?? null;

        if (empty($extra)) {
            return null;
        }

        unset($extra['dte']);

        return $extra;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentStamp(): ?string
    {
        return $this->document?->getTED();
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentAuth(): ?array
    {
        return $this->emisor?->getAutorizacionDte()?->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        $folio = $this->getFolio();

        if (is_int($folio)) {
            return sprintf(
                'LibreDTE_%s_T%03dF%09d',
                $this->getNormalizedData()['Encabezado']['Emisor']['RUTEmisor'],
                $this->getNormalizedData()['Encabezado']['IdDoc']['TipoDTE'],
                $folio
            );
        } else {
            return sprintf(
                'LibreDTE_%s_%03d-%s',
                $this->getNormalizedData()['Encabezado']['Emisor']['RUTEmisor'],
                $this->getNormalizedData()['Encabezado']['IdDoc']['TipoDTE'],
                $folio
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setFolio(int $folio): static
    {
        if ($this->getXmlDocument()) {
            throw new LogicException(
                'No es posible asignar el folio si ya se generó el documento XML.'
            );
        }

        $parsedData = $this->getParsedData();
        $normalizedData = $this->getNormalizedData();

        if ($parsedData === null && $normalizedData === null) {
            throw new LogicException(
                'No es posible asignar el folio si no existen datos parseados o normalizados.'
            );
        }

        if ($parsedData !== null) {
            $parsedData['Encabezado']['IdDoc']['Folio'] = $folio;
            $this->setParsedData($parsedData);
        }

        if ($normalizedData !== null) {
            $normalizedData['Encabezado']['IdDoc']['Folio'] = $folio;
            $this->setNormalizedData($normalizedData);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFolio(): int|string|null
    {
        $data = $this->getNormalizedData() ?? $this->getParsedData();

        $folio = $data['Encabezado']['IdDoc']['Folio'];

        if (!$folio) {
            return null;
        }

        return is_numeric($folio) ? (int) $folio : (string) $folio;
    }

    /**
     * {@inheritDoc}
     */
    public function withCaf(CafInterface $caf): DocumentBagInterface
    {
        $class = static::class;

        return new $class(
            inputData: $this->getInputData(),
            parsedData: $this->getParsedData(),
            normalizedData: $this->getNormalizedData(),
            libredteData: $this->getLibredteData(),
            options: $this->getOptions(),
            caf: $caf,
            certificate: $this->getCertificate(),
            documentType: $this->getDocumentType(),
            emisor: $this->getEmisor(),
            receptor: $this->getReceptor()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function withCertificate(
        CertificateInterface $certificate
    ): DocumentBagInterface {
        $class = static::class;

        return new $class(
            inputData: $this->getInputData(),
            parsedData: $this->getParsedData(),
            normalizedData: $this->getNormalizedData(),
            libredteData: $this->getLibredteData(),
            options: $this->getOptions(),
            caf: $this->getCaf(),
            certificate: $certificate,
            documentType: $this->getDocumentType(),
            emisor: $this->getEmisor(),
            receptor: $this->getReceptor()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias(): string
    {
        return $this->getTipoDocumento()?->getAlias()
            ?? (
                $this->getTipoDocumento()?->getCodigo()
                    ? 'documento_' .  $this->getTipoDocumento()->getCodigo()
                    : null
            )
            ?? $this->getParsedData()['Encabezado']['IdDoc']['TipoDTE']
            ?? 'documento_desconocido'
        ;
    }
}
