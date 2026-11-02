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

namespace libredte\lib\Core\Package\Billing\Component\Document\Support;

use Derafu\Array\ArrayReader;
use Derafu\Array\ArrayWriter;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Config\Trait\OptionsAwareTrait;
use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;
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
     * @var array<string, array<string, mixed>|bool>
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
     * @var array<string, mixed>|null
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
     * @var array<string, mixed>|null
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
     * @var array<string, mixed>|null
     */
    private ?array $libredteData;

    /**
     * Instancia del documento XML asociada al DTE.
     *
     * @var XmlDocumentInterface|null
     */
    private ?XmlDocumentInterface $xmlDocument;

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
     * @var array<string, mixed>|null
     */
    private ?array $timbre = null;

    /**
     * Arreglo con los datos normalizados consolidados con el timbre y la firma
     * si existen en la bolsa.
     *
     * @var array<string, mixed>|null
     */
    private ?array $data = null;

    /**
     * Constructor del contenedor.
     *
     * Recibe los datos en diferentes formatos para pasarlos a los setters que
     * los normalizan y asignan al contenedor.
     *
     * @param string|array<string, mixed>|stdClass|null $inputData
     * @param array<string, mixed>|null $parsedData
     * @param array<string, mixed>|null $normalizedData
     * @param array<string, mixed>|null $libredteData
     * @param array<string, mixed>|OptionsInterface $options
     * @param XmlDocumentInterface|null $xmlDocument
     * @param CafInterface|null $caf
     * @param CertificateInterface|null $certificate
     * @param DocumentInterface|null $document
     * @param TipoDocumentoInterface|null $documentType
     * @param EmisorInterface|null $emisor
     * @param ReceptorInterface|null $receptor
     */
    public function __construct(
        string|array|stdClass|null $inputData = null,
        array|null $parsedData = null,
        array|null $normalizedData = null,
        array|null $libredteData = null,
        array|OptionsInterface|null $options = null,
        XmlDocumentInterface|null $xmlDocument = null,
        CafInterface|null $caf = null,
        CertificateInterface|null $certificate = null,
        DocumentInterface|null $document = null,
        TipoDocumentoInterface|null $documentType = null,
        EmisorInterface|null $emisor = null,
        ReceptorInterface|null $receptor = null
    ) {
        $this
            ->setInputData($inputData)
            ->setParsedData($parsedData)
            ->setNormalizedData($normalizedData)
            ->setLibredteData($libredteData)
            ->setOptions($options ?? [])
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
    public function getOptionsSchema(): array
    {
        return $this->optionsSchema;
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
            $inputData = ValueCaster::requireString(json_encode($inputData));
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
    public function requireInputData(): string
    {
        return $this->getInputData() ?? throw $this->missing('inputData');
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
    public function getParsedDataReader(): ?ArrayReader
    {
        $data = $this->getParsedData();

        return $data === null ? null : ArrayReader::fromArray($data, 'parsed data');
    }

    /**
     * {@inheritDoc}
     */
    public function setNormalizedData(?array $normalizedData): static
    {
        if ($normalizedData !== null) {
            $reader = ArrayReader::fromArray($normalizedData, 'normalized data');
            $normalizedData = $reader->getMap('DTE.Documento')
                ?? $reader->getMap('DTE.Exportaciones')
                ?? $reader->getMap('DTE.Liquidacion')
                ?? $normalizedData
            ;
        }

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
    public function getNormalizedDataReader(): ?ArrayReader
    {
        $data = $this->getNormalizedData();

        return $data === null ? null : ArrayReader::fromArray($data, 'normalized data');
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function requireNormalizedData(): array
    {
        return $this->getNormalizedData() ?? throw $this->missing('normalizedData');
    }

    /**
     * {@inheritDoc}
     */
    public function requireNormalizedDataReader(): ArrayReader
    {
        return ArrayReader::fromArray($this->requireNormalizedData(), 'normalized data');
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
    public function getLibredteDataReader(): ?ArrayReader
    {
        $data = $this->getLibredteData();

        return $data === null ? null : ArrayReader::fromArray($data, 'LibreDTE data');
    }

    /**
     * {@inheritDoc}
     */
    public function getParserOptions(): array
    {
        return $this->getOptions()->getReader()->getArray('parser') ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getBuilderOptions(): array
    {
        return $this->getOptions()->getReader()->getArray('builder') ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getNormalizerOptions(): array
    {
        return $this->getOptions()->getReader()->getArray('normalizer') ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getSanitizerOptions(): array
    {
        return $this->getOptions()->getReader()->getArray('sanitizer') ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getValidatorOptions(): array
    {
        return $this->getOptions()->getReader()->getArray('validator') ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getRendererOptions(): array
    {
        return $this->getOptions()->getReader()->getArray('renderer') ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function setXmlDocument(?XmlDocumentInterface $xmlDocument): static
    {
        $this->xmlDocument = $xmlDocument;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocument(): ?XmlDocumentInterface
    {
        return $this->xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function requireXmlDocument(): XmlDocumentInterface
    {
        return $this->getXmlDocument() ?? throw $this->missing('xmlDocument');
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
    public function requireCaf(): CafInterface
    {
        return $this->getCaf() ?? throw $this->missing('caf');
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
    public function requireCertificate(): CertificateInterface
    {
        return $this->getCertificate() ?? throw $this->missing('certificate');
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
    public function requireDocument(): DocumentInterface
    {
        return $this->getDocument() ?? throw $this->missing('document');
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
    public function requireTipoDocumento(): TipoDocumentoInterface
    {
        return $this->getTipoDocumento() ?? throw $this->missing('tipoDocumento');
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentTypeId(): ?int
    {
        $TipoDTE = $this->getParsedDataReader()?->get('Encabezado.IdDoc.TipoDTE', ValueType::mixed())
            ?? $this->getNormalizedDataReader()?->get('Encabezado.IdDoc.TipoDTE', ValueType::mixed())
            ?? $this->xmlDocument?->query('//Encabezado/IdDoc/TipoDTE')
            ?? $this->document?->getCodigo()
            ?? null
        ;

        if (!$TipoDTE) {
            throw new DocumentException(
                'The document type (TipoDTE) must be indicated in the DTE data.'
            );
        }

        // Un tipo que no es numérico (por ejemplo, el alias de un documento) no
        // tiene código: se entrega 0 y el error es que no existe ese tipo.
        return is_numeric($TipoDTE) ? (int) $TipoDTE : 0;
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
    public function requireEmisor(): EmisorInterface
    {
        return $this->getEmisor() ?? throw $this->missing('emisor');
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
        $tagXml = $this->requireTipoDocumento()->requireTagXml()->getNombre();
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
     *
     * @return array<string, mixed>
     */
    public function requireData(): array
    {
        return $this->getData() ?? throw $this->missing('data');
    }

    /**
     * {@inheritDoc}
     */
    public function getDataReader(): ?ArrayReader
    {
        $data = $this->getData();

        return $data === null ? null : ArrayReader::fromArray($data, 'document data');
    }

    /**
     * {@inheritDoc}
     */
    public function requireDataReader(): ArrayReader
    {
        return ArrayReader::fromArray($this->requireData(), 'document data');
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentData(): ?array
    {
        if (!isset($this->document)) {
            return null;
        }

        $documentData = $this->document->getData();
        $documentExtra = $this->getLibredteDataReader()?->getArray('extra.dte');

        if (empty($documentExtra)) {
            return $documentData;
        }

        return array_replace_recursive($documentData, $documentExtra);
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentExtra(): ?array
    {
        if (!isset($this->document)) {
            return null;
        }

        $extra = $this->getLibredteDataReader()?->getMap('extra');

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

        $data = $this->requireNormalizedDataReader();
        $rut = $data->requireAsString('Encabezado.Emisor.RUTEmisor');
        $tipo = $data->requireAsInt('Encabezado.IdDoc.TipoDTE');

        if (is_int($folio)) {
            return sprintf('LibreDTE_%s_T%03dF%012d', $rut, $tipo, $folio);
        } else {
            return sprintf('LibreDTE_%s_%03d-%s', $rut, $tipo, $folio);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setFolio(int $folio): static
    {
        if ($this->getXmlDocument()) {
            throw new LogicException(
                'The document number cannot be assigned after the XML document has been generated.'
            );
        }

        $parsedData = $this->getParsedData();
        $normalizedData = $this->getNormalizedData();

        if ($parsedData === null && $normalizedData === null) {
            throw new LogicException(
                'The document number cannot be assigned when there are no parsed or normalized data.'
            );
        }

        if ($parsedData !== null) {
            ArrayWriter::fromReference($parsedData)->set('Encabezado.IdDoc.Folio', $folio);
            $this->setParsedData($parsedData);
        }

        if ($normalizedData !== null) {
            ArrayWriter::fromReference($normalizedData)->set('Encabezado.IdDoc.Folio', $folio);
            $this->setNormalizedData($normalizedData);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFolio(): int|string|null
    {
        $data = $this->getNormalizedDataReader() ?? $this->getParsedDataReader();

        $folio = $data?->get('Encabezado.IdDoc.Folio', ValueType::mixed());

        if (!$folio) {
            return null;
        }

        return is_numeric($folio) ? (int) $folio : ValueCaster::toString($folio);
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
            ?? $this->getParsedTipoDte()
            ?? 'documento_desconocido'
        ;
    }

    /**
     * Obtiene el tipo de documento indicado en los datos procesados (parseados).
     *
     * @return string|null `null` si los datos no indican el tipo de documento.
     */
    private function getParsedTipoDte(): ?string
    {
        $tipo = $this->getParsedDataReader()?->get('Encabezado.IdDoc.TipoDTE', ValueType::mixed());

        return $tipo === null ? null : ValueCaster::requireString($tipo);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>&array{
     *     document_stamp: string|null,
     *     document_xml: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'document' => $this->getDocumentData(),
            'document_parsed' => $this->getParsedData(),
            'document_normalized' => $this->getNormalizedData(),
            'document_extra' => $this->getDocumentExtra(),
            'document_stamp' => $this->getDocumentStamp(),
            'document_auth' => $this->getDocumentAuth(),
            'document_type' => $this->getDocumentType()?->toArray(),
            'document_id' => $this->document?->getId(),
            'document_xml' => $this->document?->saveXml(),
            'options' => $this->getOptions()->all(),
            'certificate' => $this->certificate?->toArray(),
            'emisor' => $this->emisor?->toArray(),
            'receptor' => $this->receptor?->toArray(),
            'caf' => $this->caf?->toArray(),
            'timbre' => $this->timbre,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();

        if ($array['document_stamp'] !== null) {
            $array['document_stamp'] = base64_encode($array['document_stamp']);
        }

        if ($array['document_xml'] !== null) {
            $array['document_xml'] = base64_encode($array['document_xml']);
        }

        if ($this->caf !== null) {
            $array['caf'] = $this->caf->jsonSerialize();
        }

        return $array;
    }

    /**
     * Crea la excepción de un dato que la bolsa no tiene asignado.
     *
     * @param string $item Nombre del dato que falta.
     * @return DocumentException
     */
    private function missing(string $item): DocumentException
    {
        return new DocumentException([
            'The document bag does not have {item}.',
            'item' => $item,
        ]);
    }
}
