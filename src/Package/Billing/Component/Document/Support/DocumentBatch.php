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

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Config\Trait\OptionsAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBatchInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BatchProcessorException;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Contenedor de datos para procesamiento en lote de documentos tributarios.
 *
 * Permite "mover" varios documentos, junto a otros datos asociados, por métodos
 * de manera sencilla y, sobre todo, extensible.
 */
class DocumentBatch implements DocumentBatchInterface
{
    use OptionsAwareTrait;

    /**
     * Reglas de esquema de las opciones del lote de documentos.
     *
     * Acá solo se indicarán los índices que deben pueden existir en las
     * opciones. No se define el esquema de cada opción pues cada clase que
     * utilice estas opciones deberá resolver y validar sus propias opciones.
     *
     * @var array
     */
    protected array $optionsSchema = [
        'batch_processor' => [
            'types' => 'array',
            'default' => [],
        ],
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
     * Ruta al archivo que contiene el lote de documentos que se deben procesar.
     *
     * @var string|null
     */
    private ?string $inputFile;

    /**
     * Contenido con el lote de documentos que se deben procesar.
     *
     * Si el lote se creó a partir de un archivo, se lee la primera vez que se
     * solicita.
     *
     * @var string|null
     */
    private ?string $inputData;

    /**
     * Emisor del documento tributario.
     *
     * @var EmisorInterface|null
     */
    private ?EmisorInterface $emisor = null;

    /**
     * Certificado digital (firma electrónica) para la firma del documento.
     *
     * @var CertificateInterface|null
     */
    private ?CertificateInterface $certificate = null;

    /**
     * Listado de bolsas con los documentos procesados.
     *
     * @var DocumentBagInterface[]
     */
    private array $documentBags = [];

    /**
     * Constructor del lote.
     *
     * Se debe indicar el contenido del lote (`inputData`) o la ruta del archivo
     * que lo contiene (`inputFile`), pero no ambos.
     *
     * @param string|null $inputData Contenido con los documentos.
     * @param string|null $inputFile Ruta al archivo con los documentos.
     * @param array|OptionsInterface|null $options
     * @throws BatchProcessorException Si no se indica ninguno de los dos orígenes
     * o se indican ambos.
     */
    public function __construct(
        ?string $inputData = null,
        ?string $inputFile = null,
        array|OptionsInterface|null $options = []
    ) {
        if ($inputData === null && $inputFile === null) {
            throw new BatchProcessorException(
                'Se debe indicar el contenido (inputData) o la ruta (inputFile) del lote.'
            );
        }
        if ($inputData !== null && $inputFile !== null) {
            throw new BatchProcessorException(
                'Se debe indicar solo uno: el contenido (inputData) o la ruta (inputFile) del lote.'
            );
        }

        $this->inputData = $inputData;
        $this->inputFile = $inputFile;
        $this->setOptions($options ?? []);
    }

    /**
     * {@inheritDoc}
     */
    public function getInputFile(): ?string
    {
        return $this->inputFile;
    }

    /**
     * {@inheritDoc}
     */
    public function getInputData(): string
    {
        if ($this->inputData === null) {
            $file = (string) $this->inputFile;
            $data = is_readable($file) ? file_get_contents($file) : false;
            if ($data === false) {
                throw new BatchProcessorException(
                    sprintf('No fue posible leer el archivo %s.', $file),
                    documentBatch: $this
                );
            }
            $this->inputData = $data;
        }

        return $this->inputData;
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
    public function setDocumentBags(array $bags): static
    {
        $this->documentBags = $bags;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentBags(): array
    {
        return $this->documentBags;
    }

    /**
     * {@inheritDoc}
     */
    public function getBatchProcessorOptions(): array
    {
        return $this->getOptions()->get('batch_processor')->all();
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'document_bags' => $this->getDocumentBags(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $data = $this->toArray();

        $data['document_bags'] = array_map(fn (DocumentBagInterface $bag) => $bag->jsonSerialize(), $data['document_bags']);

        return $data;
    }
}
